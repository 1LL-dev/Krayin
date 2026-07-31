@php
    $leadLookup = (object) ['code' => 'lead_id', 'name' => trans('admin::app.products.view.interested-leads.add'), 'lookup_type' => 'leads'];
@endphp

<div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-4 flex items-center justify-between gap-4">
        <h4 class="font-semibold dark:text-white">
            @lang('admin::app.products.view.interested-leads.title')
        </h4>

        <span class="rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-800">
            {{ $product->interestedLeads->count() }}
        </span>
    </div>

    <x-admin::form :action="route('admin.products.leads.attach', $product->id)" method="POST">
        <div class="mb-4 flex items-end gap-2 max-sm:flex-col">
            <div class="w-full">
                <x-admin::attributes.edit.lookup
                    :attribute="$leadLookup"
                    :value="null"
                    validations="required"
                    :can-add-new="false"
                />
            </div>

            <button type="submit" class="secondary-button whitespace-nowrap">
                @lang('admin::app.products.view.interested-leads.attach')
            </button>
        </div>
    </x-admin::form>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b dark:border-gray-800">
                <tr>
                    <th class="p-2">@lang('admin::app.products.view.interested-leads.lead-name')</th>
                    <th class="p-2">@lang('admin::app.products.view.interested-leads.status')</th>
                    <th class="p-2">@lang('admin::app.products.view.interested-leads.phone')</th>
                    <th class="p-2">@lang('admin::app.products.view.interested-leads.email')</th>
                    <th class="p-2">@lang('admin::app.products.view.interested-leads.last-activity')</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($product->interestedLeads as $leadProduct)
                    @php $lead = $leadProduct->lead; @endphp

                    @if ($lead)
                        <tr class="border-b dark:border-gray-800">
                            <td class="p-2">
                                <a class="text-brandColor hover:underline" href="{{ route('admin.leads.view', $lead->id) }}">{{ $lead->title }}</a>
                            </td>
                            <td class="p-2">{{ $lead->stage?->name ?? $lead->status ?? '--' }}</td>
                            <td class="p-2">{{ data_get($lead->person, 'contact_numbers.0.value', '--') }}</td>
                            <td class="p-2">{{ data_get($lead->person, 'emails.0.value', '--') }}</td>
                            <td class="p-2">{{ $lead->activities->sortByDesc('created_at')->first()?->created_at?->format('M d, Y H:i') ?? '--' }}</td>
                            <td class="p-2 text-right">
                                <x-admin::form :action="route('admin.products.leads.detach', [$product->id, $lead->id])" method="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline">@lang('admin::app.products.view.interested-leads.remove')</button>
                                </x-admin::form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td class="p-3 text-center text-gray-500" colspan="6">@lang('admin::app.products.view.interested-leads.empty')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
