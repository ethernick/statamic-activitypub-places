<template>
    <div>
        <place-stack
            :open="isCreatingPlace"
            :edit-place="editingPlace"
            @close="closeDrawer"
            @submit-success="$emit('submit-success')"
        />

        <check-in-stack
            :open="isCheckingIn"
            @close="isCheckingIn = false"
            @submit-success="$emit('submit-success')"
        />

        <map-service-selector-stack
            v-if="activePlace"
            :open="isSelectingService"
            :place="activePlace"
            @close="isSelectingService = false"
        />
    </div>
</template>

<script>
import PlaceStack from './PlaceStack.vue';
import CheckInStack from '../checkin/CheckInStack.vue';
import MapServiceSelectorStack from '../map/MapServiceSelectorStack.vue';

export default {
    components: {
        PlaceStack,
        CheckInStack,
        MapServiceSelectorStack
    },
    data() {
        return {
            isCreatingPlace: false,
            isCheckingIn: false,
            editingPlace: null,
            isSelectingService: false,
            activePlace: null
        }
    },
    mounted() {
        if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
            // Standardized & Legacy Event Listeners
            this._onCreatePlace = () => {
                this.isCreatingPlace = true;
                this.editingPlace = null;
            };
            this._onCheckin = () => {
                this.isCheckingIn = true;
            };
            this._onEditPlace = (place) => {
                this.isCreatingPlace = true;
                this.editingPlace = place;
            };
            this._onSelectMap = (place) => {
                this.activePlace = place;
                this.isSelectingService = true;
            };

            // Standardized events
            Statamic.$activitypub.bus.on('activitypub:places:open-create', this._onCreatePlace);
            Statamic.$activitypub.bus.on('activitypub:places:open-checkin', this._onCheckin);
            Statamic.$activitypub.bus.on('activitypub:places:open-edit', this._onEditPlace);
            Statamic.$activitypub.bus.on('activitypub:places:open-map', this._onSelectMap);

            // Legacy backward-compatible events
            Statamic.$activitypub.bus.on('activitypub:inbox:create-place', this._onCreatePlace);
            Statamic.$activitypub.bus.on('activitypub:inbox:create-checkin', this._onCheckin);
            Statamic.$activitypub.bus.on('activitypub:inbox:edit-place', this._onEditPlace);
            Statamic.$activitypub.bus.on('activitypub:inbox:select-map-service', this._onSelectMap);
        }
    },
    beforeDestroy() {
        if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
            Statamic.$activitypub.bus.off('activitypub:places:open-create', this._onCreatePlace);
            Statamic.$activitypub.bus.off('activitypub:places:open-checkin', this._onCheckin);
            Statamic.$activitypub.bus.off('activitypub:places:open-edit', this._onEditPlace);
            Statamic.$activitypub.bus.off('activitypub:places:open-map', this._onSelectMap);

            Statamic.$activitypub.bus.off('activitypub:inbox:create-place', this._onCreatePlace);
            Statamic.$activitypub.bus.off('activitypub:inbox:create-checkin', this._onCheckin);
            Statamic.$activitypub.bus.off('activitypub:inbox:edit-place', this._onEditPlace);
            Statamic.$activitypub.bus.off('activitypub:inbox:select-map-service', this._onSelectMap);
        }
    },
    methods: {
        closeDrawer() {
            this.isCreatingPlace = false;
            this.editingPlace = null;
        }
    }
}
</script>
