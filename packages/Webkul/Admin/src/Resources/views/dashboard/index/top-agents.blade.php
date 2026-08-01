{!! view_render_event('admin.dashboard.index.top_agents.before') !!}

<v-dashboard-top-agents>
    <x-admin::shimmer.dashboard.index.top-persons />
</v-dashboard-top-agents>

{!! view_render_event('admin.dashboard.index.top_agents.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-agents-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-persons />
        </template>

        <template v-else>
            <div class="w-full rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        Top Agents
                    </p>
                </div>

                <div
                    class="flex flex-col"
                    v-if="report.statistics.length"
                >
                    <div
                        class="flex items-center gap-2.5 border-b p-4 last:border-b-0 dark:border-gray-800"
                        v-for="(agent, index) in report.statistics"
                        :key="agent.id"
                    >
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            @{{ index + 1 }}
                        </span>

                        <x-admin::avatar ::name="agent.name" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-800 dark:text-white">
                                @{{ agent.name }}
                            </p>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @{{ agent.deals_won }} @{{ agent.deals_won === 1 ? 'deal won' : 'deals won' }}
                            </p>
                        </div>

                        <p class="shrink-0 text-sm font-medium text-gray-800 dark:text-white">
                            @{{ agent.formatted_closed_revenue }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <img
                            src="{{ vite()->asset('images/empty-placeholders/users.svg') }}"
                            class="dark:mix-blend-exclusion dark:invert"
                        >

                        <div class="flex flex-col items-center text-center">
                            <p class="text-base font-semibold text-gray-400">
                                No agent performance yet
                            </p>

                            <p class="text-gray-400">
                                Won deals will appear for the selected period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-agents', {
            template: '#v-dashboard-top-agents-template',

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
                getStats(filters) {
                    this.isLoading = true;

                    filters = Object.assign({}, filters, {type: 'top-agents'});

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                }
            }
        });
    </script>
@endPushOnce
