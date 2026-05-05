<template>
    <inbox-stack 
        :open="open" 
        title="New Check-In" 
        @closed="handleClosed"
    >
        <div class="px-1">
            <!-- Phase 1: Search -->
            <div v-show="!selected">
                <p class="text-xs text-gray-500 mb-6">Search for a place to check-in. You can search existing places or find new ones on OpenStreetMap.</p>
                <location-search 
                    :search-url="findUrl"
                    @selected="handleSelected" 
                />
            </div>

            <!-- Phase 2: Check-in Details -->
            <transition name="slide-up">
                <div v-if="selected" class="mt-4 space-y-6">
                    <!-- Location Brief -->
                    <div class="p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-900/30">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold truncate text-blue-900 dark:text-blue-300">
                                    {{ selected.name }}
                                    <span v-if="selected.is_internal" class="ml-1 text-[10px] bg-blue-100 text-blue-600 px-1 py-0.5 rounded uppercase tracking-tighter">Internal</span>
                                </div>
                                <div class="text-[10px] text-gray-500 truncate">{{ selected.display_name || selected.address }}</div>
                            </div>
                            <button @click="selected = null" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-2 text-[10px] h-6 rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Change</button>
                        </div>

                        <!-- Map Preview (display only, server handles image generation) -->
                        <div class="h-48 w-full rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-inner">
                            <div ref="mapPreviewContainer" class="h-full w-full"></div>
                        </div>
                    </div>

                    <!-- Actor Selection -->
                    <div v-if="actors.length > 0">
                        <label class="block text-sm font-bold mb-2">Post As</label>
                        <select v-model="form.actor" class="input-text w-full rounded-lg">
                            <option v-for="actor in actors" :key="actor.id" :value="actor.id">{{ actor.name }} ({{ actor.handle }})</option>
                        </select>
                    </div>

                    <!-- Content -->
                    <div class="flex flex-col">
                        <label class="block font-bold text-xs mb-1">Check-in Message</label>
                        <div class="min-h-[200px]">
                            <markdown-fieldtype
                                :value="form.content"
                                :model-value="form.content"
                                :config="{
                                    cheatsheet: true,
                                    container: 'assets',
                                    buttons: ['bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'link', 'image', 'table'],
                                    type: 'markdown'
                                }"
                                :meta="{ previewUrl: null }"
                                @input="val => form.content = val"
                                @update:modelValue="val => form.content = val"
                                @update:value="val => form.content = val"
                            />
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="font-bold text-xs mb-1 block">Tags (comma separated)</label>
                        <input v-model="tagsInput" type="text" class="input-text w-full rounded-lg" placeholder="e.g. coffee, morning, travel" />
                    </div>
                </div>
            </transition>
        </div>

        <template #footer-end>
            <button @click="handleClosed" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-4 h-10 text-sm rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>
            <button @click="save" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-linear-to-b from-primary/90 to-primary hover:bg-primary-hover text-white disabled:opacity-60 disabled:text-white dark:disabled:text-white border border-primary-border shadow-ui-md inset-shadow-2xs inset-shadow-white/25 disabled:inset-shadow-none dark:disabled:inset-shadow-none [&_svg]:text-white [&_svg]:opacity-60 px-4 h-10 text-sm gap-2 rounded-lg ml-2" :disabled="!selected || saving">
                <span v-if="saving" class="loading loading-xs mr-2"></span>
                Post Check-In
            </button>
        </template>
    </inbox-stack>
</template>

<script>
import LocationSearch from '../location/LocationSearch.v6.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export default {
    components: {
        LocationSearch
    },
    props: {
        open: Boolean
    },
    data() {
        return {
            selected: null,
            saving: false,
            actors: [],
            tagsInput: '',
            form: {
                actor: null,
                content: ''
            },
            map: null,
            marker: null
        }
    },
    computed: {
        findUrl() {
            return cp_url('activitypub/places/find');
        }
    },
    watch: {
        open(val) {
            if (val) {
                this.fetchActors();
            } else {
                this.destroyMap();
            }
        },
        selected(val) {
            if (val) {
                this.$nextTick(() => this.initMap());
            } else {
                this.destroyMap();
            }
        }
    },
    methods: {
        fetchActors() {
            this.$axios.get(cp_url('activitypub/places/actors'))
                .then(response => {
                    this.actors = response.data.actors;
                    if (this.actors.length > 0 && !this.form.actor) {
                        this.form.actor = this.actors[0].id;
                    }
                });
        },
        handleSelected(result) {
            this.selected = result;
        },
        save() {
            if (this.saving || !this.selected) return;
            this.saving = true;

            const payload = {
                actor: this.form.actor,
                content: this.form.content,
                place: this.selected,
                tags: this.tagsInput.split(',').map(t => t.trim()).filter(t => t !== '')
            };

            this.$axios.post(cp_url('activitypub/places/checkin'), payload)
            .then(response => {
                Statamic.$toast.success('Check-in posted!');
                this.$emit('submit-success');
                this.$emit('close');
                this.reset();
            })
            .catch(err => {
                Statamic.$toast.error(err.response?.data?.message || 'Failed to post check-in.');
            })
            .finally(() => {
                this.saving = false;
            });
        },
        initMap() {
            if (this.map) this.destroyMap();
            if (!this.$refs.mapPreviewContainer) return;

            const lat = this.selected.latitude || this.selected.lat;
            const lon = this.selected.longitude || this.selected.lon;

            if (!lat || !lon) {
                console.error('[ActivityPubPlaces] Invalid coordinates for map:', this.selected);
                return;
            }

            this.map = L.map(this.$refs.mapPreviewContainer, {
                zoomControl: false,
                attributionControl: false
            }).setView([lat, lon], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
            this.marker = L.marker([lat, lon]).addTo(this.map);

            // Double-tap invalidateSize to handle transitions
            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 100);
            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
        },
        destroyMap() {
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
        },
        handleClosed() {
            this.$emit('close');
            this.reset();
        },
        reset() {
            this.selected = null;
            this.form.content = '';
            this.tagsInput = '';
        }
    }
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.3s ease-out;
}
.slide-up-enter, .slide-up-leave-to {
    transform: translateY(20px);
    opacity: 0;
}
</style>
