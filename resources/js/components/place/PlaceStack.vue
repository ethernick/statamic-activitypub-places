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
                <location-search @selected="handleSelected" />
            </div>

            <!-- Phase 2: Metadata Details -->
            <transition name="slide-up">
                <div v-if="selected" class="mt-4 space-y-6">
                    <!-- Location Brief -->
                    <div class="p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-900/30 flex items-start gap-4">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold truncate text-blue-900 dark:text-blue-300">{{ selected.osm_name }}</div>
                            <div class="text-[10px] text-gray-500 truncate">{{ selected.address }}</div>
                        </div>
                        <button @click="selected = null" class="btn btn-xs">Change</button>
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
                                    type: 'markdown'
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
            <button @click="handleClosed" class="btn">Cancel</button>
            <button @click="save" class="btn btn-primary ml-2" :disabled="!selected || saving">
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
</style>
