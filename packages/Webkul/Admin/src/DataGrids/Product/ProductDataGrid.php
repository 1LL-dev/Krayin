<?php

namespace Webkul\Admin\DataGrids\Product;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\DataGrid\DataGrid;
use Webkul\Tag\Repositories\TagRepository;
use Webkul\User\Repositories\UserRepository;

class ProductDataGrid extends DataGrid
{
    /**
     * Default property listing sort column.
     *
     * @var string
     */
    protected $sortColumn = 'products.created_at';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('products')
            ->leftJoin('product_tags', 'products.id', '=', 'product_tags.product_id')
            ->leftJoin('tags', 'tags.id', '=', 'product_tags.tag_id')
            ->leftJoin('persons as owners', 'products.owner_id', '=', 'owners.id')
            ->leftJoin('users as assigned_agents', 'products.assigned_agent_id', '=', 'assigned_agents.id')
            ->leftJoin('attributes as property_status_attribute', function ($join) {
                $join->on('property_status_attribute.entity_type', '=', DB::raw("'products'"))
                    ->where('property_status_attribute.code', 'property_status');
            })
            ->leftJoin('attribute_values as property_status_value', function ($join) {
                $join->on('products.id', '=', 'property_status_value.entity_id')
                    ->on('property_status_attribute.id', '=', 'property_status_value.attribute_id')
                    ->where('property_status_value.entity_type', 'products');
            })
            ->leftJoin('attribute_options as property_status', 'property_status_value.integer_value', '=', 'property_status.id')
            ->leftJoin('attributes as property_type_attribute', function ($join) {
                $join->on('property_type_attribute.entity_type', '=', DB::raw("'products'"))
                    ->where('property_type_attribute.code', 'property_type');
            })
            ->leftJoin('attribute_values as property_type_value', function ($join) {
                $join->on('products.id', '=', 'property_type_value.entity_id')
                    ->on('property_type_attribute.id', '=', 'property_type_value.attribute_id')
                    ->where('property_type_value.entity_type', 'products');
            })
            ->leftJoin('attribute_options as property_type', 'property_type_value.integer_value', '=', 'property_type.id')
            ->leftJoin('attributes as property_address_attribute', function ($join) {
                $join->on('property_address_attribute.entity_type', '=', DB::raw("'products'"))
                    ->where('property_address_attribute.code', 'property_address');
            })
            ->leftJoin('attribute_values as property_address_value', function ($join) {
                $join->on('products.id', '=', 'property_address_value.entity_id')
                    ->on('property_address_attribute.id', '=', 'property_address_value.attribute_id')
                    ->where('property_address_value.entity_type', 'products');
            })
            ->leftJoin('attributes as bedrooms_attribute', function ($join) {
                $join->on('bedrooms_attribute.entity_type', '=', DB::raw("'products'"))
                    ->where('bedrooms_attribute.code', 'bedrooms');
            })
            ->leftJoin('attribute_values as bedrooms_value', function ($join) {
                $join->on('products.id', '=', 'bedrooms_value.entity_id')
                    ->on('bedrooms_attribute.id', '=', 'bedrooms_value.attribute_id')
                    ->where('bedrooms_value.entity_type', 'products');
            })
            ->leftJoin('attributes as bathrooms_attribute', function ($join) {
                $join->on('bathrooms_attribute.entity_type', '=', DB::raw("'products'"))
                    ->where('bathrooms_attribute.code', 'bathrooms');
            })
            ->leftJoin('attribute_values as bathrooms_value', function ($join) {
                $join->on('products.id', '=', 'bathrooms_value.entity_id')
                    ->on('bathrooms_attribute.id', '=', 'bathrooms_value.attribute_id')
                    ->where('bathrooms_value.entity_type', 'products');
            })
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'tags.name as tag_name',
                'owners.name as owner_name',
                'assigned_agents.name as assigned_agent_name',
                'property_status.name as property_status_name',
                'property_type.name as property_type_name',
                'property_address_value.json_value as location',
                'bedrooms_value.text_value as bedrooms',
                'bathrooms_value.text_value as bathrooms',
            )
            ->groupBy('products.id');

        $this->addFilter('id', 'products.id');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('name', 'products.name');
        $this->addFilter('price', 'products.price');
        $this->addFilter('owner_name', 'products.owner_id');
        $this->addFilter('assigned_agent_name', 'products.assigned_agent_id');
        $this->addFilter('property_status_name', 'property_status.id');
        $this->addFilter('property_type_name', 'property_type.id');
        $this->addFilter('tag_name', 'tags.name');

        return $queryBuilder;
    }

    /**
     * Process requested sorting.
     *
     * Only columns defined by this Property DataGrid can be passed to the query
     * builder, preventing stale browser state from creating an invalid order.
     */
    protected function processRequestedSorting($requestedSort): Builder
    {
        $sortableColumns = [
            'id' => 'products.id',
            'sku' => 'products.sku',
            'name' => 'products.name',
            'price' => 'products.price',
            'owner_name' => 'owners.name',
            'assigned_agent_name' => 'assigned_agents.name',
            'property_status_name' => 'property_status.name',
            'property_type_name' => 'property_type.name',
            'tag_name' => 'tags.name',
        ];

        $requestedColumn = $requestedSort['column'] ?? null;

        if (! isset($sortableColumns[$requestedColumn])) {
            return $this->queryBuilder->orderBy($this->sortColumn, $this->sortOrder);
        }

        $sortOrder = strtolower($requestedSort['order'] ?? $this->sortOrder) === 'asc'
            ? 'asc'
            : 'desc';

        return $this->queryBuilder->orderBy($sortableColumns[$requestedColumn], $sortOrder);
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'sku',
            'label' => trans('admin::app.products.index.datagrid.sku'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'owner_name',
            'label' => trans('admin::app.products.index.datagrid.owner'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => PersonRepository::class,
                'column' => ['label' => 'name', 'value' => 'name'],
            ],
        ]);

        $this->addColumn([
            'index' => 'assigned_agent_name',
            'label' => trans('admin::app.products.index.datagrid.assigned-agent'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => UserRepository::class,
                'column' => ['label' => 'name', 'value' => 'name'],
            ],
        ]);

        $this->addColumn([
            'index' => 'property_status_name',
            'label' => trans('admin::app.products.index.datagrid.property-status'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => DB::table('attribute_options')
                ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
                ->where('attributes.entity_type', 'products')
                ->where('attributes.code', 'property_status')
                ->orderBy('attribute_options.sort_order')
                ->get(['attribute_options.name as label', 'attribute_options.id as value'])
                ->toArray(),
        ]);

        $this->addColumn([
            'index' => 'property_type_name',
            'label' => trans('admin::app.products.index.datagrid.property-type'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => DB::table('attribute_options')
                ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
                ->where('attributes.entity_type', 'products')
                ->where('attributes.code', 'property_type')
                ->orderBy('attribute_options.sort_order')
                ->get(['attribute_options.name as label', 'attribute_options.id as value'])
                ->toArray(),
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.products.index.datagrid.name'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'price',
            'label' => trans('admin::app.products.index.datagrid.price'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                return core()->formatBasePrice($row->price, 2);
            },
        ]);

        $this->addColumn([
            'index' => 'tag_name',
            'label' => trans('admin::app.products.index.datagrid.tag-name'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'closure' => fn ($row) => $row->tag_name ?? '--',
            'filterable_options' => [
                'repository' => TagRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('products.view')) {
            $this->addAction([
                'index' => 'view',
                'icon' => 'icon-eye',
                'title' => trans('admin::app.products.index.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.products.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('products.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.products.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.products.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('products.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.products.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.products.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.products.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.products.mass_delete'),
        ]);
    }
}
