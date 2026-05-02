<template>
    <div class="location-fieldtype">
        <div v-if="value && value.osm_id" class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border rounded shadow-sm mb-2">
            <div class="flex items-center min-w-0 mr-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded flex items-center justify-center mr-3 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-sm truncate">{{ value.title || 'Selected Place' }}</div>
                    <div class="text-[10px] text-gray-400 mt-0.5 uppercase tracking-tight">OSM {{ value.osm_type }}:{{ value.osm_id }}</div>
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <button @click="isSearching = true" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-3 h-8 text-xs rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700" title="Change Location">Change</button>
                <button @click="clear" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-red-50 text-red-500 border border-red-200 shadow-sm px-3 h-8 text-xs rounded-lg dark:bg-gray-800 dark:border-red-900/30 dark:hover:bg-red-900/50" title="Remove Location">&times;</button>
            </div>
        </div>

        <div v-else>
            <button @click="isSearching = true" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-linear-to-b from-primary/90 to-primary hover:bg-primary-hover text-white disabled:opacity-60 disabled:text-white dark:disabled:text-white border border-primary-border shadow-ui-md inset-shadow-2xs inset-shadow-white/25 disabled:inset-shadow-none dark:disabled:inset-shadow-none [&_svg]:text-white [&_svg]:opacity-60 w-full py-4 flex flex-col items-center justify-center gap-2 rounded-lg bg-transparent hover:bg-blue-50 dark:hover:bg-blue-900/10 text-gray-800 dark:text-white">
                <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Select a Location</span>
            </button>
        </div>

        <!-- Location Search Drawer (Adopt standard Core Stack) -->
        <inbox-stack 
            :open="isSearching" 
            title="Locate Place" 
            @closed="isSearching = false"
        >
            <div class="px-1">
                <p class="text-xs text-gray-500 mb-6">Find a location on OpenStreetMap.</p>
                <location-search @selected="handleSelected" />
            </div>
            
            <template #footer-end>
                <button @click="isSearching = false" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-4 h-10 text-sm rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
            </template>
        </inbox-stack>
    </div>
</template>

<script>
import LocationSearch from './LocationSearch.vue';

const Fieldtype = window.__STATAMIC__?.core?.FieldtypeMixin || (typeof window !== 'undefined' && window.Fieldtype) || {};

export default {
    mixins: [Fieldtype],
    components: {
        LocationSearch
    },
    data() {
        return {
            isSearching: false
        }
    },
    methods: {
        handleSelected(result) {
            const newValue = {
                osm_type: result.osm_type,
                osm_id: result.osm_id.toString(),
                latitude: parseFloat(result.lat),
                longitude: parseFloat(result.lon),
                address: result.display_name,
                // Force title to blank for manual points, otherwise use result name
                title: result.osm_type === 'point' ? '' : (result.name || result.display_name.split(',')[0]),
            };

            this.update(newValue);
            this.isSearching = false;

            // Automate sibling field population using Statamic's global Publish event bus
            const mapping = {
                'osm_type': newValue.osm_type,
                'osm_id': newValue.osm_id,
                'latitude': newValue.latitude,
                'longitude': newValue.longitude,
                'osm_name': newValue.title,
                'address': newValue.address
            };

            console.log('[ActivityPubPlaces] Syncing confirmed location data to form via multi-layered broadcast...', mapping);

            Object.entries(mapping).forEach(([handle, value]) => {
                // Multi-version compatibility approach for sibling sync
                let parent = this.$parent;
                while (parent) {
                    if (typeof parent.setFieldValue === 'function') {
                        parent.setFieldValue(handle, value);
                        break;
                    } else if (parent.values && typeof parent.values[handle] !== 'undefined') {
                        if (typeof this.$set === 'function') {
                            this.$set(parent.values, handle, value); // Vue 2 reactivity safety
                        } else {
                            parent.values[handle] = value; // Vue 3 native proxy
                        }
                        break;
                    }
                    parent = parent.$parent;
                }
            });

            // Signal to other components
            if (this.$events) {
                this.$events.$emit('location-selected', newValue);
            }
        },
        clear() {
            if (confirm('Are you sure you want to remove this location?')) {
                this.update(this.config.default || null);
            }
        }
    }
}
</script>
