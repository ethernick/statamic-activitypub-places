<template>
    <a href="#" @click.prevent="open" class="block px-4 py-2 text-sm">
        New Check-In
    </a>
</template>

<script setup>
const open = () => {
    if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
        Statamic.$activitypub.bus.emit('activitypub:inbox:create-checkin');
    }
};
</script>
