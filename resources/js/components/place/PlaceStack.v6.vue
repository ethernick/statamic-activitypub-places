<template>
    <inbox-stack 
        :open="open" 
        title="New Place" 
        @closed="handleClosed"
    >
        <div class="px-1">
            <!-- Phase 1: Search -->
            <div v-show="!selected">
                <p class="text-xs text-gray-500 mb-6">Search for a location to share with your followers.</p>
                <location-search 
                    @change="provisionalLocation = $event"
                    @selected="handleSelected" 
                />
            </div>

            <!-- Phase 2: Metadata Details -->
            <transition name="slide-up">
                <div v-if="selected" class="mt-4 space-y-6">
                    <!-- Location Summary (Click to change location) -->
                    <div 
                        @click="changeLocation" 
                        class="group cursor-pointer py-1.5 -mx-1 px-1 rounded-lg hover:bg-gray-100/70 dark:hover:bg-gray-800/60 transition-colors"
                        title="Click to change location"
                    >
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-primary shrink-0 mt-0.5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-gray-900 dark:text-gray-100 group-hover:text-primary transition-colors">
                                        {{ selected.osm_name }}
                                    </span>
                                    <button type="button" @click.stop="changeLocation" class="text-gray-400 hover:text-gray-900 dark:hover:text-white group-hover:text-primary transition-colors ml-auto p-0.5 shrink-0" title="Change Location">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed break-words">
                                    {{ selected.address }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actor Selection -->
                    <div v-if="actors.length > 0">
                        <label class="block text-sm font-bold mb-2">Post As</label>
                        <select v-model="form.actor" class="input-text w-full">
                            <option v-for="actor in actors" :key="actor.id" :value="actor.id">{{ actor.name }} ({{ actor.handle }})</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="font-bold text-xs mb-1 block">Title</label>
                        <input v-model="form.title" type="text" class="input-text w-full" placeholder="e.g. My Favorite Cafe" />
                    </div>

                    <!-- Content -->
                    <div class="flex flex-col">
                        <label class="block font-bold text-xs mb-1">Content</label>
                        <div class="min-h-[200px]">
                            <markdown-fieldtype
                                :value="form.content"
                                :model-value="form.content"
                                :config="{
                                    cheatsheet: true,
                                    container: 'assets',
                                    buttons: ['bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'link', 'image', 'table'],
                                    type: 'markdown',
                                    toolbar_mode: 'fixed'
                                }"
                                :meta="{ previewUrl: null }"
                                @input="val => form.content = val"
                                @update:modelValue="val => form.content = val"
                                @update:value="val => form.content = val"
                            />
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <template #footer-end>
            <button @click="handleClosed" class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 shadow-sm px-4 h-10 text-sm rounded-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</button>

            <!-- Phase 1: Confirm Location -->
            <button 
                v-if="!selected"
                @click="confirmLocation" 
                class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-linear-to-b from-primary/90 to-primary hover:bg-primary-hover text-white disabled:opacity-60 disabled:text-white dark:disabled:text-white border border-primary-border shadow-ui-md inset-shadow-2xs inset-shadow-white/25 dark:inset-shadow-black/20 disabled:inset-shadow-none dark:disabled:inset-shadow-none [&_svg]:text-white [&_svg]:opacity-60 px-4 h-10 text-sm gap-2 rounded-lg ml-2" 
                :disabled="!provisionalLocation"
            >
                Confirm Location
            </button>

            <!-- Phase 2: Save Place -->
            <button 
                v-else
                @click="save" 
                :disabled="saving" 
                class="relative inline-flex items-center justify-center whitespace-nowrap shrink-0 font-medium antialiased cursor-pointer no-underline disabled:[&_svg]:opacity-30 disabled:cursor-not-allowed [&_svg]:shrink-0 dark:[&_svg]:text-white bg-linear-to-b from-primary/90 to-primary hover:bg-primary-hover text-white disabled:opacity-60 disabled:text-white dark:disabled:text-white border border-primary-border shadow-ui-md inset-shadow-2xs inset-shadow-white/25 dark:inset-shadow-black/20 disabled:inset-shadow-none dark:disabled:inset-shadow-none [&_svg]:text-white [&_svg]:opacity-60 px-4 h-10 text-sm gap-2 rounded-lg ml-2"
            >
                <span v-if="saving" class="loading loading-xs mr-2"></span>
                Save Place
            </button>
        </template>
    </inbox-stack>
</template>

<script>
import LocationSearch from '../location/LocationSearch.vue';

export default {
    components: {
        LocationSearch
    },
    props: {
        open: Boolean,
        editPlace: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            selected: null,
            provisionalLocation: null,
            saving: false,
            actors: [],
            form: {
                actor: null,
                title: '',
                content: ''
            }
        }
    },
    computed: {
        isEditing() {
            return !!this.editPlace;
        }
    },
    watch: {
        open(val) {
            if (val) {
                this.fetchActors();
                if (this.editPlace) {
                    this.selected = {
                        osm_name: this.editPlace.osm_name || 'Location Data Locked',
                        address: this.editPlace.address || 'Coordinates are preserved for federation.'
                    };
                    this.form.title = this.editPlace.title || '';
                    this.form.content = this.editPlace.content_raw || this.editPlace.content || '';
                    if (this.editPlace.actor && this.editPlace.actor.id) {
                        this.form.actor = this.editPlace.actor.id;
                    }
                }
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
            this.provisionalLocation = result;
            this.applyLocation(result);
        },
        confirmLocation() {
            if (!this.provisionalLocation) return;
            this.applyLocation(this.provisionalLocation);
        },
        applyLocation(result) {
            const osm_name = result.name || result.display_name.split(',')[0];
            this.selected = {
                osm_type: result.osm_type,
                osm_id: result.osm_id.toString(),
                latitude: parseFloat(result.lat),
                longitude: parseFloat(result.lon),
                address: result.display_name,
                osm_name: osm_name
            };
            
            // Auto-populate title if empty or matches previous OSM name
            if (!this.form.title || this.form.title === '') {
                this.form.title = osm_name;
            }
        },
        changeLocation() {
            this.selected = null;
        },
        save() {
            if (this.saving || !this.selected) return;
            
            this.saving = true;
            
            const method = this.isEditing ? 'put' : 'post';
            const endpoint = this.isEditing 
                ? cp_url('activitypub/places/' + this.editPlace.id)
                : cp_url('activitypub/places/search');

            const payload = {
                ...this.selected,
                ...this.form,
                name: this.selected.osm_name // Pass official name as fallback/meta
            };

            this.$axios[method](endpoint, payload)
            .then(response => {
                Statamic.$toast.success(this.isEditing ? 'Place updated successfully!' : 'Place created and posted!');
                this.$emit('submit-success');
                this.$emit('close');
                this.reset();
            })
            .catch(err => {
                Statamic.$toast.error(err.response?.data?.message || 'Failed to save place.');
            })
            .finally(() => {
                this.saving = false;
            });
        },
        handleClosed() {
            this.$emit('close');
            this.reset();
        },
        reset() {
            this.selected = null;
            this.provisionalLocation = null;
            this.form.title = '';
            this.form.content = '';
            // keep actor selection for efficiency
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

/* Custom Reliable Styles */
.loc-brief-box {
    background-color: rgba(239, 246, 255, 0.5); /* blue-50/50 */
    border-color: #bfdbfe; /* blue-200 */
}
.loc-name { color: #1e3a8a; } /* blue-900 */
.loc-address { color: #6b7280; } /* gray-500 */

/* Dark Mode Overrides */
html.dark .loc-brief-box,
html.is-dark .loc-brief-box,
html.isdark .loc-brief-box {
    background-color: #0a0a0a; /* neutral-950 */
    border-color: #262626; /* neutral-800 */
}
html.dark .loc-name,
html.is-dark .loc-name,
html.isdark .loc-name { color: #f5f5f5; } /* neutral-100 */

html.dark .loc-address,
html.is-dark .loc-address,
html.isdark .loc-address { color: #737373; } /* neutral-500 */
</style>
