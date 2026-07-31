<?php

it('returns workflow snapshot and property value metrics for the real-estate dashboard', function () {
    $admin = getDefaultAdmin();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'real-estate-operations']));

    $response->assertOk()
        ->assertJsonStructure([
            'statistics' => [
                'property_overview' => [
                    'total',
                    'total_property_value',
                    'active_listings',
                    'statuses',
                    'status_breakdown',
                ],
                'workflow_snapshot' => [
                    'new_leads_today',
                    'follow_ups_due',
                    'viewings_today',
                    'calls_pending',
                    'active_buyers',
                    'active_sellers',
                ],
            ],
        ]);
});

it('returns the next leads needing calls for the calling queue widget', function () {
    $admin = getDefaultAdmin();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'calling-queue']));

    $response->assertOk()
        ->assertJsonStructure([
            'statistics' => [
                '*' => [
                    'id',
                    'name',
                    'phone',
                    'source',
                    'status',
                    'next_action',
                ],
            ],
        ]);
});
