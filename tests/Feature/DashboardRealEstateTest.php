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
                    'formatted_total_property_value',
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

it('returns the report data required by the visible dashboard cards', function () {
    $admin = getDefaultAdmin();

    foreach (['revenue-stats', 'over-all'] as $type) {
        $this->actingAs($admin)
            ->getJson(route('admin.dashboard.stats', ['type' => $type]))
            ->assertOk()
            ->assertJsonStructure([
                'statistics' => [
                    'property_overview' => [
                        'total',
                        'total_property_value',
                        'formatted_total_property_value',
                        'active_listings',
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
    }
});

it('returns the restored lead and property performance reports', function () {
    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'total-leads']))
        ->assertOk()
        ->assertJsonStructure([
            'statistics' => [
                'all' => ['over_time'],
                'won' => ['over_time'],
                'lost' => ['over_time'],
            ],
        ]);

    foreach (['open-leads-by-states', 'top-selling-products'] as $type) {
        $response = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.stats', ['type' => $type]))
            ->assertOk();

        expect($response->json('statistics'))->toBeArray();
    }
});

it('keeps operational work queues off the reporting dashboard', function () {
    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('Total property value')
        ->assertSee('New leads')
        ->assertSee('Lead outcome trend')
        ->assertSee('Pipeline distribution')
        ->assertSee('Lead sources')
        ->assertSee('Property mix')
        ->assertSee('Top property performance')
        ->assertDontSee('Priority outreach for the next warm follow-up.')
        ->assertDontSee("Today's Follow-ups")
        ->assertDontSee('Upcoming Viewings')
        ->assertDontSee('A polished teleprompter for every outreach call.');
});
