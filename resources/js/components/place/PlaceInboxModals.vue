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
            Statamic.$activitypub.bus.on('activitypub:inbox:create-place', () => {
                this.isCreatingPlace = true;
                this.editingPlace = null;
            });
            Statamic.$activitypub.bus.on('activitypub:inbox:create-checkin', () => {
                this.isCheckingIn = true;
            });
            Statamic.$activitypub.bus.on('activitypub:inbox:edit-place', (place) => {
                this.isCreatingPlace = true;
                this.editingPlace = place;
            });
            Statamic.$activitypub.bus.on('activitypub:inbox:select-map-service', (place) => {
                this.activePlace = place;
                this.isSelectingService = true;
            });
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
