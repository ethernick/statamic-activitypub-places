<template>
    <a href="#" @click.prevent="open" class="block px-4 py-2 text-sm">
        New Check-In
    </a>
</template>

<script>
export default {
    methods: {
        open() {
            if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
                // Open the Check-in drawer
                Statamic.$activitypub.bus.emit('activitypub:inbox:create-checkin');
            }
        }
    }
}
</script>
