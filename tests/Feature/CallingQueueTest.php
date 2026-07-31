<?php

use Illuminate\Support\Facades\DB;
use Webkul\Lead\Repositories\LeadRepository;

it('can see the calling queue page when authenticated', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.leads.calling_queue.index'))
        ->assertOK()
        ->assertSee('Calling Queue');
});

it('can log an outbound call via calling queue API', function () {
    $admin = getDefaultAdmin();

    $pipeline = DB::table('lead_pipelines')->first();
    $stage = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipeline->id)->first();

    $lead = app(LeadRepository::class)->create([
        'title' => 'Test Real Estate Buyer',
        'entity_type' => 'leads',
        'user_id' => $admin->id,
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
    ]);

    test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), [
            'lead_id' => $lead->id,
            'call_outcome' => 'Interested',
            'comment' => 'Follow up next week',
            'call_duration' => 45,
        ])
        ->assertOK()
        ->assertJsonPath('message', 'Call activity saved successfully.');
});
