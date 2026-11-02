<template>
    <div
        v-if="shouldRender"
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
                    {{ locationTitle }}
                </span>
            </div>
            <map-directions-dropdown 
                :location="resolvedLocation" 
                label="Open Map" 
                placement="bottom" 
                variant="text" 
            />
        </div>
        <!-- Leaflet map container — explicit height/width ensures Leaflet has dimensions -->
        <div :id="mapId" style="height: 180px; width: 100%;"></div>
    </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { getOsmUrl } from '../../mapUtils';

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
        resolvedLocation() {
            if (!this.note) return null;
            if (this.note.location && this.note.location.latitude && this.note.location.longitude) {
                return this.note.location;
            }
            if (this.note.latitude && this.note.longitude) {
                return this.note;
            }
            return null;
        },
        locationTitle() {
            const loc = this.resolvedLocation;
            if (!loc) return 'Location';
            return loc.name || loc.title || this.note.title || this.note.osm_name || 'Location';
        },
        shouldRender() {
            // Guard: don't render on events or Arrive activities (Arrive handles its own presentation)
            if (this.note.event || this.note.activity_type === 'Arrive') {
                return false;
            }
            return !!(this.resolvedLocation && this.resolvedLocation.latitude && this.resolvedLocation.longitude);
        },
        mapId() {
            const rawId = this.note.id || this.note.slug || (this.note.activitypub_id ? this.note.activitypub_id.replace(/[^a-zA-Z0-9_-]/g, '_') : Math.random().toString(36).substr(2, 9));
            return `inbox-place-map-${rawId}`;
        },
        osmUrl() {
            return getOsmUrl(this.resolvedLocation);
        }
    },
    mounted() {
        if (!this.shouldRender) return;
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

            if (!this.resolvedLocation) return;
            const lat = parseFloat(this.resolvedLocation.latitude);
            const lon = parseFloat(this.resolvedLocation.longitude);
            if (isNaN(lat) || isNaN(lon)) return;

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
