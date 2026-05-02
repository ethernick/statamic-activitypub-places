<template>
    <div class="location-search">
        <!-- Search Input -->
        <div class="flex items-center mb-4">
            <div class="relative flex-1">
                <input 
                    type="text" 
                    v-model="query" 
                    @keydown.enter.prevent="search"
                    class="input-text pr-10" 
                    placeholder="Search for a city, street, or landmark..."
                    :disabled="loading"
                >
                <div v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <span class="loading loading-xs"></span>
                </div>
            </div>
            <button @click="search" class="btn ml-2" :disabled="loading || !query">
                Search
            </button>
            <button @click="useCurrentLocation" class="btn ml-2" title="Use current location">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
        </div>

        <div v-if="error" class="alert alert-danger mb-4 text-sm py-2">
            {{ error }}
        </div>

        <!-- Use Grid for more predictable sizing across CP contexts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Results List -->
            <div class="results-container border rounded bg-white dark:bg-gray-800 relative flex flex-col" style="height: 400px; min-height: 400px;">
                <div class="flex-1 overflow-y-auto">
                    <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex flex-col items-center justify-center p-8 text-center text-gray-500">
                        <span class="loading loading-sm mb-2"></span>
                        <p>Searching OpenStreetMap...</p>
                    </div>

                    <div v-if="results.length === 0 && !loading" class="p-8 text-center text-gray-500 h-full flex flex-col items-center justify-center">
                        <div v-if="!hasSearched">
                            <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            <p>Type a place name to begin.</p>
                        </div>
                        <p v-else>No results found for "{{ query }}".</p>
                    </div>

                    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li 
                            v-for="result in results" 
                            :key="result.place_id"
                            @click="selectResult(result)"
                            :class="[
                                'p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all border-l-4',
                                result.is_exact ? 'border-teal-500' : 'border-blue-500',
                                { 'bg-gray-50 dark:bg-gray-700/50 shadow-inner': selectedResult && selectedResult.place_id === result.place_id }
                            ]"
                        >
                            <div class="font-medium text-sm">{{ result.display_name }}</div>
                            <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">{{ result.type }} · {{ result.category }}</div>
                        </li>
                    </ul>
                </div>
                
                <!-- Explicit selected bar outside the map but tied to the layout -->
                <div v-if="selectedResult" class="p-3 bg-blue-50 dark:bg-blue-900/20 border-t flex justify-between items-center transition-all duration-300">
                    <div class="flex-1 min-w-0 mr-3">
                        <div class="text-[9px] text-blue-600 dark:text-blue-400 font-black uppercase tracking-widest leading-none mb-1">Selected Location</div>
                        <div class="text-xs font-bold truncate">{{ selectedResult.name || selectedResult.display_name.split(',')[0] }}</div>
                    </div>
                    <button @click="confirmSelection" class="btn btn-primary btn-sm px-4">
                        Confirm
                    </button>
                </div>
            </div>

            <!-- Map Preview -->
            <div class="map-preview-container relative rounded border overflow-hidden bg-gray-100 flex flex-col" style="height: 400px; min-height: 400px;">
                <div ref="mapContainer" class="flex-1 z-0 bg-[#f8f9fa] dark:bg-[#111111]"></div>
                
                <!-- Map Overlay Placeholder -->
                <div v-if="!selectedResult" class="absolute inset-0 flex items-center justify-center bg-gray-50/50 dark:bg-gray-900/50 z-10 backdrop-blur-[1px] pointer-events-none">
                    <div class="text-center p-4">
                        <span class="text-sm font-semibold text-gray-600 bg-white/90 dark:bg-gray-800/90 px-3 py-1.5 rounded shadow-sm border border-gray-200 dark:border-gray-700">
                            Select a result to preview on map
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import L from 'leaflet';

