<template>
    <div class="inbox-activity-arrive">
        <div 
            class="group relative border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-all cursor-pointer"
            @click="openMapSelector"
        >
            <!-- Map Container -->
            <div ref="mapContainer" class="h-48 w-full z-0 bg-gray-50 dark:bg-gray-950"></div>
            
            <!-- Overlay Info -->
            <div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-black/80 via-black/40 to-transparent pointer-events-none transition-all">
                <div class="flex items-end justify-between">
                    <div class="min-w-0 flex-1 mr-4">
                        <h4 class="text-white font-bold truncate leading-tight">{{ title }}</h4>
                        <p class="text-white/60 text-[11px] truncate">{{ address }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white border border-white/20 shadow-xl transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Launcher Icon Top Right -->
            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-300">
                <div class="relative inline-flex items-center justify-center whitespace-nowrap font-bold antialiased border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 px-3 h-8 text-[10px] gap-2 rounded-lg shadow-lg uppercase tracking-tight">
                    <span>Open in Maps</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
            </div>
        </div>
        
        <!-- Summary/Content if any -->
        <!--div v-if="activity.content" class="mt-3 text-sm text-gray-600 dark:text-gray-400 prose dark:prose-invert max-w-none" v-html="activity.content"></div-->
    </div>
</template>

<script>
import L from 'leaflet';

export default {
    name: 'InboxActivityArrive',
    props: {
        activity: {
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
        location() {
            // Arrive object often has latitude/longitude directly or in activity object
            // Use the activity itself if it has lat/lon, or its location object
            return (this.activity.latitude && this.activity.longitude) ? this.activity : (this.activity.location || {});
        },
        title() {
            return this.activity.title || this.location.name || this.location.osm_name || 'Arrival';
        },
        address() {
            return this.location.address || 'Unknown Location';
        }
    },
    mounted() {
        this.$nextTick(() => this.initMap());
    },
    beforeDestroy() {
        if (this.map) {
            this.map.remove();
        }
    },
    methods: {
        initMap() {
            if (!this.$refs.mapContainer) return;

            const lat = parseFloat(this.location.latitude);
            const lng = parseFloat(this.location.longitude);

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
                shadowSize: [41, 41]
            });

            L.marker([lat, lng], { icon }).addTo(this.map);
        },
        openMapSelector() {
            if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
                // Pass the location data to the selector
                Statamic.$activitypub.bus.emit('activitypub:inbox:select-map-service', this.location);
            }
        }
    }
}
</script>

<style scoped>
.inbox-activity-arrive >>> .leaflet-tile-pane {
    filter: grayscale(0.2) contrast(1.1);
}
html.dark .inbox-activity-arrive >>> .leaflet-tile-pane,
html.is-dark .inbox-activity-arrive >>> .leaflet-tile-pane {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
</style>
