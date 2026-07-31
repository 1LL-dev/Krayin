{!! view_render_event('admin.dashboard.index.real_estate_operations.before') !!}

<v-dashboard-real-estate-operations>
    <div class="light-shimmer-bg dark:shimmer h-96 rounded-lg"></div>
</v-dashboard-real-estate-operations>

{!! view_render_event('admin.dashboard.index.real_estate_operations.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-real-estate-operations-template">
        <template v-if="isLoading">
            <div class="light-shimmer-bg dark:shimmer h-96 rounded-lg"></div>
        </template>

        <template v-else>
            <div class="grid gap-4">
                <section class="rounded-xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 p-5 text-white shadow-lg">
                    <div class="mb-4 flex items-center justify-between"><div><p class="text-lg font-semibold">@lang('admin::app.dashboard.index.real-estate.today-at-a-glance')</p><p class="text-sm text-blue-100">@lang('admin::app.dashboard.index.real-estate.today-at-a-glance-info')</p></div><i class="icon-dashboard text-3xl text-blue-200"></i></div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                        <div class="rounded-lg bg-white/10 p-3"><p class="text-2xl font-semibold">@{{ report.property_overview.total }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.total-properties')</p></div>
                        <div class="rounded-lg bg-emerald-400/15 p-3"><p class="text-2xl font-semibold">@{{ report.property_overview.statuses.available || 0 }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.available')</p></div>
                        <div class="rounded-lg bg-amber-400/15 p-3"><p class="text-2xl font-semibold">@{{ (report.property_overview.statuses.sold || 0) + (report.property_overview.statuses.rented || 0) }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.sold-rented')</p></div>
                        <div class="rounded-lg bg-white/10 p-3"><p class="text-2xl font-semibold">@{{ report.lead_overview.total }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.active-leads')</p></div>
                        <div class="rounded-lg bg-violet-400/15 p-3"><p class="text-2xl font-semibold">@{{ report.activity_tracking.follow_ups.length }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.today-follow-ups')</p></div>
                        <div class="rounded-lg bg-sky-400/15 p-3"><p class="text-2xl font-semibold">@{{ report.activity_tracking.viewings.length }}</p><p class="text-xs text-blue-100">@lang('admin::app.dashboard.index.real-estate.upcoming-viewings')</p></div>
                    </div>
                </section>

                <div class="grid gap-4 xl:grid-cols-3">
                    <section class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.property-overview')</p>
                        <p class="mt-2 text-3xl font-semibold text-primary-600">@{{ report.property_overview.total }}</p>
                        <p class="text-sm text-gray-500">@lang('admin::app.dashboard.index.real-estate.total-properties')</p>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <template v-for="status in propertyStatuses" :key="status.key">
                                <div class="rounded bg-gray-50 p-2 dark:bg-gray-950">
                                    <p class="text-gray-500">@{{ status.label }}</p>
                                    <p class="font-semibold text-gray-800 dark:text-white">@{{ report.property_overview.statuses[status.key] || 0 }}</p>
                                </div>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.lead-overview')</p>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.lead_overview.total }}</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.total-leads')</p></div>
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.lead_overview.new }}</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.new-leads')</p></div>
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.lead_overview.conversion_rate }}%</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.conversion-rate')</p></div>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <div v-for="stage in report.lead_overview.stages" :key="stage.name" class="flex justify-between"><span class="text-gray-500">@{{ stage.name }}</span><span class="font-medium text-gray-800 dark:text-white">@{{ stage.total }}</span></div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.activity-tracking')</p>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.activity_tracking.calls_today }}</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.calls-today')</p></div>
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.activity_tracking.follow_ups.length }}</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.upcoming-follow-ups')</p></div>
                            <div><p class="text-2xl font-semibold text-gray-800 dark:text-white">@{{ report.activity_tracking.viewings.length }}</p><p class="text-xs text-gray-500">@lang('admin::app.dashboard.index.real-estate.upcoming-viewings')</p></div>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <div v-for="activity in report.activity_tracking.recent" :key="activity.id" class="flex justify-between gap-3"><span class="truncate text-gray-800 dark:text-white">@{{ activity.title || activity.type }}</span><span class="whitespace-nowrap text-gray-500">@{{ activity.agent_name || '—' }}</span></div>
                        </div>
                    </section>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <section class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.agent-performance')</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-left text-sm"><thead class="text-gray-500"><tr><th class="pb-2">@lang('admin::app.dashboard.index.real-estate.agent')</th><th class="pb-2 text-right">@lang('admin::app.dashboard.index.real-estate.properties')</th><th class="pb-2 text-right">@lang('admin::app.dashboard.index.real-estate.leads')</th><th class="pb-2 text-right">@lang('admin::app.dashboard.index.real-estate.activities')</th></tr></thead>
                            <tbody><tr v-for="agent in report.agent_performance" :key="agent.id" class="border-t dark:border-gray-800"><td class="py-2 font-medium text-gray-800 dark:text-white">@{{ agent.name }}</td><td class="py-2 text-right">@{{ agent.properties }}</td><td class="py-2 text-right">@{{ agent.leads }}</td><td class="py-2 text-right">@{{ agent.activities }}</td></tr></tbody></table>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.property-insights')</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><p class="mb-2 text-sm font-medium text-gray-500">@lang('admin::app.dashboard.index.real-estate.recently-added')</p><a v-for="property in report.property_insights.recently_added" :key="property.id" :href="propertyUrl(property.id)" class="mb-2 block text-sm text-primary-600 hover:underline">@{{ property.name }}</a></div>
                            <div><p class="mb-2 text-sm font-medium text-gray-500">@lang('admin::app.dashboard.index.real-estate.most-interested')</p><a v-for="property in report.property_insights.most_interested" :key="property.id" :href="propertyUrl(property.id)" class="mb-2 flex justify-between text-sm text-primary-600 hover:underline"><span>@{{ property.name }}</span><span>@{{ property.interested_leads }}</span></a></div>
                        </div>
                        <div class="mt-4"><p class="mb-2 text-sm font-medium text-gray-500">@lang('admin::app.dashboard.index.real-estate.recently-updated')</p><a v-for="property in report.property_insights.recently_updated" :key="property.id" :href="propertyUrl(property.id)" class="mb-2 block text-sm text-primary-600 hover:underline">@{{ property.name }}</a></div>
                    </section>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-real-estate-operations', {
            template: '#v-dashboard-real-estate-operations-template',

            data() {
                return {
                    isLoading: true,
                    report: {property_overview: {statuses: {}}, lead_overview: {stages: []}, activity_tracking: {follow_ups: [], viewings: [], recent: []}, agent_performance: [], property_insights: {recently_added: [], recently_updated: [], most_interested: []}},
                    propertyStatuses: [
                        {key: 'available', label: "@lang('admin::app.dashboard.index.real-estate.available')"},
                        {key: 'reserved', label: "@lang('admin::app.dashboard.index.real-estate.reserved')"},
                        {key: 'sold', label: "@lang('admin::app.dashboard.index.real-estate.sold')"},
                        {key: 'rented', label: "@lang('admin::app.dashboard.index.real-estate.rented')"},
                        {key: 'off-market', label: "@lang('admin::app.dashboard.index.real-estate.off-market')"},
                        {key: 'draft', label: "@lang('admin::app.dashboard.index.real-estate.draft')"},
                    ],
                }
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;
                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {params: Object.assign({}, filters, {type: 'real-estate-operations'})})
                        .then(response => { this.report = response.data.statistics; })
                        .finally(() => { this.isLoading = false; });
                },

                propertyUrl(id) {
                    return '{{ route('admin.products.view', ':id') }}'.replace(':id', id);
                },
            },
        });
    </script>
@endPushOnce
