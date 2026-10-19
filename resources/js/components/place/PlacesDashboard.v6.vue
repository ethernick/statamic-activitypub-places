<template>
    <div class="w-full min-w-0 mx-auto max-w-page max-[1220px]:mb-18 places-dashboard">
        <!-- Page Header -->
        <header class="relative flex flex-wrap items-center justify-between gap-4 px-2 sm:px-0 py-6 max-md:pb-8 md:py-8">
            <h1 class="text-[25px] leading-[1.25] st-text-legibility font-medium antialiased flex items-center gap-2.5 md:flex-1">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Places Explorer</span>
            </h1>
            <div class="flex items-center gap-2">
                <button @click="createNewPlace" class="btn btn-primary flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Place
                </button>
            </div>
        </header>

        <!-- Row 1: Full-width Map Panel -->
        <div class="@container/panel relative bg-gray-150 dark:bg-gray-950/35 dark:inset-shadow-2xs dark:inset-shadow-black w-full rounded-2xl max-[600px]:p-1.25 p-1.75 [&:has(>[data-ui-panel-header])]:pt-0 focus-none starting-style-transition mb-8">
            <header data-ui-panel-header class="px-4.5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <h2 class="font-bold text-gray-900 dark:text-gray-100">Places Map</h2>
                    <span v-if="allPlaces.length > 0" class="text-xs text-gray-500 font-medium bg-gray-100 dark:bg-gray-900 px-2.5 py-0.5 rounded-full border border-gray-200 dark:border-gray-800">
                        {{ allPlaces.length }} locations
                    </span>
                </div>
                <button v-if="selectedPlace" @click="selectedPlace = null" class="btn btn-default text-xs py-1 px-2.5">
                    Clear Selection
                </button>
            </header>
            <div class="bg-white dark:bg-gray-850 rounded-xl ring ring-gray-200 dark:ring-x-0 dark:ring-b-0 dark:ring-gray-700/80 shadow-ui-md overflow-hidden p-0 relative">
                <div ref="mapContainer" class="w-full bg-gray-50 dark:bg-gray-950 h-350 z-0"></div>
            </div>
        </div>

        <!-- Row 2: Selected Place Details & Stream -->
        <div v-if="selectedPlace" class="flex flex-col md:flex-row gap-8 items-start animate-in fade-in slide-in-from-bottom-4 duration-500">
            <!-- Details Column (1/3) -->
            <div class="w-full md:w-1/3">
                <div class="@container/panel relative bg-gray-150 dark:bg-gray-950/35 dark:inset-shadow-2xs dark:inset-shadow-black w-full rounded-2xl max-[600px]:p-1.25 p-1.75 [&:has(>[data-ui-panel-header])]:pt-0 focus-none starting-style-transition h-full flex flex-col">
                    <header data-ui-panel-header class="px-4.5 py-3 flex items-center justify-between">
                        <h2 class="font-bold text-gray-900 dark:text-gray-100">Place Details</h2>
                    </header>
                    <div class="bg-white dark:bg-gray-850 rounded-xl ring ring-gray-200 dark:ring-x-0 dark:ring-b-0 dark:ring-gray-700/80 shadow-ui-md px-5 py-5 flex-1 flex flex-col justify-between space-y-6">
                        <div>
                            <div class="mb-4">
                                <h3 class="text-xl font-bold leading-tight text-gray-900 dark:text-gray-100">{{ selectedPlace.title }}</h3>
                                <div v-if="selectedPlace.osm_name && selectedPlace.osm_name !== selectedPlace.title" class="text-xs text-gray-400 italic mt-0.5">{{ selectedPlace.osm_name }}</div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Full Address</label>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ selectedPlace.address }}</p>
                                </div>

                                <div v-if="selectedPlace.osm_type && selectedPlace.osm_id">
                                    <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">OpenStreetMap</label>
                                    <a :href="getOsmUrl(selectedPlace)" 
                                       target="_blank" 
                                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-medium"
                                    >
                                        <span>{{ selectedPlace.osm_type }}/{{ selectedPlace.osm_id }}</span>
                                        <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <div>
                                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Latitude</label>
                                        <code class="text-[11px] font-mono text-gray-600 dark:text-gray-400">{{ selectedPlace.latitude.toFixed(6) }}</code>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Longitude</label>
                                        <code class="text-[11px] font-mono text-gray-600 dark:text-gray-400">{{ selectedPlace.longitude.toFixed(6) }}</code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                            <a :href="selectedPlace.edit_url" class="btn w-full justify-center text-xs font-semibold">Edit Entry Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Stream Column (2/3) - Header title removed for streamlined look -->
            <div class="w-full md:w-2/3">
                <div class="@container/panel relative bg-gray-150 dark:bg-gray-950/35 dark:inset-shadow-2xs dark:inset-shadow-black w-full rounded-2xl max-[600px]:p-1.25 p-1.75 focus-none starting-style-transition h-full flex flex-col">
                    <div class="bg-white dark:bg-gray-850 rounded-xl ring ring-gray-200 dark:ring-x-0 dark:ring-b-0 dark:ring-gray-700/80 shadow-ui-md p-0 overflow-hidden flex-1 flex flex-col">
                        <div v-if="loadingActivities" class="flex-1 flex flex-col items-center justify-center py-16 text-gray-400">
                            <span class="loading loading-lg mb-4"></span>
                            <p class="text-sm animate-pulse">Syncing related activity...</p>
                        </div>

                        <div v-else-if="activities.length === 0" class="flex-1 flex flex-col items-center justify-center py-16 text-center text-gray-400 p-6">
                            <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            <p class="text-sm font-semibold">No federation activities found for this location yet.</p>
                        </div>

                        <div v-else class="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                            <activitypub-post-card 
                                v-for="activity in activities" 
                                :key="activity.id" 
                                :item="activity" 
                                :readonly="true" 
                                :actors="actors"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state when no place selected -->
        <div v-else class="@container/panel relative bg-gray-150 dark:bg-gray-950/35 dark:inset-shadow-2xs dark:inset-shadow-black w-full rounded-2xl max-[600px]:p-1.25 p-1.75 [&:has(>[data-ui-panel-header])]:pt-0 focus-none starting-style-transition">
            <header data-ui-panel-header class="px-4.5 py-3 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-gray-100">Timeline & Activity</h2>
            </header>
            <div class="bg-white dark:bg-gray-850 rounded-xl ring ring-gray-200 dark:ring-x-0 dark:ring-b-0 dark:ring-gray-700/80 shadow-ui-md text-center py-16 px-4">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-1">Select a Place to View Timeline</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto">Click any location pin on the map to explore local check-ins, check-out activity, and metadata.</p>
            </div>
        </div>

        <activitypub-modals :actors="actors" @submit-success="fetchPlaces" />
    </div>
