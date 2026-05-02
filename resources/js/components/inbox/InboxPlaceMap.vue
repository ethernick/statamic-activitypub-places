<template>
    <div
        v-if="note.location && note.location.latitude && note.location.longitude"
        class="mt-3 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm"
    >
        <!-- Map header -->
        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <a
                :href="osmUrl"
                target="_blank"
                rel="noopener"
                class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline truncate"
            >
                {{ note.location.name || 'View on OpenStreetMap' }}
            </a>
        </div>
        <!-- Leaflet map container — explicit height/width ensures Leaflet has dimensions -->
        <div :id="mapId" style="height: 180px; width: 100%;"></div>
    </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export default {
    name: 'InboxPlaceMap',
    props: {
        note: {
            type: Object,
            required: true
        }
    },
    data() {
        return { map: null };
    },
    computed: {
        mapId() {
            return `inbox-place-map-${this.note.id}`;
        },
        osmUrl() {
            const { latitude: lat, longitude: lon } = this.note.location;
            return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=17/${lat}/${lon}`;
        }
    },
    mounted() {
        if (!this.note.location?.latitude) return;
        // Defer initialization: let the DOM fully paint and any parent
        // CSS transitions complete before Leaflet measures the container.
        this.$nextTick(() => {
            setTimeout(() => this.initMap(), 150);
        });
    },
    beforeDestroy() {
        // Vue 2
        this.destroyMap();
    },
    beforeUnmount() {
        // Vue 3
        this.destroyMap();
    },
    methods: {
        initMap() {
            const container = document.getElementById(this.mapId);
            if (!container) return;

            // Guard against double-init if watch triggers again
            if (this.map) this.destroyMap();

            const { latitude: lat, longitude: lon } = this.note.location;

            this.map = L.map(container, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: true,
                dragging: true,
                doubleClickZoom: true
            }).setView([lat, lon], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
            L.marker([lat, lon]).addTo(this.map);

            // Second invalidateSize pass after tiles begin loading
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
