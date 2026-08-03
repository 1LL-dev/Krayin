<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the standard attributes used by property listings.
     */
    public function up(): void
    {
        $now = now();

        DB::table('attributes')
            ->where('entity_type', 'products')
            ->whereIn('code', ['name', 'sku', 'description', 'price'])
            ->update([
                'updated_at' => $now,
            ]);

        foreach ([
            'name' => 'Property Title',
            'sku' => 'Listing ID',
            'description' => 'Property Description',
            'price' => 'Listing Price',
        ] as $code => $name) {
            DB::table('attributes')
                ->where('entity_type', 'products')
                ->where('code', $code)
                ->update(['name' => $name]);
        }

        $attributes = [
            [
                'code' => 'property_type',
                'name' => 'Property Type',
                'type' => 'select',
                'validation' => null,
                'sort_order' => 10,
                'is_required' => true,
                'options' => ['Apartment', 'Villa', 'House', 'Townhouse', 'Land', 'Commercial', 'Office', 'Warehouse'],
            ],
            [
                'code' => 'property_status',
                'name' => 'Property Status',
                'type' => 'select',
                'validation' => null,
                'sort_order' => 11,
                'is_required' => true,
                'options' => ['Draft', 'Available', 'Reserved', 'Sold', 'Rented', 'Off Market'],
            ],
            [
                'code' => 'property_address',
                'name' => 'Address',
                'type' => 'address',
                'validation' => null,
                'sort_order' => 20,
                'is_required' => false,
            ],
            [
                'code' => 'bedrooms',
                'name' => 'Bedrooms',
                'type' => 'text',
                'validation' => 'integer',
                'sort_order' => 30,
                'is_required' => false,
            ],
            [
                'code' => 'bathrooms',
                'name' => 'Bathrooms',
                'type' => 'text',
                'validation' => 'integer',
                'sort_order' => 31,
                'is_required' => false,
            ],
            [
                'code' => 'area',
                'name' => 'Area',
                'type' => 'text',
                'validation' => 'decimal',
                'sort_order' => 32,
                'is_required' => false,
            ],
            [
                'code' => 'area_unit',
                'name' => 'Area Unit',
                'type' => 'select',
                'validation' => null,
                'sort_order' => 33,
                'is_required' => false,
                'options' => ['sqm', 'sqft'],
            ],
            [
                'code' => 'parking_spaces',
                'name' => 'Parking Spaces',
                'type' => 'text',
                'validation' => 'integer',
                'sort_order' => 34,
                'is_required' => false,
            ],
        ];

        foreach ($attributes as $attribute) {
            $attributeId = DB::table('attributes')
                ->where('entity_type', 'products')
                ->where('code', $attribute['code'])
                ->value('id');

            $attributeData = [
                'name' => $attribute['name'],
                'type' => $attribute['type'],
                'lookup_type' => null,
                'entity_type' => 'products',
                'sort_order' => $attribute['sort_order'],
                'validation' => $attribute['validation'],
                'is_required' => $attribute['is_required'],
                'is_unique' => false,
                'quick_add' => false,
                'is_user_defined' => false,
                'updated_at' => $now,
            ];

            if ($attributeId) {
                DB::table('attributes')->where('id', $attributeId)->update($attributeData);
            } else {
                $attributeId = DB::table('attributes')->insertGetId(array_merge($attributeData, [
                    'code' => $attribute['code'],
                    'created_at' => $now,
                ]));
            }

            if (! isset($attribute['options'])) {
                continue;
            }

            DB::table('attribute_options')->where('attribute_id', $attributeId)->delete();

            foreach ($attribute['options'] as $sortOrder => $name) {
                DB::table('attribute_options')->insert([
                    'attribute_id' => $attributeId,
                    'name' => $name,
                    'sort_order' => $sortOrder + 1,
                ]);
            }
        }
    }

    /**
     * Remove property-specific attributes and restore the original product labels.
     */
    public function down(): void
    {
        DB::table('attributes')
            ->where('entity_type', 'products')
            ->whereIn('code', [
                'property_type',
                'property_status',
                'property_address',
                'bedrooms',
                'bathrooms',
                'area',
                'area_unit',
                'parking_spaces',
            ])
            ->delete();

        foreach ([
            'name' => 'Name',
            'sku' => 'SKU',
            'description' => 'Description',
            'price' => 'Price',
        ] as $code => $name) {
            DB::table('attributes')
                ->where('entity_type', 'products')
                ->where('code', $code)
                ->update(['name' => $name]);
        }
    }
};
