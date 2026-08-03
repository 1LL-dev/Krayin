<?php

namespace Webkul\DataTransfer\Helpers\Importers\Products;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class Importer extends AbstractImporter
{
    /**
     * Error code for non existing SKU.
     */
    const ERROR_SKU_NOT_FOUND_FOR_DELETE = 'sku_not_found_to_delete';

    /**
     * Error message templates.
     */
    protected array $messages = [
        self::ERROR_SKU_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.products.validation.errors.sku-not-found',
    ];

    /**
     * Permanent entity columns.
     */
    protected array $permanentAttributes = ['sku'];

    /**
     * Permanent entity column.
     */
    protected string $masterAttributeCode = 'sku';

    /**
     * Cached attributes.
     */
    protected mixed $attributes = [];

    /**
     * Valid csv columns.
     */
    protected array $validColumnNames = [
        'sku',
        'name',
        'description',
        'quantity',
        'price',
        'owner_id',
        'owner_name',
        'owner_phone',
        'assigned_agent_id',
    ];

    /**
     * Contacts loaded while resolving property owners.
     */
    protected Collection $owners;

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected ImportBatchRepository $importBatchRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected ProductRepository $productRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected AttributeValueRepository $attributeValueRepository,
        protected PersonRepository $personRepository,
        protected SKUStorage $skuStorage
    ) {
        parent::__construct(
            $importBatchRepository,
            $attributeRepository,
            $attributeValueRepository
        );

        $this->initAttributes();
    }

    /**
     * Load all attributes and families to use later.
     */
    protected function initAttributes(): void
    {
        $this->attributes = $this->attributeRepository
            ->scopeQuery(fn ($query) => $query
                ->where('entity_type', 'products')
                ->with('options'))
            ->get();

        foreach ($this->attributes as $attribute) {
            $this->validColumnNames[] = $attribute->code;
        }
    }

    /**
     * Initialize Product error templates.
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    /**
     * Save validated batches.
     */
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $source->rewind();

        $this->skuStorage->init();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            $this->validateRow($rowData, $source->getCurrentRowNumber());

            $source->next();
        }

        parent::saveValidatedBatches();

        return $this;
    }

    /**
     * Validates row.
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        /**
         * If row is already validated than no need for further validation.
         */
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        /**
         * If import action is delete than no need for further validation.
         */
        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->isSKUExist($rowData['sku'])) {
                $this->skipRow($rowNumber, self::ERROR_SKU_NOT_FOUND_FOR_DELETE, 'sku');

                return false;
            }

            return true;
        }

        $rowData = $this->normalizeAttributeValues($rowData);

        /**
         * Validate product attributes
         */
        $rules = $this->getValidationRules('products', $rowData);

        $rules['owner_id'] = ['nullable', 'integer', 'exists:persons,id'];
        $rules['owner_name'] = ['nullable', 'string', 'required_without:owner_id'];
        $rules['owner_phone'] = ['nullable', 'string'];
        $rules['assigned_agent_id'] = ['nullable', 'integer', 'exists:users,id'];

        foreach ($this->attributes as $attribute) {
            if (! array_key_exists($attribute->code, $rowData)) {
                continue;
            }

            if (in_array($attribute->type, ['select', 'radio'])) {
                $rules[$attribute->code][] = Rule::exists('attribute_options', 'id')
                    ->where('attribute_id', $attribute->id);
            }

            if ($attribute->type === 'address') {
                $rules[$attribute->code][] = 'array';
            }
        }

        $validator = Validator::make($rowData, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $failedAttributes = $validator->failed();

                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    /**
     * Start the import process.
     */
    public function importBatch(ImportBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->import->action == Import::ACTION_DELETE) {
            $this->deleteProducts($batch);
        } else {
            $this->saveProductsData($batch);
        }

        /**
         * Update import batch summary.
         */
        $batch = $this->importBatchRepository->update([
            'state' => Import::STATE_PROCESSED,

            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    /**
     * Delete products from current batch.
     */
    protected function deleteProducts(ImportBatchContract $batch): bool
    {
        /**
         * Load SKU storage with batch skus.
         */
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isSKUExist($rowData['sku'])) {
                continue;
            }

            $product = $this->skuStorage->get($rowData['sku']);

            $idsToDelete[] = $product['id'];
        }

        $idsToDelete = array_unique($idsToDelete);

        $this->deletedItemsCount = count($idsToDelete);

        $this->productRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    /**
     * Save products from current batch.
     */
    protected function saveProductsData(ImportBatchContract $batch): bool
    {
        /**
         * Load SKU storage with batch skus.
         */
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $products = [];

        /**
         * Prepare products for import.
         */
        foreach ($batch->data as $rowData) {
            $this->prepareProducts($rowData, $products);
        }

        $this->saveProducts($products);

        return true;
    }

    /**
     * Prepare products from current batch.
     */
    public function prepareProducts(array $rowData, array &$products): void
    {
        if (empty($rowData['owner_id']) && filled($rowData['owner_name'] ?? null)) {
            $rowData['owner_id'] = $this->resolveOwnerId(
                $rowData['owner_name'],
                $rowData['owner_phone'] ?? null,
                $rowData['assigned_agent_id'] ?? null,
            );
        }

        unset($rowData['owner_name'], $rowData['owner_phone']);

        if ($this->isSKUExist($rowData['sku'])) {
            $products['update'][$rowData['sku']] = $rowData;
        } else {
            $products['insert'][$rowData['sku']] = [
                ...$rowData,
                'created_at' => $rowData['created_at'] ?? now(),
                'updated_at' => $rowData['updated_at'] ?? now(),
            ];
        }
    }

    /**
     * Save products from current batch.
     */
    public function saveProducts(array $products): void
    {
        if (! empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);

            foreach ($products['update'] as $sku => $productData) {
                $product = $this->skuStorage->get($sku);

                $this->productRepository->update([
                    ...$productData,
                    'entity_type' => 'products',
                ], $product['id']);
            }
        }

        if (! empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);

            foreach ($products['insert'] as $productData) {
                $this->productRepository->create([
                    ...$productData,
                    'entity_type' => 'products',
                ]);
            }
        }
    }

    /**
     * Save channels from current batch.
     */
    public function saveChannels(array $channels): void
    {
        $productChannels = [];

        foreach ($channels as $sku => $channelIds) {
            $product = $this->skuStorage->get($sku);

            foreach (array_unique($channelIds) as $channelId) {
                $productChannels[] = [
                    'product_id' => $product['id'],
                    'channel_id' => $channelId,
                ];
            }
        }

        DB::table('product_channels')->upsert(
            $productChannels,
            [
                'product_id',
                'channel_id',
            ],
        );
    }

    /**
     * Save links.
     */
    public function loadUnloadedSKUs(array $skus): void
    {
        $notLoadedSkus = [];

        foreach ($skus as $sku) {
            if ($this->skuStorage->has($sku)) {
                continue;
            }

            $notLoadedSkus[] = $sku;
        }

        /**
         * Load not loaded SKUs to the sku storage.
         */
        if (! empty($notLoadedSkus)) {
            $this->skuStorage->load($notLoadedSkus);
        }
    }

    /**
     * Check if SKU exists.
     */
    public function isSKUExist(string $sku): bool
    {
        return $this->skuStorage->has($sku);
    }

    /**
     * Prepare row data to save into the database.
     */
    protected function prepareRowForDb(array $rowData): array
    {
        return $this->normalizeAttributeValues(
            parent::prepareRowForDb($rowData)
        );
    }

    /**
     * Convert human-readable property values into the values stored by Krayin.
     */
    protected function normalizeAttributeValues(array $rowData): array
    {
        foreach ($this->attributes as $attribute) {
            if (! array_key_exists($attribute->code, $rowData)) {
                continue;
            }

            $value = $rowData[$attribute->code];

            if ($attribute->type === 'address' && is_string($value) && trim($value) !== '') {
                $decodedValue = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $rowData[$attribute->code] = $decodedValue;
                }
            }

            if (
                in_array($attribute->type, ['select', 'radio'])
                && filled($value)
                && ! is_numeric($value)
            ) {
                $option = $attribute->options->first(
                    fn ($option) => strcasecmp($option->name, trim($value)) === 0
                );

                if ($option) {
                    $rowData[$attribute->code] = $option->id;
                }
            }
        }

        return $rowData;
    }

    /**
     * Find or create the CRM contact who owns an imported property.
     */
    protected function resolveOwnerId(string $name, mixed $phone, mixed $assignedAgentId): int
    {
        $name = trim($name);
        $phone = trim((string) $phone);
        $normalizedPhone = $this->normalizePhone($phone);
        $cacheKey = mb_strtolower($name).'|'.$normalizedPhone;

        if (! isset($this->owners)) {
            $this->owners = $this->personRepository->all([
                'id',
                'name',
                'emails',
                'contact_numbers',
                'user_id',
            ]);
        }

        $owner = $this->owners->first(function ($person) use ($name, $normalizedPhone) {
            if ($normalizedPhone !== '') {
                $hasMatchingPhone = collect($person->contact_numbers ?? [])
                    ->contains(fn ($number) => $this->normalizePhone($number['value'] ?? null) === $normalizedPhone);

                if ($hasMatchingPhone) {
                    return true;
                }
            }

            return strcasecmp(trim($person->name), $name) === 0;
        });

        if ($owner) {
            return $owner->id;
        }

        $contactNumbers = $phone !== ''
            ? [['label' => 'work', 'value' => $phone]]
            : [];

        $personData = [
            'name' => $name,
            'emails' => [],
            'contact_numbers' => $contactNumbers,
            'user_id' => filled($assignedAgentId) ? $assignedAgentId : null,
            'unique_id' => 'property-import|'.hash('sha256', $cacheKey),
        ];

        $owner = $this->personRepository
            ->getModel()
            ->newQuery()
            ->create($personData);

        $this->attributeValueRepository->save([
            ...$personData,
            'entity_type' => 'persons',
            'entity_id' => $owner->id,
        ]);

        $this->owners->push($owner);

        return $owner->id;
    }

    /**
     * Normalize a phone number for contact matching.
     */
    protected function normalizePhone(mixed $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }
}