// Fix for Leaflet default icon issues
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default {
    props: {
        searchUrl: {
            type: String,
            default: () => cp_url('activitypub/places/search')
        }
    },
    data() {
        return {
            query: '',
            results: [],
            loading: false,
            hasSearched: false,
            selectedResult: null,
            error: null,
            // Non-reactive storage for map objects
            // map: null,
            // marker: null,
            // geometryLayer: null,
        }
    },
    mounted() {
        this.initMap();
        window.addEventListener('resize', this.invalidateSize);
        // Ensure map is properly sized after DOM settlement
        this.$nextTick(() => {
            this.resizeTimers = [
                setTimeout(() => this.invalidateSize(), 200),
                setTimeout(() => this.invalidateSize(), 1000)
            ];
        });
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.invalidateSize);
        if (this.resizeTimers) {
            this.resizeTimers.forEach(t => clearTimeout(t));
        }
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    },
    methods: {
        invalidateSize() {
            if (this.map && this.map._container) {
                this.map.invalidateSize();
            }
        },
        initMap() {
            if (!this.$refs.mapContainer) return;
            
        this.map = L.map(this.$refs.mapContainer, {
            zoomControl: true,
            dragging: true
        }).setView([0, 0], 2);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(this.map);

        this.marker = null;
        this.geometryLayer = null;
        this.discoveryMarkers = L.layerGroup().addTo(this.map);

        // Add click listener for manual pin placement
        this.map.on('click', (e) => this.onMapClick(e));
        },
        onMapClick(e) {
            console.log('[ActivityPubPlaces] Map clicked at:', e.latlng);
            const { lat, lng } = e.latlng;
            this.reverseGeocode(lat, lng);
        },
        search() {
            if (this.loading || !this.query) return;
            
            this.loading = true;
            this.error = null;
            this.hasSearched = true;
            this.results = [];
            
            this.$axios.get(this.searchUrl, {
                params: { q: this.query }
            })
            .then(response => {
                this.results = response.data.results;
                this.renderDiscoveryMarkers();
                if (this.results.length === 1) {
                    this.selectResult(this.results[0], true);
                }
            })
            .catch(err => {
                this.error = 'Unable to find that location. Check your internet connection or try a different search.';
            })
            .finally(() => {
                this.loading = false;
                this.$nextTick(this.invalidateSize);
            });
        },
        useCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }

            this.loading = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    this.reverseGeocode(latitude, longitude);
                },
                (err) => {
                    this.error = 'Failed to get current location: ' + err.message;
                    this.loading = false;
                }
            );
        },
        reverseGeocode(lat, lon) {
            this.loading = true;
            this.error = null;
            this.hasSearched = true;
            
            // Show temporary marker immediately while waiting for server
            this.updateMarkerAndGeometry({ lat, lon }, false);
            this.map.panTo([lat, lon]);

            this.$axios.get(cp_url('activitypub/places/reverse'), {
                params: { lat, lon }
            })
            .then(response => {
                this.results = response.data.results || [];
                console.log(`[ActivityPubPlaces] Discovery complete. Found ${this.results.length} candidates.`, this.results);
                
                if (this.results.length > 0) {
                    this.renderDiscoveryMarkers();
                    // Select the first result (usually the Exact Coordinate Point)
                    this.selectResult(this.results[0], false);
                }
            })
            .catch(err => {
                this.error = 'Unable to identify this location. You can still try searching for it specifically.';
            })
            .finally(() => {
                this.loading = false;
                this.$nextTick(this.invalidateSize);
            });
        },
        renderDiscoveryMarkers() {
            this.discoveryMarkers.clearLayers();
            
            this.results.forEach((result, index) => {
                const lat = parseFloat(result.lat);
                const lon = parseFloat(result.lon);
                const number = index + 1;
                
                // Create a colored div icon (No numbers)
                const icon = L.divIcon({
                    className: 'discovery-marker-container',
                    html: `<div class="discovery-marker ${result.is_exact ? 'is-exact' : ''}">
                             <div class="pin"></div>
                           </div>`,
                    iconSize: [30, 42],
                    iconAnchor: [15, 42]
                });

                const m = L.marker([lat, lon], { icon })
                    .on('click', (e) => {
                        L.DomEvent.stopPropagation(e);
                        this.selectResult(result, false);
                    })
                    .addTo(this.discoveryMarkers);
                
                m.bindTooltip(result.name || result.display_name, { direction: 'top', offset: [0, -40] });
            });
        },
        selectResult(result, shouldZoom = true) {
            this.selectedResult = result;
            const lat = parseFloat(result.lat);
            const lon = parseFloat(result.lon);
            
            this.updateMarkerAndGeometry(result, shouldZoom);

            this.$nextTick(() => {
                if (!this.resizeTimers) this.resizeTimers = [];
                this.resizeTimers.push(setTimeout(() => this.invalidateSize(), 100));
            });
        },
        updateMarkerAndGeometry(result, shouldZoom = true) {
            const geojson = result?.geojson;
            const lat = parseFloat(result?.lat);
            const lon = parseFloat(result?.lon);

            // Clear existing layers
            if (this.geometryLayer) this.map.removeLayer(this.geometryLayer);

            // Update discovery marker styling to highlight the selected one
            this.discoveryMarkers.eachLayer((layer) => {
                const element = layer.getElement();
                if (!element) return;
                
                // Compare coordinates to find matching marker
                const latMatch = Math.abs(layer.getLatLng().lat - lat) < 0.000001;
                const lonMatch = Math.abs(layer.getLatLng().lng - lon) < 0.000001;
                const isSelected = latMatch && lonMatch;
                
                const markerDiv = element.querySelector('.discovery-marker');
                if (markerDiv) {
                    markerDiv.classList.toggle('selected', isSelected);
                }
                
                if (isSelected) {
                    layer.setZIndexOffset(1000);
                } else {
                    layer.setZIndexOffset(0);
                }
            });

            if (geojson && geojson.type !== 'Point') {
                // Render polygon/geometry as a subtle hint
                this.geometryLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#3b82f6', // Tailwind blue-500
                        weight: 2,
                        opacity: 0.4, // Subtler
                        fillColor: '#3b82f6',
                        fillOpacity: 0.1, // Very subtle
                        dashArray: '5, 5' // Dashed to look less like "directions"
                    }
                }).addTo(this.map);
                
                if (shouldZoom) {
                    // Fit map to boundary if explicitly requested (e.g. from search)
                    this.map.fitBounds(this.geometryLayer.getBounds(), { padding: [20, 20] });
                }
            }
            
            if (shouldZoom && (!geojson || geojson.type === 'Point')) {
                this.map.setView([lat, lon], 15);
            } else if (!shouldZoom) {
                // Just pan to center without changing zoom
                this.map.panTo([lat, lon]);
            }
        },
        confirmSelection() {
            if (!this.selectedResult) return;
            this.$emit('selected', this.selectedResult);
        }
    }
}
</script>

<style scoped>
.location-search {
    width: 100%;
}
.loading-xs {
    width: 1rem;
    height: 1rem;
}
.map-preview-container :deep(.leaflet-container),
.map-preview-container .leaflet-container {
    cursor: crosshair !important;
}

/* Numbered Discovery Markers */
:deep(.discovery-marker-container) {
    background: transparent;
    border: none;
}
:deep(.discovery-marker) {
    position: relative;
    width: 30px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
:deep(.discovery-marker .pin) {
    position: absolute;
    top: 0;
    left: 0;
    width: 30px;
    height: 42px;
    background-image: url('https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png');
    background-size: contain;
    background-repeat: no-repeat;
    filter: saturate(0.5) brightness(1.2);
    transition: all 0.2s;
}
:deep(.discovery-marker.is-exact .pin) {
    filter: hue-rotate(140deg) saturate(1.2); /* Green/Teal for Exact Point */
}
:deep(.discovery-marker.selected) {
    transform: scale(1.25) translateY(-4px);
    z-index: 1000;
}
:deep(.discovery-marker.selected .pin) {
    filter: saturate(1.5) contrast(1.2);
    drop-shadow: 0 10px 15px rgba(0,0,0,0.3);
}
</style>
