{!! view_render_event('admin.dashboard.index.revenue.before') !!}

<v-dashboard-revenue-stats>
    <x-admin::shimmer.dashboard.index.revenue />
</v-dashboard-revenue-stats>

{!! view_render_event('admin.dashboard.index.revenue.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-revenue-stats-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue />
        </template>

        <template v-else>
            <div class="overflow-hidden rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">Portfolio snapshot</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Current listing value and availability at a glance.</p>
                    </div>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        @{{ report.statistics.property_overview.total }} properties
                    </span>
                </div>

                <div class="grid md:grid-cols-[1.35fr_1fr]">
                    <div class="px-5 py-6 md:border-r md:border-gray-200 dark:md:border-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total property value</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            @{{ report.statistics.property_overview.formatted_total_property_value }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Combined list value across the portfolio.</p>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-6 md:border-t-0 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Active listings</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            @{{ report.statistics.property_overview.active_listings }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Listings currently carrying an active value.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 border-t border-gray-200 sm:grid-cols-3 xl:grid-cols-6 dark:border-gray-800">
                    <div
                        class="border-b border-r border-gray-200 px-4 py-3 last:border-r-0 sm:border-b-0 dark:border-gray-800"
                        v-for="status in report.statistics.property_overview.status_breakdown"
                        :key="status.key"
                    >
                        <p class="truncate text-xs capitalize text-gray-500 dark:text-gray-400">@{{ formatStatus(status.key) }}</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">@{{ status.value }}</p>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-revenue-stats', {
            template: '#v-dashboard-revenue-stats-template',

            data() {
                return {
                    report: {
                        statistics: {
                            property_overview: {
                                total: 0,
                                total_property_value: 0,
                                formatted_total_property_value: '',
                                active_listings: 0,
                                status_breakdown: [],
                            },
                        },
                    },
                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                formatStatus(value) {
                    return String(value || '').replaceAll('-', ' ');
                },

                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                        params: Object.assign({}, filters, {type: 'revenue-stats'}),
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
