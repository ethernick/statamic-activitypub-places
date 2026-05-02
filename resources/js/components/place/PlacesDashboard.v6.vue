<template>
    <div class="places-dashboard">
        <header class="mb-6 flex items-center justify-between">
            <h1 class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Places Explorer
            </h1>
            <div class="flex gap-2">
                <button @click="createNewPlace" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-linear-to-b from-primary/90 to-primary hover:bg-primary-hover text-white disabled:opacity-60 disabled:text-white dark:disabled:text-white border border-primary-border shadow-ui-md inset-shadow-2xs inset-shadow-white/25 disabled:inset-shadow-none dark:disabled:inset-shadow-none [&_svg]:text-white [&_svg]:opacity-60 px-4 h-10 text-sm gap-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Place
                </button>
            </div>
        </header>

        <!-- Row 1: Full-width Map -->
        <div class="card p-0 overflow-hidden mb-8 shadow-sm border border-gray-200 dark:border-gray-800 relative">
            <div ref="mapContainer" class="w-full bg-gray-50 dark:bg-gray-950 h-350"></div>
        </div>

        <!-- Row 2: Selected Place Details & Stream -->
        <div v-if="selectedPlace" class="flex flex-col md:flex-row gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <!-- Details Column (1/3) -->
            <div class="w-full md:w-1/3 space-y-6">
                <div class="card p-6 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 shadow-sm h-full">
                    <div class="mb-6">
                        <div class="text-[10px] text-blue-500 font-black uppercase tracking-widest mb-1">{{ selectedPlace.is_internal ? 'Internal Entry' : 'Federated Entry' }}</div>
                        <h2 class="text-2xl font-bold leading-tight">{{ selectedPlace.title }}</h2>
                        <div v-if="selectedPlace.osm_name && selectedPlace.osm_name !== selectedPlace.title" class="text-xs text-gray-400 italic">{{ selectedPlace.osm_name }}</div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Full Address</label>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ selectedPlace.address }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Latitude</label>
                                <code class="text-[11px] font-mono text-gray-600 dark:text-gray-400">{{ selectedPlace.latitude.toFixed(6) }}</code>
                            </div>
                            <div>
                                <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Longitude</label>
                                <code class="text-[11px] font-mono text-gray-600 dark:text-gray-400">{{ selectedPlace.longitude.toFixed(6) }}</code>
                            </div>
                        </div>

                        <div class="pt-6">
                            <a :href="selectedPlace.edit_url" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-4 w-full h-10 text-sm rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Edit Entry Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Stream Column (2/3) -->
            <div class="w-full md:w-2/3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-gray-400">Related Activity Stream</h3>
                </div>
                
                <div v-if="loadingActivities" class="card p-12 flex flex-col items-center justify-center text-gray-400">
                    <span class="loading loading-lg mb-4"></span>
                    <p class="text-sm animate-pulse">Syncing related check-ins...</p>
                </div>

                <div v-else-if="activities.length === 0" class="card p-12 text-center text-gray-400 border-dashed border-2 border-gray-200 dark:border-gray-800">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    <p>No federation activities found for this location yet.</p>
                </div>

                <div v-else class="flex flex-col gap-4">
                    <div v-for="activity in activities" :key="activity.id" class="inbox-stream-item">
                        <div class="card p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start gap-4">
                                <template v-if="activity.actor">
                                    <img :src="activity.actor.avatar || 'https://www.gravatar.com/avatar/?d=mp'" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 object-cover flex-shrink-0">
                                </template>
                                <div v-else class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-sm truncate">{{ activity.actor_name || 'ActivityPub User' }}</span>
                                            <span v-if="activity.actor" class="text-[10px] text-gray-500 truncate">{{ activity.actor.handle }}</span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ activity.date_human || formatDate(activity.date) }}</span>
                                    </div>
                                    <div v-if="activity.content" class="text-sm text-gray-700 dark:text-gray-300 mt-2 prose prose-sm dark:prose-invert max-w-none" v-html="activity.content"></div>
                                    <div v-else class="text-sm text-gray-500 italic mt-2">
                                        {{ activity.activity_type === 'Arrive' ? 'Checked in' : 'Shared a location' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12 text-gray-400 animate-in fade-in duration-700">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
            <h2 class="text-xl font-bold text-gray-600 dark:text-gray-300">Select a Place to View Timeline</h2>
            <p class="text-sm">Click any pin on the map to explore local activity and metadata.</p>
        </div>

        <place-inbox-modals @submit-success="fetchPlaces" />
    </div>
</template>

<script>
import L from 'leaflet';
import PlaceInboxModals from './PlaceInboxModals.vue';

// Fix for Leaflet default icon issues in Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default {
    components: {
        PlaceInboxModals
    },
    data() {
        return {
            map: null,
            allPlaces: [],
            selectedPlace: null,
            activities: [],
            loadingActivities: false,
            loadingPlaces: false,
        }
    },
    mounted() {
        this.initMap();
        this.fetchPlaces();
        window.addEventListener('resize', this.invalidateSize);
        // Ensure map is properly sized after DOM settlement
        this.$nextTick(() => {
            this.resizeTimer = setTimeout(() => {
                this.invalidateSize();
            }, 200);
        });
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.invalidateSize);
        if (this.resizeTimer) clearTimeout(this.resizeTimer);
        
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    },
    methods: {
        async invalidateSize() {
            if (this.map && this.map._container) {
                this.map.invalidateSize();
            }
        },
        initMap() {
            if (!this.$refs.mapContainer) return;

            this.map = L.map(this.$refs.mapContainer, {
                center: [49.1685, -122.5850],
                zoom: 12,
                scrollWheelZoom: true,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

            // Dark mode support handled via CSS
        },
        fetchPlaces() {
            this.loadingPlaces = true;
            this.$axios.get(cp_url('activitypub/places/all'))
            .then(response => {
                if (!this.map) return;
                this.allPlaces = response.data.places;
                this.renderMarkers();
                this.autoZoom();
            })
            .finally(() => {
                this.loadingPlaces = false;
            });
        },
        renderMarkers() {
            if (!this.map) return;
            const blueIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            const greyIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41],
                className: 'marker-grey'
            });

            this.allPlaces.forEach(place => {
                const marker = L.marker([place.latitude, place.longitude], {
                    icon: place.is_internal ? blueIcon : greyIcon
                }).addTo(this.map);

                marker.on('click', () => {
                    this.selectPlace(place);
                });
            });
        },
        autoZoom() {
            if (!this.map || this.allPlaces.length === 0) return;
            const bounds = L.latLngBounds(this.allPlaces.map(p => [p.latitude, p.longitude]));
            this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        },
        selectPlace(place) {
            if (!this.map) return;
            this.selectedPlace = place;
            this.fetchActivities(place.id);
            this.map.panTo([place.latitude, place.longitude], { animate: true });
        },
        fetchActivities(id) {
            this.loadingActivities = true;
            this.activities = [];
            this.$axios.get(cp_url(`activitypub/places/${id}/activities`))
            .then(response => {
                this.activities = response.data.data;
            })
            .finally(() => {
                this.loadingActivities = false;
            });
        },
        createNewPlace() {
            if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
                Statamic.$activitypub.bus.emit('activitypub:inbox:create-place');
            }
        },
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>

<style scoped>
:deep(.marker-grey) {
    filter: saturate(0) brightness(1.4) opacity(0.8);
}
.places-dashboard :deep(.leaflet-tile-pane) {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
/* Revert filter for light mode if it was applied via dark class elsewhere */
:root:not(.dark) .places-dashboard :deep(.leaflet-tile-pane) {
    filter: none !important;
}
</style>
