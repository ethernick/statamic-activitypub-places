<template>
    <a href="#" @click.prevent="open" class="block px-4 py-2 text-sm">
        New Place
    </a>
</template>

<script>
export default {
    methods: {
        open() {
            if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
                // Open the modal/drawer
                Statamic.$activitypub.bus.emit('activitypub:inbox:create-place');
            }
        }
    }
}
</script>
