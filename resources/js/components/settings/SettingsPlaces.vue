<template>
    <div class="px-6 py-6 pb-0">
        <h2 class="text-xl font-medium mb-4">OpenStreetMap Configuration</h2>
        <p class="text-sm text-gray-600 mb-6 w-3/4">
            The Places addon uses OpenStreetMap (via Nominatim) to search and validate geographic features. 
            Nominatim requires a valid User-Agent, usually an email address, or they will actively block your server.
            You can also define a custom endpoint if you are using your own Nominatim/Geocoding server.
        </p>

        <div class="mb-4 w-full md:w-3/4">
            <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Nominatim User-Agent</label>
            <input 
                type="text" 
                class="input-text w-full" 
                v-model="form.nominatim_user_agent" 
                placeholder="e.g. ActivityPubPlaces at mydomain.com"
            />
            <p class="text-xs text-gray-500 mt-1">This is required. Include your contact email or domain to avoid ratelimits.</p>
        </div>

        <div class="mb-4 w-full md:w-3/4">
            <label class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Nominatim API URL</label>
            <input 
                type="text" 
                class="input-text w-full" 
                v-model="form.nominatim_url" 
                placeholder="https://nominatim.openstreetmap.org/search"
            />
            <p class="text-xs text-gray-500 mt-1">Leave blank to use the default open provider.</p>
        </div>

        <h2 class="text-xl font-medium mt-12 mb-4">Check-in Configuration</h2>
        <div class="mb-4 w-full md:w-3/4">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox" v-model="form.compatibility_mode" class="checkbox" />
                </div>
                <div class="ml-3 text-sm">
                    <label class="font-medium text-gray-700 dark:text-gray-300">ActivityPub Compatibility Mode</label>
                    <p class="text-xs text-gray-500">When enabled, check-ins will also generate a standard <strong>Note</strong> activity. This ensures that check-ins appear on platforms that don't support custom Activity types (like Mastodon).</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        form: {
            type: Object,
            required: true
        }
    }
}
</script>
