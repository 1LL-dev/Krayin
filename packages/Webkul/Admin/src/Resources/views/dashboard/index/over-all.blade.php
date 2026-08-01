{!! view_render_event('admin.dashboard.index.over_all.before') !!}

<v-dashboard-over-all-stats>
    <x-admin::shimmer.dashboard.index.over-all />
</v-dashboard-over-all-stats>

{!! view_render_event('admin.dashboard.index.over_all.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-over-all-stats-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.over-all />
        </template>

        <template v-else>
            <div class="grid grid-cols-3 gap-4 max-lg:grid-cols-2 max-sm:grid-cols-1">
                <div
                    class="rounded-lg border border-gray-300 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900"
                    v-for="metric in metrics"
                    :key="metric.label"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">@{{ metric.label }}</p>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            @{{ metric.context }}
                        </span>
                    </div>

                    <p class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">@{{ metric.value }}</p>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-over-all-stats', {
            template: '#v-dashboard-over-all-stats-template',

            data() {
                return {
                    report: {
                        statistics: {
                            workflow_snapshot: {
                                new_leads_today: 0,
                                follow_ups_due: 0,
                                viewings_today: 0,
                                calls_pending: 0,
                                active_buyers: 0,
                                active_sellers: 0,
                            },
                        },
                    },
                    isLoading: true,
                }
            },

            computed: {
                metrics() {
                    const snapshot = this.report.statistics.workflow_snapshot;

                    return [
                        {label: 'New leads', value: snapshot.new_leads_today, context: 'Today'},
                        {label: 'Follow-ups due', value: snapshot.follow_ups_due, context: 'Next 24h'},
                        {label: 'Viewings', value: snapshot.viewings_today, context: 'Today'},
                        {label: 'Pending calls', value: snapshot.calls_pending, context: 'Open'},
                        {label: 'Active buyers', value: snapshot.active_buyers, context: 'Pipeline'},
                        {label: 'Total contacts', value: snapshot.active_sellers, context: 'Database'},
                    ];
                },
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                        params: Object.assign({}, filters, {type: 'over-all'}),
                    }).then(response => {
                        this.report = response.data;
                    }).finally(() => {
                        this.isLoading = false;
                    });
                },
            },
        });
    </script>
@endPushOnce
