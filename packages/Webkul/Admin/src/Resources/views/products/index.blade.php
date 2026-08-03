<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.products.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <!-- Breadcrumbs -->
                <x-admin::breadcrumbs name="products" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.products.index.title')
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                {!! view_render_event('admin.products.index.create_button.before') !!}

                @if (bouncer()->hasPermission('settings.automation.data_transfer.imports.create'))
                    <a
                        href="{{ route('admin.settings.data_transfer.imports.create', ['type' => 'products']) }}"
                        class="secondary-button"
                    >
                        @lang('admin::app.products.index.import-btn')
                    </a>
                @endif

                <!-- Create button for Product -->
                @if (bouncer()->hasPermission('products.create'))
                    <a
                        href="{{ route('admin.products.create') }}"
                        class="primary-button"
                    >
                        @lang('admin::app.products.index.create-btn')
                    </a>
                @endif

                {!! view_render_event('admin.products.index.create_button.after') !!}
            </div>
        </div>

        {!! view_render_event('admin.products.index.datagrid.before') !!}

        @include('admin::products.index.listing-highlights')

        <div id="property-directory">
            <x-admin::datagrid :src="route('admin.products.index')">
                <!-- DataGrid Shimmer -->
                <x-admin::shimmer.datagrid />
            </x-admin::datagrid>
        </div>

        {!! view_render_event('admin.products.index.datagrid.after') !!}
    </div>
</x-admin::layouts>
