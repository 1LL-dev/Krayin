<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Repositories\LeadRepository;

class CallingQueueController extends Controller
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected ActivityRepository $activityRepository,
    ) {}

    /**
     * Display the logged-in agent's calling queue.
     */
    public function index(): View
    {
        $leads = $this->getAssignedLeads();

        return view('admin::leads.calling-queue', [
            'queue' => [
                'leads' => $leads->map(fn (Lead $lead) => $this->serializeLead($lead))->values(),
                'statistics' => $this->getStatistics($leads),
            ],
        ]);
    }

    /**
     * Save an outbound call using the existing Activity model and lead relation.
     */
    public function storeCall(): JsonResponse
    {
        $data = request()->validate([
            'lead_id' => ['required', 'integer'],
            'call_outcome' => ['required', 'in:No Answer,Busy,Left Voicemail,Interested,Not Interested,Callback Requested,Wrong Number,Do Not Call,Appointment Booked'],
            'comment' => ['nullable', 'string'],
            'call_duration' => ['nullable', 'integer', 'min:0'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $lead = $this->leadRepository->findOrFail($data['lead_id']);

        $userIds = bouncer()->getAuthorizedUserIds();

        if ($userIds && ! in_array($lead->user_id, $userIds)) {
            abort(403);
        }

        $userId = auth()->guard('user')->id();

        $activity = DB::transaction(function () use ($data, $lead, $userId) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);

            Event::dispatch('activity.create.before');

            $activity = $this->activityRepository->create([
                'title' => 'Outbound call: '.$lead->title,
                'type' => 'call',
                'comment' => $data['comment'] ?? null,
                'call_outcome' => $data['call_outcome'],
                'call_duration' => $data['call_duration'] ?? 0,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'is_done' => true,
                'user_id' => $userId,
            ]);

            $activity->leads()->syncWithoutDetaching([$lead->id]);

            if ($lead->person_id) {
                $activity->persons()->syncWithoutDetaching([$lead->person_id]);
            }

            Event::dispatch('activity.create.after', $activity);

            if (! empty($data['follow_up_at'])) {
                $scheduleFrom = Carbon::parse($data['follow_up_at'], config('app.timezone'))->startOfMinute();
                $title = 'Follow-up call: '.$lead->title;

                $followUpActivity = $lead->activities()
                    ->where('activities.title', $title)
                    ->where('activities.type', 'call')
                    ->where('activities.is_done', false)
                    ->where('activities.user_id', $userId)
                    ->where('activities.schedule_from', $scheduleFrom->toDateTimeString())
                    ->first();

                $followUpWasCreated = false;

                if (! $followUpActivity) {
                    Event::dispatch('activity.create.before');

                    $followUpActivity = $this->activityRepository->create([
                        'title' => $title,
                        'type' => 'call',
                        'schedule_from' => $scheduleFrom,
                        'schedule_to' => $scheduleFrom->copy()->addMinutes(30),
                        'is_done' => false,
                        'user_id' => $userId,
                    ]);

                    $followUpWasCreated = true;
                }

                $followUpActivity->leads()->syncWithoutDetaching([$lead->id]);

                if ($lead->person_id) {
                    $followUpActivity->persons()->syncWithoutDetaching([$lead->person_id]);
                }

                if ($followUpWasCreated) {
                    Event::dispatch('activity.create.after', $followUpActivity);
                }
            }

            return $activity;
        });

        return response()->json([
            'message' => trans('admin::app.calling-queue.call-saved'),
            'activity' => $this->serializeActivity($activity->fresh()),
        ]);
    }

    /**
     * Return leads assigned to the current agent or authorized users with only the data used in the workspace.
     */
    protected function getAssignedLeads()
    {
        $query = Lead::query()
            ->with([
                'person',
                'stage',
                'products.product',
                'activities' => fn ($query) => $query->whereIn('type', ['call', 'note', 'property_viewing', 'meeting', 'task', 'email'])->latest()->limit(12),
            ]);

        $userIds = bouncer()->getAuthorizedUserIds();

        if ($userIds) {
            $query->whereIn('user_id', $userIds);
        } else {
            $query->where('user_id', auth()->guard('user')->id());
        }

        return $query->latest('updated_at')
            ->limit(150)
            ->get();
    }

    /**
     * Build queue statistics from existing assigned leads and activities.
     */
    protected function getStatistics($leads): array
    {
        $today = Carbon::today();
        $calledLeadIds = $leads->filter(fn (Lead $lead) => $lead->activities->contains(fn ($activity) => $activity->type === 'call' && $activity->created_at?->isToday()))->pluck('id');

        return [
            'total_assigned' => $leads->count(),
            'remaining_today' => $leads->count() - $calledLeadIds->count(),
            'completed_today' => $calledLeadIds->count(),
            'callbacks_due' => $leads->filter(fn (Lead $lead) => $lead->activities->contains(fn ($activity) => $activity->call_outcome === 'Callback Requested' && $activity->follow_up_at && $activity->follow_up_at->lessThanOrEqualTo($today->copy()->endOfDay())))->count(),
        ];
    }

    /**
     * Serialize the existing lead, contact, requirement, match, and activity data for Vue.
     */
    protected function serializeLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'title' => $lead->title,
            'stage' => $lead->stage?->name,
            'stage_code' => $lead->stage?->code,
            'contact' => [
                'name' => $lead->person?->name,
                'phone' => collect($lead->person?->contact_numbers ?? [])->pluck('value')->first(),
                'email' => collect($lead->person?->emails ?? [])->pluck('value')->first(),
            ],
            'requirements' => [
                'intent' => $lead->client_intent,
                'preference' => $lead->purchase_preference,
                'budget_min' => $lead->budget_min,
                'budget_max' => $lead->budget_max,
                'location' => $lead->preferred_location,
                'property_type' => $lead->preferred_property_type,
                'bedrooms' => $lead->bedrooms_required,
            ],
            'properties' => $lead->products->map(fn ($match) => [
                'id' => $match->product_id,
                'name' => $match->name,
                'price' => $match->price,
                'match_status' => $match->match_status,
            ])->values(),
            'activities' => $lead->activities->map(fn ($activity) => $this->serializeActivity($activity))->values(),
        ];
    }

    /**
     * Serialize an existing Activity for the call history and timeline.
     */
    protected function serializeActivity($activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'type' => $activity->type,
            'comment' => $activity->comment,
            'call_outcome' => $activity->call_outcome,
            'call_duration' => $activity->call_duration,
            'follow_up_at' => optional($activity->follow_up_at)->toDateTimeString(),
            'created_at' => optional($activity->created_at)->toDateTimeString(),
        ];
    }
}
