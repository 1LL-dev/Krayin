{!! view_render_event('admin.dashboard.index.total_leads.before') !!}

<!-- Total Leads Vue Component -->
<v-dashboard-total-leads>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.total-leads />
</v-dashboard-total-leads>

{!! view_render_event('admin.dashboard.index.total_leads.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-leads-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.total-leads />
        </template>

        <!-- Calling Queue Section -->
        <template v-else>
            <div class="grid gap-3 rounded-lg border border-gray-300 bg-white px-3 py-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            Calling Queue
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Priority outreach for the next warm follow-up.
                        </p>
                    </div>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Next 5 calls
                    </span>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="lead in queue"
                        :key="lead.id"
                        class="flex items-center justify-between gap-3 border-b border-gray-200 px-3 py-2.5 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                @{{ getInitials(lead.name) }}
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">@{{ lead.name }}</p>

                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <span>@{{ lead.phone }}</span>
                                    <span v-if="lead.property_interest">· @{{ lead.property_interest }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <a
                                :href="'{{ route('admin.leads.calling_queue.index') }}'"
                                class="primary-button whitespace-nowrap"
                            >
                                Call
                            </a>
                        </div>
                    </div>

                    <div v-if="queue.length === 0" class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-300">
                        No leads are waiting for a call right now.
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-leads', {
            template: '#v-dashboard-total-leads-template',

            data() {
                return {
                    report: [],

                    queue: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getInitials(name) {
                    return (name || 'Lead').split(' ').filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();
                },

                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'calling-queue';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;
                            this.queue = Array.isArray(response.data.statistics) ? response.data.statistics : [];

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
@endPushOnce
