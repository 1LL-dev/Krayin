<v-property-listing-highlights>
    <div class="light-shimmer-bg dark:shimmer h-48 rounded-lg"></div>
</v-property-listing-highlights>

@pushOnce('scripts')
    <script type="text/x-template" id="v-property-listing-highlights-template">
        <div v-if="isLoading" class="light-shimmer-bg dark:shimmer h-48 rounded-lg"></div>
        <section v-else class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between"><div><p class="text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.products.index.listing-highlights.title')</p><p class="text-sm text-gray-500">@lang('admin::app.products.index.listing-highlights.info')</p></div><a href="#property-directory" class="text-sm font-medium text-brandColor">@lang('admin::app.products.index.listing-highlights.directory')</a></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <a v-for="property in properties" :key="property.id" :href="propertyUrl(property.id)" class="group overflow-hidden rounded-lg border border-gray-200 transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800">
                    <div class="flex h-24 items-end bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 p-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusClass(property.property_status_name)">@{{ property.property_status_name || 'Draft' }}</span></div>
                    <div class="p-3"><div class="flex items-start justify-between gap-2"><p class="truncate font-semibold text-gray-800 dark:text-white">@{{ property.name }}</p><span class="whitespace-nowrap text-sm font-semibold text-brandColor">@{{ property.price }}</span></div><p class="mt-1 truncate text-sm text-gray-500">@{{ location(property.location) || 'Location not set' }}</p><div class="mt-3 flex items-center justify-between text-xs text-gray-500"><span>@{{ property.property_type_name || 'Property' }}</span><span>@{{ property.bedrooms || '—' }} bd · @{{ property.bathrooms || '—' }} ba</span></div><p class="mt-2 text-xs text-gray-500">@{{ property.assigned_agent_name || 'Unassigned agent' }}</p></div>
                </a>
            </div>
        </section>
    </script>

    <script type="module">
        app.component('v-property-listing-highlights', {
            template: '#v-property-listing-highlights-template',
            data() { return {isLoading: true, properties: []}; },
            mounted() {
                this.$axios.get('{{ route('admin.products.index') }}', {params: {pagination: {page: 1, per_page: 6}}})
                    .then(response => { this.properties = response.data.records; })
                    .finally(() => { this.isLoading = false; });
            },
            methods: {
                propertyUrl(id) { return '{{ route('admin.products.view', ':id') }}'.replace(':id', id); },
                location(value) { try { const address = JSON.parse(value); return [address.address, address.city, address.state].filter(Boolean).join(', '); } catch (error) { return value; } },
                statusClass(status) {
                    return {
                        Available: 'bg-emerald-100 text-emerald-800',
                        Reserved: 'bg-amber-100 text-amber-800',
                        Sold: 'bg-slate-200 text-slate-800',
                        Rented: 'bg-blue-100 text-blue-800',
                    }[status] || 'bg-white/90 text-gray-800';
                },
            },
        });
    </script>
@endPushOnce
