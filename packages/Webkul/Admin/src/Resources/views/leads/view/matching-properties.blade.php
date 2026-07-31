<v-lead-property-matches></v-lead-property-matches>

@pushOnce('scripts')
    <script type="text/x-template" id="v-lead-property-matches-template">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">@lang('admin::app.leads.view.property-matches.title')</p>
                    <p class="text-sm text-gray-500">@lang('admin::app.leads.view.property-matches.info')</p>
                </div>
                <x-admin::lookup
                    ::src="searchUrl"
                    name="property"
                    :placeholder="trans('admin::app.leads.view.property-matches.add-property')"
                    @on-selected="addProperty"
                />
            </div>

            <div v-if="properties.length" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="property in properties" :key="property.id" class="rounded-lg border border-gray-300 p-3 shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div><span class="rounded-full px-2 py-1 text-xs font-semibold" :class="matchClass(property.match_status)">@{{ matchLabel(property.match_status) }}</span><a :href="propertyUrl(property.product_id)" class="mt-2 block font-medium text-brandColor hover:underline">@{{ property.name }}</a></div>
                        <i class="icon-delete cursor-pointer text-xl" @click="removeProperty(property)"></i>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">@{{ $admin.formatPrice(property.price || 0) }}</p>
                    <select v-model="property.match_status" @change="saveMatch(property)" class="mt-3 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="interested">@lang('admin::app.leads.view.property-matches.interested')</option>
                        <option value="viewed">@lang('admin::app.leads.view.property-matches.viewed')</option>
                        <option value="favorite">@lang('admin::app.leads.view.property-matches.favorite')</option>
                        <option value="rejected">@lang('admin::app.leads.view.property-matches.rejected')</option>
                    </select>
                </div>
            </div>

            <div v-else class="py-8 text-center text-sm text-gray-500">@lang('admin::app.leads.view.property-matches.empty')</div>
        </div>
    </script>

    <script type="module">
        app.component('v-lead-property-matches', {
            template: '#v-lead-property-matches-template',

            data() {
                return {
                    properties: @json($lead->products),
                    searchUrl: '{{ route('admin.products.search') }}',
                }
            },

            methods: {
                addProperty(property) {
                    if (this.properties.some(item => item.product_id === property.id)) {
                        return;
                    }

                    const match = {product_id: property.id, name: property.name, price: property.price, quantity: 1, match_status: 'interested'};
                    this.$axios.put('{{ route('admin.leads.product.add', $lead->id) }}', match)
                        .then(response => {
                            this.properties.push(response.data.data);
                            this.$emitter.emit('add-flash', {type: 'success', message: response.data.message});
                        });
                },

                saveMatch(property) {
                    this.$axios.put('{{ route('admin.leads.product.add', $lead->id) }}', {
                        product_id: property.product_id,
                        price: property.price || 0,
                        quantity: property.quantity || 1,
                        match_status: property.match_status,
                    });
                },

                removeProperty(property) {
                    this.$axios.delete('{{ route('admin.leads.product.remove', $lead->id) }}', {params: {product_id: property.product_id}})
                        .then(response => {
                            this.properties = this.properties.filter(item => item.product_id !== property.product_id);
                            this.$emitter.emit('add-flash', {type: 'success', message: response.data.message});
                        });
                },

                propertyUrl(id) {
                    return '{{ route('admin.products.view', ':id') }}'.replace(':id', id);
                },

                matchClass(status) {
                    return {
                        interested: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
                        viewed: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
                        favorite: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
                        rejected: 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                    }[status] || 'bg-blue-100 text-blue-800';
                },

                matchLabel(status) {
                    return {
                        interested: "@lang('admin::app.leads.view.property-matches.interested')",
                        viewed: "@lang('admin::app.leads.view.property-matches.viewed')",
                        favorite: "@lang('admin::app.leads.view.property-matches.favorite')",
                        rejected: "@lang('admin::app.leads.view.property-matches.rejected')",
                    }[status] || "@lang('admin::app.leads.view.property-matches.interested')";
                },
            },
        });
    </script>
@endPushOnce
