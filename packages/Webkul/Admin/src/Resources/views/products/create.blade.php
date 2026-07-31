@php
    $propertyAttributes = app('Webkul\\Attribute\\Repositories\\AttributeRepository')
        ->findWhere(['entity_type' => 'products'])
        ->sortBy('sort_order');

    $propertyAttributeCodes = [
        'name', 'sku', 'description', 'property_type', 'property_status',
        'property_address', 'bedrooms', 'bathrooms', 'area', 'area_unit',
        'parking_spaces', 'price',
    ];

    $ownerLookup = (object) ['code' => 'owner_id', 'name' => trans('admin::app.products.form.owner'), 'lookup_type' => 'persons'];
    $agentLookup = (object) ['code' => 'assigned_agent_id', 'name' => trans('admin::app.products.form.assigned-agent'), 'lookup_type' => 'users'];
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.products.create.title')
    </x-slot>

    {!! view_render_event('admin.products.create.form.before') !!}

    <x-admin::form
        :action="route('admin.products.store')"
        method="POST"
    >
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="products.create" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.products.create.title')
                    </div>
                </div>

                @if (bouncer()->hasPermission('settings.user.groups.create'))
                    <button type="submit" class="primary-button">
                        @lang('admin::app.products.create.save-btn')
                    </button>
                @endif
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.products.form.general-information')
                    </p>

                    <x-admin::attributes :custom-attributes="$propertyAttributes->whereIn('code', ['name', 'sku', 'description', 'property_type', 'property_status'])" />

                    <x-admin::form.control-group class="mb-2.5 w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.products.form.owner')
                        </x-admin::form.control-group.label>

                        <x-admin::attributes.edit.lookup
                            :attribute="$ownerLookup"
                            :value="null"
                            validations="required"
                            :can-add-new="false"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mb-2.5 w-full">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.products.form.assigned-agent')
                        </x-admin::form.control-group.label>

                        <x-admin::attributes.edit.lookup
                            :attribute="$agentLookup"
                            :value="null"
                            validations=""
                            :can-add-new="false"
                        />
                    </x-admin::form.control-group>

                    <x-admin::attributes :custom-attributes="$propertyAttributes->whereNotIn('code', array_merge($propertyAttributeCodes, ['quantity']))" />
                </div>

                <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.products.form.location')
                    </p>

                    <x-admin::attributes :custom-attributes="$propertyAttributes->where('code', 'property_address')" />
                </div>

                <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.products.form.property-details')
                    </p>

                    <x-admin::attributes :custom-attributes="$propertyAttributes->whereIn('code', ['bedrooms', 'bathrooms', 'area', 'area_unit', 'parking_spaces'])" />
                </div>

                <div class="flex flex-col gap-4">
                    <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.products.form.pricing')
                        </p>

                        <x-admin::attributes :custom-attributes="$propertyAttributes->where('code', 'price')" />
                    </div>

                    <div class="box-shadow rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.products.form.media')
                        </p>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            @lang('admin::app.products.form.media-placeholder')
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.products.create.form.after') !!}
</x-admin::layouts>
