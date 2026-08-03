<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

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

        if ($type === 'top-agents') {
            expect(count($response->json('statistics')))->toBeLessThanOrEqual(3);
        }
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

it('returns only the three highest-performing agents', function () {
    $admin = getDefaultAdmin();
    $wonStage = DB::table('lead_pipeline_stages')->where('code', 'won')->first();
    $personId = DB::table('persons')->value('id');
    $sourceId = DB::table('lead_sources')->value('id');
    $typeId = DB::table('lead_types')->value('id');

    expect($wonStage)->not->toBeNull()
        ->and($personId)->not->toBeNull()
        ->and($sourceId)->not->toBeNull()
        ->and($typeId)->not->toBeNull();

    DB::table('leads')->update(['closed_at' => now()->subYear()]);

    foreach ([
        'Import Agent One' => 60000000,
        'Import Agent Two' => 70000000,
        'Import Agent Three' => 80000000,
        'Import Agent Four' => 90000000,
    ] as $name => $revenue) {
        $userId = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => str()->uuid().'@example.test',
            'password' => null,
            'status' => 1,
            'role_id' => $admin->role_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leads')->insert([
            'title' => $name.' closed deal',
            'lead_value' => $revenue,
            'status' => 1,
            'closed_at' => now()->subDay(),
            'user_id' => $userId,
            'person_id' => $personId,
            'lead_source_id' => $sourceId,
            'lead_type_id' => $typeId,
            'lead_pipeline_id' => $wonStage->lead_pipeline_id,
            'lead_pipeline_stage_id' => $wonStage->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $statistics = $this->actingAs($admin)
        ->getJson(route('admin.dashboard.stats', ['type' => 'top-agents']))
        ->assertOk()
        ->json('statistics');

    expect($statistics)->toHaveCount(3)
        ->and(collect($statistics)->pluck('name')->all())->toBe([
            'Import Agent Four',
            'Import Agent Three',
            'Import Agent Two',
        ]);
});
