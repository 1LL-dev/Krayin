{!! view_render_event('admin.dashboard.index.top_selling_proudcts.before') !!}

<v-dashboard-top-selling-products>
    <x-admin::shimmer.dashboard.index.top-selling-products />
</v-dashboard-top-selling-products>

{!! view_render_event('admin.dashboard.index.top_selling_proudcts.after') !!}

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-top-selling-products-template">
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-selling-products />
        </template>

        <template v-else>
            <div class="overflow-hidden rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">Top property performance</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Closed-revenue leaders for the selected reporting period.</p>
                    </div>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Top @{{ report.statistics.length }}
                    </span>
                </div>

                <div v-if="report.statistics.length">
                    <div class="grid grid-cols-[minmax(0,1fr)_160px_160px] gap-4 border-b border-gray-200 bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 max-md:hidden dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                        <span>Property</span>
                        <span>List price</span>
                        <span class="text-right">Closed revenue</span>
                    </div>

                    <a
                        class="grid grid-cols-[minmax(0,1fr)_160px_160px] items-center gap-4 border-b border-gray-200 px-5 py-4 transition-colors last:border-b-0 hover:bg-gray-50 max-md:grid-cols-1 max-md:gap-2 dark:border-gray-800 dark:hover:bg-gray-950"
                        v-for="(property, index) in report.statistics"
                        :key="property.id"
                        :href="propertyUrl(property.id)"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                @{{ index + 1 }}
                            </span>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">@{{ property.name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 md:hidden dark:text-gray-400">List price · @{{ property.formatted_price }}</p>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 max-md:hidden dark:text-gray-300">@{{ property.formatted_price }}</p>

                        <div class="flex items-center justify-between gap-3 md:block md:text-right">
                            <span class="text-xs text-gray-500 md:hidden dark:text-gray-400">Closed revenue</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">@{{ property.formatted_revenue }}</span>
                        </div>
                    </a>
                </div>

                <div v-else class="m-5 rounded-md border border-dashed border-gray-200 px-4 py-10 text-center dark:border-gray-800">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No closed property revenue yet</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completed property deals will appear here for the selected period.</p>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-selling-products', {
            template: '#v-dashboard-top-selling-products-template',

            data() {
                return {
                    report: {statistics: []},
                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});
                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                propertyUrl(id) {
                    return '{{ route('admin.products.view', ':id') }}'.replace(':id', id);
                },

                getStats(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                        params: Object.assign({}, filters, {type: 'top-selling-products'}),
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
