<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Repositories\UserRepository;

uses(DatabaseTransactions::class);

function createCallingQueueLead($admin, ?int $personId = null)
{
    $pipeline = DB::table('lead_pipelines')->first();
    $stage = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipeline->id)->first();

    return app(LeadRepository::class)->create([
        'title' => 'Calling Queue Lead '.Str::uuid(),
        'entity_type' => 'leads',
        'user_id' => $admin->id,
        'person_id' => $personId,
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
    ]);
}

function createCallingQueueUser(array $permissions)
{
    $identifier = (string) Str::uuid();
    $role = app(RoleRepository::class)->create([
        'name' => 'Calling Queue Role '.$identifier,
        'description' => 'Calling Queue feature test role',
        'permission_type' => 'custom',
        'permissions' => $permissions,
    ]);

    return app(UserRepository::class)->create([
        'name' => 'Calling Queue User '.$identifier,
        'email' => $identifier.'@example.test',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'individual',
    ]);
}

it('can see the calling queue page when authenticated', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.leads.calling_queue.index'))
        ->assertOK()
        ->assertSee('Calling Queue');
});

it('allows a user with Calling Queue permission to open the queue', function () {
    $user = createCallingQueueUser(['leads', 'leads.calling_queue']);

    test()->actingAs($user)
        ->get(route('admin.leads.calling_queue.index'))
        ->assertOK()
        ->assertSee('Calling Queue');
});

it('denies a user without Calling Queue permission from opening the queue', function () {
    $user = createCallingQueueUser(['dashboard']);

    test()->actingAs($user)
        ->get(route('admin.leads.calling_queue.index'))
        ->assertUnauthorized();
});

it('denies a user without Calling Queue permission from posting a call', function () {
    $user = createCallingQueueUser(['dashboard']);

    test()->actingAs($user)
        ->postJson(route('admin.leads.calling_queue.calls.store'), [])
        ->assertUnauthorized();
});

it('can log an outbound call via calling queue API', function () {
    $admin = getDefaultAdmin();
    $lead = createCallingQueueLead($admin);

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), [
            'lead_id' => $lead->id,
            'call_outcome' => 'Interested',
            'comment' => 'Follow up next week',
            'call_duration' => 45,
        ])
        ->assertOK()
        ->assertJsonPath('message', 'Call activity saved successfully.')
        ->assertJsonPath('activity.title', 'Outbound call: '.$lead->title)
        ->assertJsonPath('activity.type', 'call')
        ->assertJsonPath('activity.comment', 'Follow up next week')
        ->assertJsonPath('activity.call_outcome', 'Interested')
        ->assertJsonPath('activity.call_duration', 45)
        ->assertJsonPath('activity.follow_up_at', null);

    $completedCall = Activity::findOrFail($response->json('activity.id'));

    expect($completedCall->title)->toBe('Outbound call: '.$lead->title)
        ->and($completedCall->type)->toBe('call')
        ->and($completedCall->comment)->toBe('Follow up next week')
        ->and($completedCall->call_outcome)->toBe('Interested')
        ->and($completedCall->call_duration)->toBe(45)
        ->and((bool) $completedCall->is_done)->toBeTrue()
        ->and($completedCall->user_id)->toBe($admin->id)
        ->and($completedCall->follow_up_at)->toBeNull()
        ->and($completedCall->schedule_from)->toBeNull()
        ->and($completedCall->schedule_to)->toBeNull()
        ->and($lead->activities()->where('activities.title', 'Follow-up call: '.$lead->title)->exists())->toBeFalse();

    test()->assertDatabaseHas('lead_activities', [
        'activity_id' => $completedCall->id,
        'lead_id' => $lead->id,
    ]);
});

