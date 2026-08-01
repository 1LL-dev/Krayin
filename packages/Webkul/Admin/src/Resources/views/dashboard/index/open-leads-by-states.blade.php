{!! view_render_event('admin.dashboard.index.open_leads_by_states.before') !!}

<v-dashboard-open-leads-by-states>
    <x-admin::shimmer.dashboard.index.open-leads-by-states />
</v-dashboard-open-leads-by-states>

{!! view_render_event('admin.dashboard.index.open_leads_by_states.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-open-leads--by-states-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.open-leads-by-states />
        </template>

        <template v-else>
            <div class="h-full min-h-[360px] rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">Pipeline distribution</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Open leads by current stage.</p>
                    </div>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        @{{ openTotal }} open
                    </span>
                </div>

                <div v-if="report.statistics.length" class="mt-5 space-y-4">
                    <div v-for="stat in report.statistics" :key="stat.name">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <span class="truncate text-gray-600 dark:text-gray-300">@{{ stat.name || 'Unassigned' }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white">@{{ stat.total }}</span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-brandColor" :style="{width: barWidth(stat.total)}"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-5 rounded-md border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    No open lead stages in this period.
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-open-leads-by-states', {
            template: '#v-dashboard-open-leads--by-states-template',

            data() {
                return {
                    report: {statistics: []},
                    isLoading: true,
                }
            },

            computed: {
                openTotal() {
                    return this.report.statistics.reduce((total, stat) => total + Number(stat.total || 0), 0);
                },

                maxTotal() {
                    return Math.max(...this.report.statistics.map(stat => Number(stat.total || 0)), 1);
                },
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                barWidth(value) {
                    return `${Math.max((Number(value || 0) / this.maxTotal) * 100, 4)}%`;
                },

                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                        params: Object.assign({}, filters, {type: 'open-leads-by-states'}),
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
