<template>
    <div v-if="open" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <h3 class="text-lg font-bold mb-1">Open in Maps</h3>
                <p class="text-sm text-gray-500 mb-6 truncate" :title="place.address">{{ place.osm_name || place.title }}</p>
                
                <div class="grid gap-2">
                    <button v-for="service in services" :key="service.name" 
                        @click="openMap(service)"
                        class="relative flex items-center gap-4 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-all group text-left w-full"
                    >
                        <div class="w-10 h-10 rounded shadow-inner flex items-center justify-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                            <div v-html="service.iconSvg" class="w-6 h-6 flex items-center justify-center" :style="{ color: service.color }"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-gray-800 dark:text-gray-100">{{ service.name }}</div>
                            <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ service.description }}</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/30 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                <button @click="$emit('close')" class="relative inline-flex items-center justify-center whitespace-nowrap font-medium antialiased cursor-pointer no-underline bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-4 h-9 text-xs rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
            </div>
        </div>
    </div>
</template>

<script>
import { getOsmUrl } from '../../mapUtils';

export default {
    props: {
        open: Boolean,
        place: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            services: [
                {
                    name: 'Google Maps',
                    description: 'Directions & Street View',
                    color: '#4285F4',
                    iconSvg: '<svg fill="currentColor" viewBox="0 0 24 24" width="24" height="24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
                    url: (p) => `https://www.google.com/maps/search/?api=1&query=${p.latitude},${p.longitude}`
                },
                {
                    name: 'Apple Maps',
                    description: 'Native iOS & macOS experience',
                    color: '#007AFF',
                    iconSvg: '<svg fill="currentColor" viewBox="0 0 24 24" width="24" height="24"><path d="M15 11.46V2.46c0-.55-.45-1-1-1s-1 .45-1 1v9h-2v-7c0-.55-.45-1-1-1s-1 .45-1 1v7H7v-5c0-.55-.45-1-1-1s-1 .45-1 1v5c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2z"/></svg>',
                    url: (p) => `https://maps.apple.com/?ll=${p.latitude},${p.longitude}&q=${encodeURIComponent(p.osm_name || p.title || 'Location')}`
                },
                {
                    name: 'OpenStreetMap',
                    description: 'Community-driven Wiki map',
                    color: '#7EBC6F',
                    iconSvg: '<svg fill="currentColor" viewBox="0 0 24 24" width="24" height="24"><path d="M15 21l-6-2.12-5.45 2.73a.5.5 0 01-.73-.44v-14a.5.5 0 01.28-.45L9 4l6 2.12 5.45-2.73a.5.5 0 01.73.44v14a.5.5 0 01-.28.45L15 21zM9.54 18.25l4.92 1.74V6.51l-4.92-1.74v13.48z"/></svg>',
                    url: (p) => getOsmUrl(p)
                }
            ]
        }
    },
    methods: {
        openMap(service) {
            const url = service.url(this.place);
            window.open(url, '_blank', 'noopener,noreferrer');
            this.$emit('close');
        }
    }
}
</script>
