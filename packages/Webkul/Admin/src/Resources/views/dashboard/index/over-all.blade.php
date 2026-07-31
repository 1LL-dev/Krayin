{!! view_render_event('admin.dashboard.index.over_all.before') !!}

<!-- Over Details Vue Component -->
<v-dashboard-over-all-stats>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.over-all />
</v-dashboard-over-all-stats>

{!! view_render_event('admin.dashboard.index.over_all.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-over-all-stats-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.over-all />
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <!-- Stats Cards -->
            <div class="grid grid-cols-3 gap-4 max-md:grid-cols-2 max-sm:grid-cols-1">
                <!-- New Leads Today -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        New Leads Today
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.new_leads_today }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                Today
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Follow-ups Due -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        Follow-ups Due
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.follow_ups_due }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                Next 24h
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Viewings Today -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        Viewings Today
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.viewings_today }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                Scheduled
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Calls Pending -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        Calls Pending
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.calls_pending }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                Needs action
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Active Buyers -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        Active Buyers
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.active_buyers }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                In pipeline
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Active Sellers -->
                <div class="flex flex-col gap-2 rounded-lg border border-gray-300 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        Active Sellers
                    </p>

                    <div class="flex gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ report.statistics.workflow_snapshot.active_sellers }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span class="icon-stats-up text-base !font-semibold text-green-500 dark:!text-green-500"></span>

                            <p class="text-xs font-semibold text-green-500">
                                On roster
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-over-all-stats', {
            template: '#v-dashboard-over-all-stats-template',

            data() {
                return {
                    report: [],

                    isLoading: true,

                    chart: undefined,
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'over-all';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                },
            }
        });
    </script>
@endPushOnce
