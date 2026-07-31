<section class="grid gap-4 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 xl:grid-cols-3">
    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-950">
        <p class="text-sm font-semibold text-gray-500"><i class="icon-user mr-1"></i>@lang('admin::app.leads.view.client-summary.client-information')</p>
        <p class="mt-2 font-semibold text-gray-800 dark:text-white">{{ $lead->person?->name ?? $lead->title }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ collect($lead->person?->contact_numbers ?? [])->pluck('value')->first() ?? '—' }}</p>
        <p class="text-sm text-gray-500">{{ collect($lead->person?->emails ?? [])->pluck('value')->first() ?? '—' }}</p>
    </div>
    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-950">
        <p class="text-sm font-semibold text-gray-500"><i class="icon-filter mr-1"></i>@lang('admin::app.leads.view.client-summary.requirements')</p>
        <p class="mt-2 text-sm text-gray-800 dark:text-white">{{ $lead->purchase_preference ?: '—' }} · {{ $lead->preferred_property_type ?: '—' }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ $lead->preferred_location ?: 'Location not set' }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ $lead->bedrooms_required ?: '—' }} @lang('admin::app.leads.view.client-summary.bedrooms') · {{ core()->formatBasePrice($lead->budget_min ?: 0) }} – {{ core()->formatBasePrice($lead->budget_max ?: 0) }}</p>
    </div>
    <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-950">
        <p class="text-sm font-semibold text-gray-500"><i class="icon-activity mr-1"></i>@lang('admin::app.leads.view.client-summary.workflow')</p>
        <p class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-200">{{ $lead->stage?->name ?? '—' }}</p>
        <p class="mt-2 text-sm text-gray-500">@lang('admin::app.leads.view.client-summary.activity-info')</p>
    </div>
</section>
