{!! view_render_event('admin.dashboard.index.total_leads.before') !!}

<v-dashboard-total-leads>
    <x-admin::shimmer.dashboard.index.total-leads />
</v-dashboard-total-leads>

{!! view_render_event('admin.dashboard.index.total_leads.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-total-leads-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.total-leads />
        </template>

        <template v-else>
            <div class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">Lead outcome trend</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Created, won, and lost leads over time.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-[#8979FF]"></i>Total</span>
                        <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-[#63CFE5]"></i>Won</span>
                        <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-[#FFA8A1]"></i>Lost</span>
                    </div>
                </div>

                <div class="mt-5 min-h-[260px]">
                    <x-admin::charts.bar
                        ::labels="chartLabels"
                        ::datasets="chartDatasets"
                    />
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-leads', {
            template: '#v-dashboard-total-leads-template',

            data() {
                return {
                    report: {
                        statistics: {
                            all: {over_time: []},
                            won: {over_time: []},
                            lost: {over_time: []},
                        },
                    },
                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics.all.over_time.map(({label}) => label);
                },

                chartDatasets() {
                    return [
                        {
                            data: this.report.statistics.all.over_time.map(({count}) => count),
                            barThickness: 18,
                            backgroundColor: '#8979FF',
                        },
                        {
                            data: this.report.statistics.won.over_time.map(({count}) => count),
                            barThickness: 18,
                            backgroundColor: '#63CFE5',
                        },
                        {
                            data: this.report.statistics.lost.over_time.map(({count}) => count),
                            barThickness: 18,
                            backgroundColor: '#FFA8A1',
                        },
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
                        params: Object.assign({}, filters, {type: 'total-leads'}),
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
