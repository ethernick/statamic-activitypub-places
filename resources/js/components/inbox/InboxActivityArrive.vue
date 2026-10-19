<template>
    <div class="inbox-activity-arrive">
        <div 
            v-if="hasLocation"
            class="mt-3 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm"
        >
            <!-- Map header -->
            <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 min-w-0 mr-2">
                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                        {{ title }}
                    </span>
                </div>
                <map-directions-dropdown 
                    :location="location" 
                    label="Open Map" 
                    placement="bottom" 
                    variant="text" 
                />
            </div>
            <!-- Leaflet map container -->
            <div :id="mapId" style="height: 180px; width: 100%;"></div>
        </div>
        
        <!-- Summary/Content if any -->
        <div v-if="activity.content" class="mt-3 text-sm text-gray-600 dark:text-gray-400 prose dark:prose-invert max-w-none" v-html="activity.content"></div>
    </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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
            return (this.activity.latitude && this.activity.longitude) ? this.activity : (this.activity.location || {});
        },
        hasLocation() {
            return !!(this.location && this.location.latitude && this.location.longitude);
        },
        title() {
            return this.activity.title || this.location.name || this.location.osm_name || 'Arrival';
        },
        address() {
            return this.location.address || 'Unknown Location';
        },
        mapId() {
            const rawId = this.activity.id || this.activity.slug || (this.activity.activitypub_id ? this.activity.activitypub_id.replace(/[^a-zA-Z0-9_-]/g, '_') : Math.random().toString(36).substr(2, 9));
            return `inbox-arrive-map-${rawId}`;
        }
    },
    mounted() {
        if (!this.hasLocation) return;
        this.$nextTick(() => {
            setTimeout(() => this.initMap(), 150);
        });
    },
    beforeDestroy() {
        this.destroyMap();
    },
    beforeUnmount() {
        this.destroyMap();
    },
    methods: {
        initMap() {
            const container = document.getElementById(this.mapId);
            if (!container) return;

            if (this.map) this.destroyMap();

            const lat = parseFloat(this.location.latitude);
            const lng = parseFloat(this.location.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            this.map = L.map(container, {
                center: [lat, lng],
                zoom: 15,
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: true,
                dragging: true,
                doubleClickZoom: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
            L.marker([lat, lng]).addTo(this.map);

            setTimeout(() => {
                if (this.map && this.map._container) this.map.invalidateSize();
            }, 400);
        },
        destroyMap() {
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        }
    }
}
</script>
