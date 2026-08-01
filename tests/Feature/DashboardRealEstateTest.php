<?php

it('returns the native Krayin revenue and summary reports', function () {
    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'revenue-stats']))
        ->assertOk()
        ->assertJsonStructure([
            'statistics' => [
                'total_won_revenue' => ['previous', 'current', 'formatted_total', 'progress'],
                'total_lost_revenue' => ['previous', 'current', 'formatted_total', 'progress'],
            ],
            'date_range',
        ]);

    $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'over-all']))
        ->assertOk()
        ->assertJsonStructure([
            'statistics' => [
                'total_leads' => ['previous', 'current', 'progress'],
                'average_lead_value' => ['previous', 'current', 'formatted_total', 'progress'],
                'average_leads_per_day' => ['previous', 'current', 'progress'],
                'total_quotations' => ['previous', 'current', 'progress'],
                'total_persons' => ['previous', 'current', 'progress'],
                'total_organizations' => ['previous', 'current', 'progress'],
            ],
            'date_range',
        ]);
});

it('returns the dashboard chart and ranking reports', function () {
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

    foreach ([
        'open-leads-by-states',
        'revenue-by-sources',
        'revenue-by-types',
        'top-selling-products',
        'top-persons',
        'top-agents',
    ] as $type) {
        $response = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.stats', ['type' => $type]))
            ->assertOk();

        expect($response->json('statistics'))->toBeArray();
    }
});

it('renders the native report dashboard with top agents and no work queues', function () {
    $admin = getDefaultAdmin();

    $this->actingAs($admin)
        ->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('Won Revenue')
        ->assertSee('Average Lead Value')
        ->assertSee('Total Quotations')
        ->assertSee('Open Leads By Stages')
        ->assertSee('Revenue By Sources')
        ->assertSee('Revenue By Types')
        ->assertSee('Top Properties')
        ->assertSee('Top Agents')
        ->assertDontSee('Priority outreach for the next warm follow-up.')
        ->assertDontSee("Today's Follow-ups")
        ->assertDontSee('Upcoming Viewings')
        ->assertDontSee('A polished teleprompter for every outreach call.');
});
