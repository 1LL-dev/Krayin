{!! view_render_event('admin.dashboard.index.top_selling_proudcts.before') !!}

<!-- Top Selling Products Vue Component -->
<v-dashboard-top-selling-products>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.top-selling-products />
</v-dashboard-top-selling-products>

{!! view_render_event('admin.dashboard.index.top_selling_proudcts.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-selling-products-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-selling-products />
        </template>

        <!-- Follow-ups Section -->
        <template v-else>
            <div class="w-full rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        Today's Follow-ups
                    </p>
                </div>

                <div class="flex flex-col" v-if="followUps.length">
                    <div
                        class="flex gap-2.5 border-b p-4 last:border-b-0 dark:border-gray-800"
                        v-for="item in followUps"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @{{ getInitials(item.title) }}
                        </div>

                        <div class="flex w-full flex-col gap-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white" v-text="item.title"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @{{ formatDate(item.follow_up_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 p-4" v-else>
                    <div class="rounded-lg border border-dashed border-gray-200 p-4 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No follow-ups are scheduled for today yet.
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-selling-products', {
            template: '#v-dashboard-top-selling-products-template',

            data() {
                return {
                    report: [],

                    followUps: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getInitials(value) {
                    return (value || 'Follow').split(' ').filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();
                },

                formatDate(value) {
                    if (!value) {
                        return 'No time set';
                    }

                    return new Date(value).toLocaleString([], {
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                    });
                },

                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'my-real-estate-work';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;
                            this.followUps = Array.isArray(response.data.statistics.follow_ups) ? response.data.statistics.follow_ups : [];

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
@endPushOnce
