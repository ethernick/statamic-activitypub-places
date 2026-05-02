<template>
    <div v-if="shouldRender" class="mt-4 place-summary">
        <div 
            class="group relative border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all cursor-pointer"
            @click="openMapSelector"
        >
            <!-- Map Container -->
            <div ref="mapContainer" class="h-48 w-full z-0 bg-gray-50 dark:bg-gray-950"></div>
            
            <!-- Overlay Info -->
            <div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-black/80 via-black/40 to-transparent pointer-events-none transition-all group-hover:from-black/90">
                <div class="flex items-end justify-between">
                    <div class="min-w-0 flex-1 mr-4">
                        <h4 class="text-white font-bold truncate leading-tight group-hover:text-blue-200 transition-colors">{{ note.title || note.osm_name }}</h4>
                        <p class="text-white/60 text-[11px] truncate opacity-0 group-hover:opacity-100 transition-opacity duration-300">{{ note.address }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white border border-white/20 shadow-xl group-hover:scale-110 group-hover:bg-blue-500/20 transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Launcher Icon Top Right -->
            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-[-10px] group-hover:translate-y-0">
                <div class="relative inline-flex items-center justify-center whitespace-nowrap font-bold antialiased border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 px-3 h-8 text-[10px] gap-2 rounded-lg shadow-lg uppercase tracking-tight">
                    <span>Open in Maps</span>
                    <svg class="w-3 h-3 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import L from 'leaflet';

export default {
    props: {
        note: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            map: null
        }
    },
    computed: {
        shouldRender() {
            return this.note && (this.note.collection === 'places' || (this.note.latitude && this.note.longitude));
        }
    },
    mounted() {
        if (this.shouldRender) {
            this.$nextTick(() => {
                this.initMap();
            });
        }
    },
    beforeUnmount() {
        if (this.map) {
            this.map.remove();
        }
    },
    methods: {
        initMap() {
            if (!this.$refs.mapContainer) return;

            const lat = parseFloat(this.note.latitude);
            const lng = parseFloat(this.note.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            this.map = L.map(this.$refs.mapContainer, {
                center: [lat, lng],
                zoom: 14,
                dragging: false,
                touchZoom: false,
                doubleClickZoom: false,
                scrollWheelZoom: false,
                boxZoom: false,
                keyboard: false,
                zoomControl: false,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

            const icon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            L.marker([lat, lng], { icon }).addTo(this.map);
        },
        openMapSelector() {
            if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
                Statamic.$activitypub.bus.emit('activitypub:inbox:select-map-service', this.note);
            }
        }
    }
}
</script>

<style scoped>
.place-summary :deep(.leaflet-container) {
    background: #f8f9fa !important;
}
.place-summary :deep(.leaflet-tile-pane) {
    filter: grayscale(0.2) contrast(1.1);
}
html.dark .place-summary :deep(.leaflet-tile-pane),
html.is-dark .place-summary :deep(.leaflet-tile-pane) {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
</style>
