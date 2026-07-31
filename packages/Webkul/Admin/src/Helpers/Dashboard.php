<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Admin\Helpers\Reporting\Activity;
use Webkul\Admin\Helpers\Reporting\Lead;
use Webkul\Admin\Helpers\Reporting\Organization;
use Webkul\Admin\Helpers\Reporting\Person;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Quote;

class Dashboard
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Lead $leadReporting,
        protected Activity $activityReporting,
        protected Product $productReporting,
        protected Person $personReporting,
        protected Organization $organizationReporting,
        protected Quote $quoteReporting,
    ) {}

    /**
     * Returns the overall revenue statistics.
     */
    public function getRevenueStats(): array
    {
        return [
            'total_won_revenue' => $this->leadReporting->getTotalWonLeadValueProgress(),
            'total_lost_revenue' => $this->leadReporting->getTotalLostLeadValueProgress(),
        ];
    }

    /**
     * Returns the overall statistics.
     */
    public function getOverAllStats(): array
    {
        return [
            'total_leads' => $this->leadReporting->getTotalLeadsProgress(),
            'average_lead_value' => $this->leadReporting->getAverageLeadValueProgress(),
            'average_leads_per_day' => $this->leadReporting->getAverageLeadsPerDayProgress(),
            'total_quotations' => $this->quoteReporting->getTotalQuotesProgress(),
            'total_persons' => $this->personReporting->getTotalPersonsProgress(),
            'total_organizations' => $this->organizationReporting->getTotalOrganizationsProgress(),
        ];
    }

    /**
     * Returns leads statistics.
     */
    public function getTotalLeadsStats(): array
    {
        return [
            'all' => [
                'over_time' => $this->leadReporting->getTotalLeadsOverTime(),
            ],

            'won' => [
                'over_time' => $this->leadReporting->getTotalWonLeadsOverTime(),
            ],
            'lost' => [
                'over_time' => $this->leadReporting->getTotalLostLeadsOverTime(),
            ],
        ];
    }

    /**
     * Returns leads revenue statistics by sources.
     */
    public function getLeadsStatsBySources(): mixed
    {
        return $this->leadReporting->getTotalWonLeadValueBySources();
    }

    /**
     * Returns leads revenue statistics by types.
     */
    public function getLeadsStatsByTypes(): mixed
    {
        return $this->leadReporting->getTotalWonLeadValueByTypes();
    }

    /**
     * Returns open leads statistics by states.
     */
    public function getOpenLeadsByStates(): mixed
    {
        return $this->leadReporting->getOpenLeadsByStates();
    }

    /**
     * Returns top selling products statistics.
     */
    public function getTopSellingProducts(): Collection
    {
        return $this->productReporting->getTopSellingProductsByRevenue(5);
    }

    /**
     * Returns top selling products statistics.
     */
    public function getTopPersons(): Collection
    {
        return $this->personReporting->getTopCustomersByRevenue(5);
    }

    /**
     * Return the operational real-estate dashboard data.
     */
    public function getRealEstateOperations(): array
    {
        $propertyStatuses = collect([
            'draft' => 0,
            'available' => 0,
            'reserved' => 0,
            'sold' => 0,
            'rented' => 0,
            'off-market' => 0,
        ]);

        $statusAttributeId = DB::table('attributes')
            ->where('entity_type', 'products')
            ->where('code', 'property_status')
            ->value('id');

        if ($statusAttributeId) {
            $propertyStatuses = $propertyStatuses->merge(
                DB::table('attribute_values')
                    ->join('attribute_options', 'attribute_options.id', '=', 'attribute_values.integer_value')
                    ->where('attribute_values.entity_type', 'products')
                    ->where('attribute_values.attribute_id', $statusAttributeId)
                    ->select('attribute_options.name', DB::raw('COUNT(*) as total'))
                    ->groupBy('attribute_options.name')
                    ->pluck('total', 'name')
                    ->mapWithKeys(fn ($total, $name) => [Str::slug($name) => $total])
            );
        }

        $leadStages = DB::table('leads')
            ->leftJoin('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->select('lead_pipeline_stages.name', DB::raw('COUNT(leads.id) as total'))
            ->groupBy('lead_pipeline_stages.id', 'lead_pipeline_stages.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($stage) => [
                'name' => $stage->name ?: trans('admin::app.dashboard.index.real-estate.unassigned'),
                'total' => (int) $stage->total,
            ]);

        $totalLeads = DB::table('leads')->count();
        $wonLeads = DB::table('leads')
            ->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->where('lead_pipeline_stages.code', 'won')
            ->count();

        return [
            'property_overview' => [
                'total' => DB::table('products')->count(),
                'total_property_value' => DB::table('products')->sum('price') ?? 0,
                'active_listings' => DB::table('products')->where('price', '>', 0)->count(),
                'statuses' => $propertyStatuses,
                'status_breakdown' => $propertyStatuses->map(fn ($total, $key) => ['key' => $key, 'value' => (int) $total])->values(),
            ],
            'workflow_snapshot' => [
                'new_leads_today' => DB::table('leads')
                    ->whereDate('created_at', now()->toDateString())
                    ->count(),
                'follow_ups_due' => DB::table('activities')
                    ->whereNotNull('follow_up_at')
                    ->where('follow_up_at', '<=', now()->addDays(1))
                    ->where('follow_up_at', '>=', now())
                    ->count(),
                'viewings_today' => DB::table('activities')
                    ->where('type', 'property_viewing')
                    ->whereNotNull('schedule_from')
                    ->whereDate('schedule_from', now()->toDateString())
                    ->count(),
                'calls_pending' => DB::table('activities')
                    ->where('type', 'call')
                    ->where('is_done', false)
                    ->count(),
                'active_buyers' => DB::table('leads')
                    ->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')
                    ->whereNotIn('lead_pipeline_stages.code', ['won', 'lost'])
                    ->count(),
                'active_sellers' => DB::table('persons')
                    ->count(),
            ],
            'lead_overview' => [
                'total' => $totalLeads,
                'new' => DB::table('leads')
                    ->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')
                    ->where('lead_pipeline_stages.code', 'new')
                    ->count(),
                'conversion_rate' => $totalLeads ? round(($wonLeads / $totalLeads) * 100, 1) : 0,
                'stages' => $leadStages,
            ],
            'agent_performance' => DB::table('users')
                ->leftJoin('products', 'products.assigned_agent_id', '=', 'users.id')
                ->leftJoin('leads', 'leads.user_id', '=', 'users.id')
                ->leftJoin('activities', 'activities.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(DISTINCT products.id) as properties'),
                    DB::raw('COUNT(DISTINCT leads.id) as leads'),
                    DB::raw('COUNT(DISTINCT CASE WHEN activities.is_done = 1 THEN activities.id END) as activities')
                )
                ->where(function ($query) {
                    $query->whereNotNull('products.id')
                        ->orWhereNotNull('leads.id')
                        ->orWhereNotNull('activities.id');
                })
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('activities')
                ->orderByDesc('leads')
                ->limit(10)
                ->get(),
            'activity_tracking' => [
                'calls_today' => DB::table('activities')
                    ->where('type', 'call')
                    ->where('is_done', true)
                    ->whereDate('updated_at', now()->toDateString())
                    ->count(),
                'follow_ups' => $this->getUpcomingActivities('follow_up_at'),
                'viewings' => $this->getUpcomingActivities('schedule_from', 'property_viewing'),
                'recent' => $this->getRecentActivities(),
            ],
            'property_insights' => [
                'recently_added' => $this->getRecentProperties('created_at'),
                'recently_updated' => $this->getRecentProperties('updated_at'),
                'most_interested' => DB::table('products')
                    ->leftJoin('lead_products', 'lead_products.product_id', '=', 'products.id')
                    ->select('products.id', 'products.name', DB::raw('COUNT(lead_products.id) as interested_leads'))
                    ->groupBy('products.id', 'products.name')
                    ->having('interested_leads', '>', 0)
                    ->orderByDesc('interested_leads')
                    ->limit(5)
                    ->get(),
            ],
        ];
    }

    /**
     * Return the next leads that need a call for the dashboard queue widget.
     */
    public function getCallingQueue(): array
    {
        return DB::table('leads')
            ->leftJoin('persons', 'persons.id', '=', 'leads.person_id')
            ->leftJoin('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->select(
                'leads.id',
                DB::raw("COALESCE(persons.name, leads.title) as name"),
                DB::raw("CASE WHEN persons.contact_numbers IS NOT NULL AND JSON_LENGTH(persons.contact_numbers) > 0 THEN JSON_UNQUOTE(JSON_EXTRACT(persons.contact_numbers, '$[0]')) ELSE '—' END as phone"),
                DB::raw("CASE WHEN leads.lead_source_id IS NOT NULL THEN 'Inbound' ELSE 'Website' END as source"),
                DB::raw("COALESCE(lead_pipeline_stages.name, 'New') as status"),
                DB::raw("CASE WHEN lead_pipeline_stages.code = 'new' THEN 'Intro call' WHEN lead_pipeline_stages.code = 'follow_up' THEN 'Follow-up call' ELSE 'Confirm next step' END as next_action")
            )
            ->orderByDesc('leads.created_at')
            ->limit(5)
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name ?: 'Unassigned lead',
                'phone' => $lead->phone ?: '—',
                'source' => $lead->source ?: 'Website',
                'status' => $lead->status ?: 'New',
                'next_action' => $lead->next_action ?: 'Confirm next step',
            ])
            ->values()
            ->all();
    }

    /**
     * Return the signed-in agent's daily real-estate workload.
     */
    public function getMyRealEstateWork(): array
    {
        $userId = auth()->guard('user')->id();

        if (! $userId) {
            return ['leads' => 0, 'properties' => 0, 'follow_ups' => collect(), 'viewings' => collect()];
        }

        return [
            'leads' => DB::table('leads')->where('user_id', $userId)->count(),
            'properties' => DB::table('products')->where('assigned_agent_id', $userId)->count(),
            'follow_ups' => DB::table('activities')
                ->where('user_id', $userId)
                ->whereNotNull('follow_up_at')
                ->where('follow_up_at', '>=', now())
                ->orderBy('follow_up_at')
                ->limit(5)
                ->get(['id', 'title', 'follow_up_at']),
            'viewings' => DB::table('activities')
                ->where('user_id', $userId)
                ->where('type', 'property_viewing')
                ->whereNotNull('schedule_from')
                ->where('schedule_from', '>=', now())
                ->orderBy('schedule_from')
                ->limit(5)
                ->get(['id', 'title', 'schedule_from']),
        ];
    }

    /**
     * Return upcoming activities using an existing activity date field.
     */
    protected function getUpcomingActivities(string $dateColumn, ?string $type = null): Collection
    {
        return DB::table('activities')
            ->leftJoin('users', 'users.id', '=', 'activities.user_id')
            ->select('activities.id', 'activities.title', 'activities.type', 'activities.'.$dateColumn.' as scheduled_at', 'users.name as agent_name')
            ->whereNotNull('activities.'.$dateColumn)
            ->where('activities.'.$dateColumn, '>=', now())
            ->when($type, fn ($query) => $query->where('activities.type', $type))
            ->orderBy('activities.'.$dateColumn)
            ->limit(5)
            ->get();
    }

    /**
     * Return the latest communication records.
     */
    protected function getRecentActivities(): Collection
    {
        return DB::table('activities')
            ->leftJoin('users', 'users.id', '=', 'activities.user_id')
            ->select('activities.id', 'activities.title', 'activities.type', 'activities.is_done', 'activities.created_at', 'users.name as agent_name')
            ->orderByDesc('activities.created_at')
            ->limit(8)
            ->get();
    }

    /**
     * Return recently created or updated properties.
     */
    protected function getRecentProperties(string $dateColumn): Collection
    {
        return DB::table('products')
            ->leftJoin('users as assigned_agents', 'assigned_agents.id', '=', 'products.assigned_agent_id')
            ->select('products.id', 'products.name', 'products.price', 'products.'.$dateColumn.' as changed_at', 'assigned_agents.name as agent_name')
            ->orderByDesc('products.'.$dateColumn)
            ->limit(5)
            ->get();
    }

    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->leadReporting->getStartDate();
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->leadReporting->getEndDate();
    }

    /**
     * Returns date range
     */
    public function getDateRange(): string
    {
        return $this->getStartDate()->format('d M').' - '.$this->getEndDate()->format('d M');
    }
}
