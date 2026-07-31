<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.calling-queue.title')
    </x-slot>

    <v-calling-queue :initial-queue='@json($queue)'></v-calling-queue>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-calling-queue-template">
<div class="space-y-4">
    <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        <div class="flex flex-col gap-2">
            <x-admin::breadcrumbs name="leads" />

            <div class="text-xl font-bold dark:text-white">
                @lang('admin::app.calling-queue.title')
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border border-gray-300 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                @{{ statistics.total_assigned }} assigned
            </span>
            <span class="rounded-full border border-gray-300 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                @{{ statistics.remaining_today }} remaining
            </span>
            <span class="rounded-full border border-gray-300 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                @{{ statistics.completed_today }} completed
            </span>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(240px,25%)_minmax(0,1fr)_minmax(280px,25%)]">
        <aside class="self-start overflow-hidden rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-300 px-3 py-2.5 dark:border-gray-800">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                            Queue
                        </p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            @{{ filteredLeads.length }} leads
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="nextLead"
                        class="primary-button"
                    >
                        Next lead
                    </button>
                </div>

                <div class="relative mt-2.5">
                    <i class="icon-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                    <input
                        v-model="search"
                        class="w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 outline-none transition focus:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-gray-600"
                        placeholder="Search leads"
                    />
                </div>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button
                        v-for="filter in filters"
                        :key="filter.value"
                        type="button"
                        @click="activeFilter = filter.value"
                        class="rounded-md px-2.5 py-1.5 text-[11px] font-medium transition"
                        :class="
                            activeFilter === filter.value
                            ? 'bg-gray-900 text-white dark:bg-gray-700'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        @{{ filter.label }}
                    </button>
                </div>
            </div>

            <div class="max-h-[calc(100vh-260px)] overflow-y-auto">
                <button
                    v-for="lead in filteredLeads"
                    :key="lead.id"
                    type="button"
                    @click="selectLead(lead)"
                    class="flex w-full items-start gap-2 border-b border-gray-200 px-3 py-2.5 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50"
                    :class="
                        selectedLead && selectedLead.id === lead.id
                        ? 'bg-gray-50 dark:bg-gray-800/50'
                        : ''
                    "
                >
                    <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        @{{ (lead.contact && lead.contact.name) ? (lead.contact.name).charAt(0).toUpperCase() : '?' }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                @{{ (lead.contact && lead.contact.name) || lead.title }}
                            </p>
                            <span
                                v-if="lastOutcome(lead) === 'Callback Requested'"
                                class="shrink-0 rounded-full border border-gray-300 bg-gray-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Callback
                            </span>
                        </div>

                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                            @{{ (lead.contact && lead.contact.phone) || (lead.contact && lead.contact.email) || '—' }}
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <span v-if="lead.stage" class="rounded-full border border-gray-300 bg-gray-50 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                @{{ lead.stage }}
                            </span>
                            <span v-if="lastOutcome(lead)" class="text-[10px] text-gray-500 dark:text-gray-400">
                                @{{ lastOutcome(lead) }}
                            </span>
                        </div>
                    </div>
                </button>

                <div
                    v-if="filteredLeads.length === 0"
                    class="px-4 py-8 text-center"
                >
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No leads found</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Try adjusting your search or filter.</p>
                </div>
            </div>
        </aside>

        <main class="flex flex-col gap-3">
            <section v-if="selectedLead" class="rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-300 px-4 py-3 dark:border-gray-800">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-base font-semibold text-white dark:bg-gray-700">
                            @{{ (selectedLead.contact && selectedLead.contact.name) ? (selectedLead.contact.name).charAt(0).toUpperCase() : '?' }}
                        </div>

                        <div class="min-w-0">
                            <div v-if="selectedLead.stage" class="mb-1 flex items-center gap-2">
                                <span class="rounded-full border border-gray-300 bg-gray-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    @{{ selectedLead.stage }}
                                </span>
                            </div>

                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                @{{ (selectedLead.contact && selectedLead.contact.name) || selectedLead.title }}
                            </h2>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1.5">
                                    <i class="icon-phone text-gray-400"></i>
                                    @{{ (selectedLead.contact && selectedLead.contact.phone) || '-' }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="icon-mail text-gray-400"></i>
                                    @{{ (selectedLead.contact && selectedLead.contact.email) || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <a
                        :href="leadUrl(selectedLead.id)"
                        class="secondary-button"
                    >
                        View lead
                    </a>
                </div>

                <div class="grid gap-0 border-b border-gray-300 dark:border-gray-800 md:grid-cols-[1.2fr_0.8fr]">
                    <div class="border-b border-gray-300 px-4 py-3 dark:border-gray-800 md:border-b-0 md:border-r">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                            Buyer brief
                        </p>
                        <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                            @{{ selectedLead.requirements.intent || '-' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @{{ selectedLead.requirements.property_type || '-' }}
                            <span class="mx-1 text-gray-300">·</span>
                            @{{ selectedLead.requirements.bedrooms || '-' }} beds
                        </p>
                    </div>

                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">
                            Budget & location
                        </p>
                        <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                            @{{ formatPrice(selectedLead.requirements.budget_min) }}
                            <span class="text-gray-400">–</span>
                            @{{ formatPrice(selectedLead.requirements.budget_max) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @{{ selectedLead.requirements.location || '-' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 p-4 md:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Property matches</h3>
                            <span class="rounded-full border border-gray-300 bg-white px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                @{{ selectedLead.properties.length }}
                            </span>
                        </div>

                        <div v-if="selectedLead.properties.length" class="space-y-2">
                            <a
                                v-for="property in selectedLead.properties"
                                :key="property.id"
                                :href="propertyUrl(property.id)"
                                class="flex items-center gap-2 rounded-md border border-gray-200 px-2.5 py-2 transition hover:bg-white dark:border-gray-800 dark:hover:bg-gray-800/70"
                            >
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-gray-900 text-white dark:bg-gray-700">
                                    <i class="icon-home text-sm"></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        @{{ property.name }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                        @{{ formatPrice(property.price) }} · @{{ property.match_status || 'Match' }}
                                    </p>
                                </div>
                            </a>
                        </div>

                        <div v-else class="px-2 py-4 text-center">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No matched properties</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Review the buyer brief during the call and attach listings afterward.</p>
                        </div>
                    </div>

                    <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Activity timeline</h3>
                            <span class="rounded-full border border-gray-300 bg-white px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                @{{ selectedLead.activities.length }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div v-for="activity in selectedLead.activities" :key="activity.id" class="rounded-md border border-gray-200 bg-white px-2.5 py-2 dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            @{{ activity.call_outcome || activity.type }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                            @{{ activity.comment }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500">
                                        @{{ formatDate(activity.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-else class="flex min-h-[280px] items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                <div class="max-w-xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        <i class="icon-phone text-2xl"></i>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No lead selected</h3>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Choose a lead from the queue to begin the outbound session.</p>
                </div>
            </section>
        </main>

        <aside v-if="selectedLead" class="self-start rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-300 px-3 py-3 dark:border-gray-800">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Call workspace</h3>
                    <span
                        class="rounded-full px-2.5 py-1 text-[10px] font-semibold"
                        :class="
                            isCalling
                            ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
                        "
                    >
                        @{{ isCalling ? 'In call' : 'Ready' }}
                    </span>
                </div>
            </div>

            <div class="space-y-3 p-3">
                <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Call script</p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                        Review the buyer brief, confirm the intent, and guide the conversation toward the best-fit property.
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @{{ selectedLead.requirements.intent || 'Discuss the buyer’s goals and confirm next steps.' }}
                    </p>
                </div>

                <div class="rounded-md border border-gray-300 bg-white p-3 text-center dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Call timer</p>
                    <p class="mt-2 font-mono text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        @{{ timer }}
                    </p>

                    <button
                        v-if="!isCalling"
                        type="button"
                        @click="startCall"
                        class="primary-button mt-3 w-full"
                    >
                        Start call
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="endCall"
                        class="secondary-button mt-3 w-full"
                    >
                        End call
                    </button>
                </div>

                <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Outcome</p>
                    <div class="grid grid-cols-2 gap-1.5">
                        <template v-for="outcome in outcomes" :key="'outcome-' + outcome">
                            <button
                                v-if="outcome === 'Interested' || outcome === 'Appointment Booked' || outcome === 'Callback Requested' || outcome === 'No Answer' || outcome === 'Busy' || outcome === 'Left Voicemail' || outcome === 'Not Interested' || outcome === 'Wrong Number' || outcome === 'Do Not Call'"
                                type="button"
                                @click="selectedOutcome = outcome"
                                class="rounded-md border px-2 py-1.5 text-[10px] font-medium transition"
                                :class="
                                    selectedOutcome === outcome
                                    ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-600 dark:bg-gray-700'
                                    : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'
                                "
                            >
                                @{{ outcome }}
                            </button>
                        </template>
                    </div>
                </div>

                <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <label class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Notes</label>
                    <textarea
                        v-model="notes"
                        class="mt-1.5 min-h-20 w-full rounded-md border border-gray-300 bg-white p-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:bg-white dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-gray-600"
                        placeholder="Call notes..."
                    ></textarea>
                </div>

                <div class="rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <label class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Follow-up</label>
                    <input
                        v-model="followUpAt"
                        type="datetime-local"
                        class="mt-1.5 w-full rounded-md border border-gray-300 bg-white p-2 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:bg-white dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-gray-600"
                    />
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="saveCall(false)"
                        :disabled="isSaving"
                        class="secondary-button flex-1"
                    >
                        Save
                    </button>

                    <button
                        type="button"
                        @click="saveCall(true)"
                        :disabled="isSaving"
                        class="primary-button flex-[2]"
                    >
                        Save & Next
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>
        </script>

        <script type="module">
            app.component('v-calling-queue', {
                template: '#v-calling-queue-template',

                props: {
                    initialQueue: {
                        type: Object,
                        required: true,
                    },
                },

                data() {
                    return {
                        leads: this.initialQueue.leads || [],
                        statistics: this.initialQueue.statistics || {},
                        search: '',
                        activeFilter: 'all',
                        filters: [
                            { value: 'all', label: "@lang('admin::app.calling-queue.all')" },
                            { value: 'new', label: "@lang('admin::app.calling-queue.new')" },
                            { value: 'follow_up_due', label: "@lang('admin::app.calling-queue.follow-up-due')" },
                            { value: 'callback_requested', label: "@lang('admin::app.calling-queue.callback-requested')" },
                            { value: 'no_answer', label: "@lang('admin::app.calling-queue.no-answer')" },
                            { value: 'interested', label: "@lang('admin::app.calling-queue.interested')" },
                            { value: 'not_interested', label: "@lang('admin::app.calling-queue.not-interested')" },
                            { value: 'completed', label: "@lang('admin::app.calling-queue.completed')" },
                        ],
                        selectedLead: null,
                        isCalling: false,
                        isSaving: false,
                        timer: '00:00',
                        timerSeconds: 0,
                        timerInterval: null,
                        selectedOutcome: null,
                        notes: '',
                        followUpAt: '',
                        outcomes: [
                            'No Answer',
                            'Busy',
                            'Left Voicemail',
                            'Interested',
                            'Not Interested',
                            'Callback Requested',
                            'Wrong Number',
                            'Do Not Call',
                            'Appointment Booked',
                        ],
                        storeCallUrl: "{{ route('admin.leads.calling_queue.calls.store') }}",
                        leadViewUrl: "{{ route('admin.leads.view', ':id') }}",
                        productViewUrl: "{{ route('admin.products.view', ':id') }}",
                    };
                },

                mounted() {
                    if (this.filteredLeads.length) {
                        this.selectLead(this.filteredLeads[0]);
                    }
                },

                beforeUnmount() {
                    this.clearTimer();
                },

                computed: {
                    filteredLeads() {
                        const term = this.search.trim().toLowerCase();

                        return this.leads.filter((lead) => {
                            if (! this.matchesFilter(lead)) {
                                return false;
                            }

                            if (! term) {
                                return true;
                            }

                            const haystack = [
                                lead.title,
                                lead.stage,
                                lead.contact?.name,
                                lead.contact?.phone,
                                lead.contact?.email,
                            ]
                                .filter(Boolean)
                                .join(' ')
                                .toLowerCase();

                            return haystack.includes(term);
                        });
                    },
                },

                methods: {
                    matchesFilter(lead) {
                        const outcome = this.lastOutcome(lead);

                        switch (this.activeFilter) {
                            case 'new':
                                return ! outcome;

                            case 'follow_up_due':
                                return (lead.activities || []).some((activity) => activity.follow_up_at);

                            case 'callback_requested':
                                return outcome === 'Callback Requested';

                            case 'no_answer':
                                return outcome === 'No Answer';

                            case 'interested':
                                return outcome === 'Interested';

                            case 'not_interested':
                                return outcome === 'Not Interested';

                            case 'completed':
                                return (lead.activities || []).some((activity) => activity.type === 'call');

                            default:
                                return true;
                        }
                    },

                    selectLead(lead) {
                        this.endCall(false);
                        this.selectedLead = lead;
                        this.selectedOutcome = null;
                        this.notes = '';
                        this.followUpAt = '';
                    },

                    nextLead() {
                        if (! this.filteredLeads.length) {
                            return;
                        }

                        if (! this.selectedLead) {
                            this.selectLead(this.filteredLeads[0]);

                            return;
                        }

                        const index = this.filteredLeads.findIndex((lead) => lead.id === this.selectedLead.id);
                        const next = this.filteredLeads[(index + 1) % this.filteredLeads.length];

                        this.selectLead(next);
                    },

                    lastOutcome(lead) {
                        const activity = (lead.activities || []).find((item) => item.call_outcome);

                        return activity?.call_outcome || null;
                    },

                    leadUrl(id) {
                        return this.leadViewUrl.replace(':id', id);
                    },

                    propertyUrl(id) {
                        return this.productViewUrl.replace(':id', id);
                    },

                    formatPrice(value) {
                        if (value === null || value === undefined || value === '') {
                            return '-';
                        }

                        return this.$admin.formatPrice(value);
                    },

                    formatDate(value) {
                        if (! value) {
                            return '-';
                        }

                        return this.$admin.formatDate(value, 'MMM DD, yyyy HH:mm', 'UTC');
                    },

                    startCall() {
                        this.isCalling = true;
                        this.timerSeconds = 0;
                        this.timer = '00:00';
                        this.clearTimer();

                        this.timerInterval = setInterval(() => {
                            this.timerSeconds += 1;
                            const minutes = String(Math.floor(this.timerSeconds / 60)).padStart(2, '0');
                            const seconds = String(this.timerSeconds % 60).padStart(2, '0');
                            this.timer = `${minutes}:${seconds}`;
                        }, 1000);
                    },

                    endCall(keepSeconds = true) {
                        this.isCalling = false;
                        this.clearTimer();

                        if (! keepSeconds) {
                            this.timerSeconds = 0;
                            this.timer = '00:00';
                        }
                    },

                    clearTimer() {
                        if (this.timerInterval) {
                            clearInterval(this.timerInterval);
                            this.timerInterval = null;
                        }
                    },

                    saveCall(goNext) {
                        if (this.isSaving || ! this.selectedLead) {
                            return;
                        }

                        if (! this.selectedOutcome) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: "@lang('admin::app.calling-queue.select-outcome')",
                            });

                            return;
                        }

                        this.isSaving = true;

                        this.$axios.post(this.storeCallUrl, {
                            lead_id: this.selectedLead.id,
                            call_outcome: this.selectedOutcome,
                            comment: this.notes || null,
                            call_duration: this.timerSeconds || 0,
                            follow_up_at: this.followUpAt || null,
                        }).then((response) => {
                            const activity = response.data.activity;

                            this.selectedLead.activities = [activity, ...(this.selectedLead.activities || [])];

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });

                            this.endCall(false);
                            this.selectedOutcome = null;
                            this.notes = '';
                            this.followUpAt = '';

                            if (goNext) {
                                this.nextLead();
                            }
                        }).finally(() => {
                            this.isSaving = false;
                        });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