</template>

<script>
import L from 'leaflet';
import { getOsmUrl } from '../../mapUtils';

export default {
    props: {
        actors: {
            type: Array,
            default: () => []
        }
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
        }
    },
    methods: {
        getOsmUrl,
        invalidateSize() {
            if (this.map) {
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
        },
        fetchPlaces() {
            this.loadingPlaces = true;
            this.$axios.get(cp_url('activitypub/places/all'))
            .then(response => {
                this.allPlaces = response.data.places;
                this.renderMarkers();
                this.autoZoom();
            })
            .finally(() => {
                this.loadingPlaces = false;
            });
        },
        renderMarkers() {
            // Leaflet default icon fix
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
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
                    icon: place.is_internal ? new L.Icon.Default() : greyIcon
                }).addTo(this.map);

                marker.on('click', () => {
                    this.selectPlace(place);
                });
            });
        },
        autoZoom() {
            if (this.allPlaces.length > 0) {
                const bounds = L.latLngBounds(this.allPlaces.map(p => [p.latitude, p.longitude]));
                this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        },
        selectPlace(place) {
            this.selectedPlace = place;
            this.fetchActivities(place.id);
            
            // Pan smoothly
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
        },
        getActivityVerb(activity) {
            if (activity.activity_type === 'Like') return 'liked';
            if (activity.activity_type === 'Announce') return 'boosted';
            if (activity.activity_type === 'Follow') return 'followed';
            if (activity.activity_type === 'Arrive') return 'checked in at';
            return (activity.activity_type || 'activity').toLowerCase();
        },
        formatEventTime(time) {
            if (!time) return '';
            try {
                const d = new Date(time);
                return d.toLocaleDateString(undefined, { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            } catch (e) {
                return time;
            }
        }
    }
}
</script>

<style scoped>
.marker-grey {
    filter: saturate(0) brightness(1.4) opacity(0.8);
}
.places-dashboard :deep(.leaflet-tile-pane) {
    filter: none;
}
html.dark .places-dashboard :deep(.leaflet-tile-pane),
html.is-dark .places-dashboard :deep(.leaflet-tile-pane) {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
.truncate-3-lines {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
