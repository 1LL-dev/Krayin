<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\Import;

uses(DatabaseTransactions::class);

it('links the property directory to a property-preselected import form', function () {
    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Import Properties')
        ->assertSee(route('admin.settings.data_transfer.imports.create', ['type' => 'products']), false);

    $this->actingAs($admin)
        ->get(route('admin.settings.data_transfer.imports.create', ['type' => 'products']))
        ->assertOk()
        ->assertSee('Import Properties')
        ->assertSee('value="products"', false)
        ->assertSee('Accepted formats: CSV, XLS, XLSX, or TXT.');
});

it('imports a property spreadsheet with real estate fields', function () {
    Storage::fake('public');

    $admin = getDefaultAdmin();
    $sku = 'IMPORT-'.str()->uuid();
    $ownerName = 'Import Owner '.str()->uuid();
    $ownerPhone = '+971 50 123 4567';
    $address = json_encode([
        'address' => '125 Sunset Avenue',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'country' => 'US',
        'postcode' => '90026',
    ]);

    $stream = fopen('php://temp', 'r+');

    fputcsv($stream, [
        'sku',
        'name',
        'description',
        'quantity',
        'price',
        'owner_name',
        'owner_phone',
        'assigned_agent_id',
        'property_type',
        'property_status',
        'property_address',
        'bedrooms',
        'bathrooms',
        'area',
        'area_unit',
        'parking_spaces',
    ]);

    fputcsv($stream, [
        $sku,
        'Imported Sunset Residence',
        'Imported from the property spreadsheet.',
        1,
        725000,
        $ownerName,
        $ownerPhone,
        $admin->id,
        'House',
        'Available',
        $address,
        3,
        2,
        1850,
        'sqft',
        2,
    ]);

    rewind($stream);

    $file = UploadedFile::fake()->createWithContent('properties.csv', stream_get_contents($stream));

    fclose($stream);

    $this->actingAs($admin)
        ->post(route('admin.settings.data_transfer.imports.store'), [
            'type' => 'products',
            'action' => 'append',
            'validation_strategy' => 'skip-errors',
            'allowed_errors' => 10,
            'field_separator' => ',',
            'file' => $file,
        ])
        ->assertRedirect();

    $import = Import::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->getJson(route('admin.settings.data_transfer.imports.validate', $import->id))
        ->assertOk()
        ->assertJsonPath('is_valid', true);

    $this->actingAs($admin)
        ->getJson(route('admin.settings.data_transfer.imports.start', $import->id))
        ->assertOk();

    $product = DB::table('products')->where('sku', $sku)->first();

    expect($product)
        ->not->toBeNull()
        ->and($product->name)->toBe('Imported Sunset Residence')
        ->and((float) $product->price)->toBe(725000.0)
        ->and((int) $product->assigned_agent_id)->toBe($admin->id);

    $owner = DB::table('persons')->where('id', $product->owner_id)->first();

    expect($owner)
        ->not->toBeNull()
        ->and($owner->name)->toBe($ownerName)
        ->and(json_decode($owner->contact_numbers, true)[0]['value'])->toBe($ownerPhone);

    foreach ([
        'property_type' => 'House',
        'property_status' => 'Available',
        'area_unit' => 'sqft',
    ] as $attributeCode => $optionName) {
        $savedOption = DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->join('attribute_options', 'attribute_options.id', '=', 'attribute_values.integer_value')
            ->where('attribute_values.entity_type', 'products')
            ->where('attribute_values.entity_id', $product->id)
            ->where('attributes.code', $attributeCode)
            ->value('attribute_options.name');

        expect($savedOption)->toBe($optionName);
    }

    $savedAddress = DB::table('attribute_values')
        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
        ->where('attribute_values.entity_type', 'products')
        ->where('attribute_values.entity_id', $product->id)
        ->where('attributes.code', 'property_address')
        ->value('attribute_values.json_value');

    expect(json_decode($savedAddress, true))->toMatchArray([
        'address' => '125 Sunset Avenue',
        'city' => 'Los Angeles',
    ]);
});

it('rejects unsupported import actions and validation strategies', function () {
    Storage::fake('public');

    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->from(route('admin.settings.data_transfer.imports.create'))
        ->post(route('admin.settings.data_transfer.imports.store'), [
            'type' => 'products',
            'action' => 'replace-everything',
            'validation_strategy' => 'ignore-everything',
            'allowed_errors' => 10,
            'field_separator' => ',',
            'file' => UploadedFile::fake()->createWithContent('properties.csv', "sku\nTEST-1\n"),
        ])
        ->assertRedirect(route('admin.settings.data_transfer.imports.create'))
        ->assertSessionHasErrors(['action', 'validation_strategy']);
});
