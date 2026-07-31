<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['code' => 'client_intent', 'name' => 'Client Intent', 'type' => 'select', 'sort_order' => 20, 'options' => ['Buyer', 'Renter', 'Seller']],
            ['code' => 'purchase_preference', 'name' => 'Buying or Renting Preference', 'type' => 'select', 'sort_order' => 21, 'options' => ['Buy', 'Rent']],
            ['code' => 'budget_min', 'name' => 'Minimum Budget', 'type' => 'price', 'sort_order' => 22],
            ['code' => 'budget_max', 'name' => 'Maximum Budget', 'type' => 'price', 'sort_order' => 23],
            ['code' => 'preferred_location', 'name' => 'Preferred Location', 'type' => 'text', 'sort_order' => 24],
            ['code' => 'preferred_property_type', 'name' => 'Preferred Property Type', 'type' => 'select', 'sort_order' => 25, 'options' => ['Apartment', 'Villa', 'House', 'Townhouse', 'Land', 'Commercial', 'Office', 'Warehouse']],
            ['code' => 'bedrooms_required', 'name' => 'Bedrooms Required', 'type' => 'text', 'sort_order' => 26, 'validation' => 'integer'],
        ] as $attribute) {
            $id = DB::table('attributes')->where('entity_type', 'leads')->where('code', $attribute['code'])->value('id');

            $data = [
                'name' => $attribute['name'],
                'type' => $attribute['type'],
                'lookup_type' => null,
                'entity_type' => 'leads',
                'sort_order' => $attribute['sort_order'],
                'validation' => $attribute['validation'] ?? null,
                'is_required' => false,
                'is_unique' => false,
                'quick_add' => false,
                'is_user_defined' => false,
                'updated_at' => $now,
            ];

            $id
                ? DB::table('attributes')->where('id', $id)->update($data)
                : $id = DB::table('attributes')->insertGetId([...$data, 'code' => $attribute['code'], 'created_at' => $now]);

            if (! isset($attribute['options'])) {
                continue;
            }

            DB::table('attribute_options')->where('attribute_id', $id)->delete();

            foreach ($attribute['options'] as $sortOrder => $option) {
                DB::table('attribute_options')->insert([
                    'attribute_id' => $id,
                    'name' => $option,
                    'sort_order' => $sortOrder + 1,
                ]);
            }
        }

        $this->createPipeline('Buyer / Renter', [
            ['New Lead', 'new', 0], ['Contacted', 'contacted', 10], ['Qualified', 'qualified', 20], ['Property Matching', 'property_matching', 30], ['Viewing Scheduled', 'viewing_scheduled', 45], ['Viewing Completed', 'viewing_completed', 60], ['Negotiation', 'negotiation', 75], ['Deal Won', 'won', 100], ['Deal Lost', 'lost', 0],
        ], $now);

        $this->createPipeline('Seller', [
            ['New Seller Lead', 'new', 0], ['Property Evaluation', 'property_evaluation', 20], ['Listing Agreement', 'listing_agreement', 40], ['Property Listed', 'property_listed', 60], ['Negotiation', 'negotiation', 80], ['Sold', 'won', 100],
        ], $now);
    }

    protected function createPipeline(string $name, array $stages, $now): void
    {
        $pipelineId = DB::table('lead_pipelines')->where('name', $name)->value('id');

        if (! $pipelineId) {
            $pipelineId = DB::table('lead_pipelines')->insertGetId([
                'name' => $name,
                'is_default' => ! DB::table('lead_pipelines')->where('is_default', true)->exists(),
                'rotten_days' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($stages as $sortOrder => [$stageName, $code, $probability]) {
            if (DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipelineId)->where('code', $code)->exists()) {
                continue;
            }

            DB::table('lead_pipeline_stages')->insert([
                'name' => $stageName,
                'code' => $code,
                'probability' => $probability,
                'sort_order' => $sortOrder + 1,
                'lead_pipeline_id' => $pipelineId,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('attributes')->where('entity_type', 'leads')->whereIn('code', [
            'client_intent', 'purchase_preference', 'budget_min', 'budget_max', 'preferred_location', 'preferred_property_type', 'bedrooms_required',
        ])->delete();

        DB::table('lead_pipelines')->whereIn('name', ['Buyer / Renter', 'Seller'])->delete();
    }
};
