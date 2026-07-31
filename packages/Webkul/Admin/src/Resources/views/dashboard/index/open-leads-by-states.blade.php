{!! view_render_event('admin.dashboard.index.open_leads_by_states.before') !!}

<!-- Total Leads Vue Component -->
<v-dashboard-open-leads-by-states>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.open-leads-by-states />
</v-dashboard-open-leads-by-states>

{!! view_render_event('admin.dashboard.index.open_leads_by_states.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-open-leads--by-states-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.open-leads-by-states />
        </template>

        <!-- Call Script Section -->
        <template v-else>
            <div class="grid gap-3 rounded-lg border border-gray-300 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            Call Script
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            A polished teleprompter for every outreach call.
                        </p>
                    </div>

                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Step @{{ currentScriptIndex + 1 }} / @{{ scripts.length }}
                    </span>
                </div>

                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                            Script category
                        </label>

                        <select
                            v-model="selectedScript"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-1 focus:ring-gray-200 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-gray-700 dark:focus:ring-gray-800"
                            @change="currentScriptIndex = 0"
                        >
                            <option value="intro">Intro call</option>
                            <option value="follow_up">Follow-up call</option>
                            <option value="viewing">Viewing follow-through</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                @{{ activeScript.title }}
                            </p>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Step @{{ currentScriptIndex + 1 }} of @{{ scripts.length }}
                            </p>
                        </div>

                        <div class="rounded-md border border-gray-300 bg-gray-50 p-3 text-sm leading-7 text-gray-700 dark:border-gray-800 dark:bg-gray-800/70 dark:text-gray-200">
                            @{{ activeScript.message }}
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Objectives
                            </p>

                            <ul class="mt-2 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-900 dark:bg-gray-200"></span>
                                    <span>Build rapport and confirm the buyer or seller intent.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-900 dark:bg-gray-200"></span>
                                    <span>Clarify the property fit, timing, and key priorities.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-900 dark:bg-gray-200"></span>
                                    <span>Move the conversation toward the next appointment.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="previousScript"
                        >
                            Previous
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            @click="nextScript"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </script>


    <script type="module">
        app.component('v-dashboard-open-leads-by-states', {
            template: '#v-dashboard-open-leads--by-states-template',

            data() {
                return {
                    report: [],

                    isLoading: true,

                    chart: undefined,

                    selectedScript: 'intro',

                    currentScriptIndex: 0,

                    scriptCatalog: {
                        intro: [
                            {
                                title: 'Warm intro',
                                message: 'Hello, I’m following up on the property you were reviewing. I’d love to understand what you’re looking for and whether I can help shortlist a few options that fit your brief.',
                            },
                            {
                                title: 'Ask about timing',
                                message: 'When are you hoping to move, and is this a move-in timeline you’re planning for this season or the next?',
                            },
                            {
                                title: 'Close the next step',
                                message: 'Would it be helpful if I sent over two or three homes that match your preferences and arranged a private viewing tomorrow?',
                            },
                        ],
                        follow_up: [
                            {
                                title: 'Reconfirm interest',
                                message: 'Hi, I’m checking in on the options I shared earlier. Have you had a chance to review them, and what stood out most?',
                            },
                            {
                                title: 'Handle objections',
                                message: 'If the price point feels high, I can help compare similar properties or look at alternatives that better fit your budget.',
                            },
                            {
                                title: 'Book the next action',
                                message: 'Would you prefer a call this afternoon or a private tour this week so I can guide you through the shortlist?',
                            },
                        ],
                        viewing: [
                            {
                                title: 'Post-viewing recap',
                                message: 'Thanks for touring the property with me today. I’ll summarize the highlights and follow up with the next best options based on your feedback.',
                            },
                            {
                                title: 'Confirm decision criteria',
                                message: 'Which features mattered most to you during the viewing: location, layout, outdoor space, or long-term value?',
                            },
                            {
                                title: 'Stay in motion',
                                message: 'If this property feels close, I can help you prepare an offer strategy and keep the next steps moving quickly.',
                            },
                        ],
                    },
                }
            },

            computed: {
                scripts() {
                    return this.scriptCatalog[this.selectedScript] || this.scriptCatalog.intro;
                },

                activeScript() {
                    return this.scripts[this.currentScriptIndex] || this.scripts[0];
                },

                selectedScriptLabel() {
                    return {
                        intro: 'Intro call',
                        follow_up: 'Follow-up',
                        viewing: 'Viewing follow-through',
                    }[this.selectedScript] || 'Intro call';
                },
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filtets) {
                    this.isLoading = true;

                    var filtets = Object.assign({}, filtets);

                    filtets.type = 'open-leads-by-states';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filtets
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;

                            setTimeout(() => {
                                this.prepare();
                            }, 0);
                        })
                        .catch(error => {});
                },

                prepare() {
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    if (this.report.statistics.length === 0) {
                        return;
                    }

                    const ctx = document.getElementById(this.$.uid + '_chart')?.getContext('2d');

                    // Create gradient
                    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(144, 247, 236, 0.8)');
                    gradient.addColorStop(1, 'rgba(50, 204, 188, 1)');

                    this.chart = new Chart(ctx, {
                        type: 'funnel',

                        data: {
                            labels: this.report.statistics.map(stat => stat.name),
                            datasets: [
                                {
                                    data: this.report.statistics.map(stat => stat.total),
                                    backgroundColor: gradient,
                                    borderColor: 'rgba(0, 0, 0, 0)',
                                    borderWidth: 0,
                                },
                            ],
                        },

                        options: {
                            indexAxis: 'y',
                        },
                    });
                },

                previousScript() {
                    if (this.scripts.length === 0) {
                        return;
                    }

                    this.currentScriptIndex = this.currentScriptIndex > 0
                        ? this.currentScriptIndex - 1
                        : this.scripts.length - 1;
                },

                nextScript() {
                    if (this.scripts.length === 0) {
                        return;
                    }

                    this.currentScriptIndex = (this.currentScriptIndex + 1) % this.scripts.length;
                }
            }
        });
    </script>
@endPushOnce
