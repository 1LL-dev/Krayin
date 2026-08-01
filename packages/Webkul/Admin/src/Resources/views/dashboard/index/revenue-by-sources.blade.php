{!! view_render_event('admin.dashboard.index.revenue_by_sources.before') !!}

<v-dashboard-revenue-by-sources>
    <x-admin::shimmer.dashboard.index.revenue-by-sources />
</v-dashboard-revenue-by-sources>

{!! view_render_event('admin.dashboard.index.revenue_by_sources.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-revenue-by-sources-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue-by-sources />
        </template>

        <template v-else>
            <div class="flex h-full min-h-[360px] flex-col rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">Lead sources</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Won value by acquisition channel.</p>
                </div>

                <div v-if="report.statistics.length" class="flex flex-1 flex-col justify-center gap-5 px-4 pt-5">
                    <x-admin::charts.doughnut
                        ::labels="chartLabels"
                        ::datasets="chartDatasets"
                    />

                    <div class="flex flex-wrap justify-center gap-x-4 gap-y-2">
                        <div
                            class="flex items-center gap-2 whitespace-nowrap"
                            v-for="(stat, index) in report.statistics"
                            :key="stat.name"
                        >
                            <span class="h-2.5 w-2.5 rounded-sm" :style="{backgroundColor: colorFor(index)}"></span>
                            <p class="text-xs text-gray-600 dark:text-gray-300">@{{ stat.name || 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-5 flex flex-1 items-center justify-center rounded-md border border-dashed border-gray-200 px-4 py-8 text-center dark:border-gray-800">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No source revenue yet</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Won leads will build this report.</p>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-revenue-by-sources', {
            template: '#v-dashboard-revenue-by-sources-template',

            data() {
                return {
                    report: {statistics: []},
                    colors: ['#8979FF', '#FF928A', '#3CC3DF', '#F4B860', '#57B894', '#A88BEB', '#E47D9B', '#6C8AE4'],
                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics.map(({name}) => name || 'Unassigned');
                },

                chartDatasets() {
                    return [{
                        data: this.report.statistics.map(({total}) => total),
                        backgroundColor: this.report.statistics.map((stat, index) => this.colorFor(index)),
                    }];
                },
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                colorFor(index) {
                    return this.colors[index % this.colors.length];
                },

                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                        params: Object.assign({}, filters, {type: 'revenue-by-sources'}),
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
