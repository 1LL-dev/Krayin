<v-dashboard-my-real-estate-work></v-dashboard-my-real-estate-work>

@pushOnce('scripts')
    <script type="text/x-template" id="v-dashboard-my-real-estate-work-template">
        <div class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.dashboard.index.real-estate.my-work')</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('admin.leads.create') }}" class="secondary-button">@lang('admin::app.dashboard.index.real-estate.add-lead')</a>
                <a href="{{ route('admin.products.create') }}" class="secondary-button">@lang('admin::app.dashboard.index.real-estate.add-property')</a>
                <a href="{{ route('admin.leads.index') }}" class="secondary-button">@lang('admin::app.dashboard.index.real-estate.schedule-viewing')</a>
                <a href="{{ route('admin.leads.calling_queue.index') }}" class="secondary-button">@lang('admin::app.dashboard.index.real-estate.log-call')</a>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <a href="{{ route('admin.leads.index') }}" class="rounded bg-gray-50 p-3 dark:bg-gray-950"><p class="text-2xl font-semibold">@{{ work.leads }}</p><p class="text-sm text-gray-500">@lang('admin::app.dashboard.index.real-estate.my-leads')</p></a>
                <a href="{{ route('admin.products.index') }}" class="rounded bg-gray-50 p-3 dark:bg-gray-950"><p class="text-2xl font-semibold">@{{ work.properties }}</p><p class="text-sm text-gray-500">@lang('admin::app.dashboard.index.real-estate.my-properties')</p></a>
                <div class="rounded bg-gray-50 p-3 dark:bg-gray-950"><p class="text-sm font-medium">@lang('admin::app.dashboard.index.real-estate.today-follow-ups')</p><p v-for="activity in work.follow_ups" :key="activity.id" class="mt-1 truncate text-sm text-gray-500">@{{ activity.title || '—' }}</p><p v-if="! work.follow_ups.length" class="mt-1 text-sm text-gray-500">—</p></div>
                <div class="rounded bg-gray-50 p-3 dark:bg-gray-950"><p class="text-sm font-medium">@lang('admin::app.dashboard.index.real-estate.my-upcoming-viewings')</p><p v-for="activity in work.viewings" :key="activity.id" class="mt-1 truncate text-sm text-gray-500">@{{ activity.title || '—' }}</p><p v-if="! work.viewings.length" class="mt-1 text-sm text-gray-500">—</p></div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-dashboard-my-real-estate-work', {
            template: '#v-dashboard-my-real-estate-work-template',
            data() { return {work: {leads: 0, properties: 0, follow_ups: [], viewings: []}}; },
            mounted() {
                this.$axios.get("{{ route('admin.dashboard.stats') }}", {params: {type: 'my-real-estate-work'}})
                    .then(response => { this.work = response.data.statistics; });
            },
        });
    </script>
@endPushOnce
