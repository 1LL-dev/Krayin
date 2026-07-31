<div class="flex w-full flex-col gap-4 border-b border-gray-300 p-4 dark:border-gray-800 dark:text-white">
    <h4 class="font-semibold dark:text-white">
        @lang('admin::app.products.view.ownership.title')
    </h4>

    <div class="flex flex-col gap-3">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('admin::app.products.form.owner')</p>

            @if ($product->owner)
                <a class="font-medium text-brandColor hover:underline" href="{{ route('admin.contacts.persons.view', $product->owner->id) }}">
                    {{ $product->owner->name }}
                </a>

                @if (! empty($product->owner->emails[0]['value']))
                    <p class="text-sm">{{ $product->owner->emails[0]['value'] }}</p>
                @endif

                @if (! empty($product->owner->contact_numbers[0]['value']))
                    <p class="text-sm">{{ $product->owner->contact_numbers[0]['value'] }}</p>
                @endif
            @else
                <p class="text-sm">--</p>
            @endif
        </div>

        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('admin::app.products.form.assigned-agent')</p>

            @if ($product->assignedAgent)
                <div class="flex items-center gap-2">
                    @if ($product->assignedAgent->image_url)
                        <img class="h-7 w-7 rounded-full object-cover" src="{{ $product->assignedAgent->image_url }}" alt="{{ $product->assignedAgent->name }}">
                    @endif

                    <span class="font-medium">{{ $product->assignedAgent->name }}</span>
                </div>
            @else
                <p class="text-sm">--</p>
            @endif
        </div>
    </div>
</div>