it('creates a separate planned call when a follow-up date is supplied', function () {
    $admin = getDefaultAdmin();
    $person = app(PersonRepository::class)->create([
        'name' => 'Calling Queue Contact '.Str::uuid(),
        'emails' => [[
            'value' => Str::uuid().'@example.test',
            'label' => 'work',
        ]],
        'contact_numbers' => [[
            'value' => '555'.random_int(1000000, 9999999),
            'label' => 'work',
        ]],
        'entity_type' => 'persons',
        'user_id' => $admin->id,
    ]);
    $lead = createCallingQueueLead($admin, $person->id);
    $followUpAt = '2026-08-18 10:15:47';
    $expectedStart = Carbon::parse($followUpAt, config('app.timezone'))->startOfMinute();

    $response = test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), [
            'lead_id' => $lead->id,
            'call_outcome' => 'Callback Requested',
            'comment' => 'Requested a scheduled callback',
            'call_duration' => 90,
            'follow_up_at' => $followUpAt,
        ])
        ->assertOK()
        ->assertJsonPath('activity.title', 'Outbound call: '.$lead->title)
        ->assertJsonPath('activity.type', 'call')
        ->assertJsonPath('activity.follow_up_at', $followUpAt);

    $completedCall = Activity::findOrFail($response->json('activity.id'));
    $plannedCall = $lead->activities()
        ->where('activities.title', 'Follow-up call: '.$lead->title)
        ->where('activities.type', 'call')
        ->where('activities.is_done', false)
        ->firstOrFail();

    expect($completedCall->title)->toBe('Outbound call: '.$lead->title)
        ->and($completedCall->type)->toBe('call')
        ->and($completedCall->comment)->toBe('Requested a scheduled callback')
        ->and($completedCall->call_outcome)->toBe('Callback Requested')
        ->and($completedCall->call_duration)->toBe(90)
        ->and((bool) $completedCall->is_done)->toBeTrue()
        ->and($completedCall->user_id)->toBe($admin->id)
        ->and($completedCall->follow_up_at?->toDateTimeString())->toBe($followUpAt)
        ->and($completedCall->schedule_from)->toBeNull()
        ->and($completedCall->schedule_to)->toBeNull()
        ->and($plannedCall->title)->toBe('Follow-up call: '.$lead->title)
        ->and($plannedCall->type)->toBe('call')
        ->and((bool) $plannedCall->is_done)->toBeFalse()
        ->and($plannedCall->schedule_from?->toDateTimeString())->toBe($expectedStart->toDateTimeString())
        ->and($plannedCall->schedule_to?->toDateTimeString())->toBe($expectedStart->copy()->addMinutes(30)->toDateTimeString())
        ->and($plannedCall->user_id)->toBe($admin->id);

    test()->assertDatabaseHas('lead_activities', [
        'activity_id' => $completedCall->id,
        'lead_id' => $lead->id,
    ])->assertDatabaseHas('person_activities', [
        'activity_id' => $completedCall->id,
        'person_id' => $person->id,
    ])->assertDatabaseHas('lead_activities', [
        'activity_id' => $plannedCall->id,
        'lead_id' => $lead->id,
    ])->assertDatabaseHas('person_activities', [
        'activity_id' => $plannedCall->id,
        'person_id' => $person->id,
    ]);

    expect($lead->activities()->where('activities.type', 'meeting')->exists())->toBeFalse();

    $calendarActivities = app(ActivityRepository::class)->getActivities([
        $expectedStart->copy()->startOfDay(),
        $expectedStart->copy()->endOfDay(),
    ]);

    expect($calendarActivities->pluck('id')->all())->toContain($plannedCall->id);
});

it('does not duplicate an identical planned follow-up call', function () {
    $admin = getDefaultAdmin();
    $lead = createCallingQueueLead($admin);
    $followUpAt = '2026-08-19 14:45:39';
    $expectedStart = Carbon::parse($followUpAt, config('app.timezone'))->startOfMinute();
    $payload = [
        'lead_id' => $lead->id,
        'call_outcome' => 'Callback Requested',
        'comment' => 'Call again at the requested time',
        'call_duration' => 60,
        'follow_up_at' => $followUpAt,
    ];

    test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), $payload)
        ->assertOK();

    test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), $payload)
        ->assertOK();

    $plannedCalls = $lead->activities()
        ->where('activities.title', 'Follow-up call: '.$lead->title)
        ->where('activities.type', 'call')
        ->where('activities.is_done', false)
        ->where('activities.user_id', $admin->id)
        ->where('activities.schedule_from', $expectedStart->toDateTimeString())
        ->get();

    expect($plannedCalls)->toHaveCount(1)
        ->and($lead->activities()->where('activities.type', 'meeting')->exists())->toBeFalse();
});

it('returns validation errors for an invalid call payload', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->postJson(route('admin.leads.calling_queue.calls.store'), [
            'lead_id' => 'invalid',
            'call_outcome' => 'invalid',
            'call_duration' => -1,
            'follow_up_at' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'lead_id',
            'call_outcome',
            'call_duration',
            'follow_up_at',
        ]);
});
