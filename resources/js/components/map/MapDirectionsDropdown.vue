<template>
    <div class="relative flex-shrink-0" ref="dropdownWrapper">
        <!-- Variant: Pill (For floating map preview headers) -->
        <button 
            v-if="variant === 'pill'"
            type="button" 
            @click.stop="toggleDropdown"
            class="group inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-medium rounded-md bg-black/60 hover:bg-black/80 text-white/90 hover:text-white backdrop-blur-sm transition-all select-none cursor-pointer focus:outline-none border border-white/10 shadow-sm"
            :aria-expanded="isOpen"
        >
            <span>{{ label }}</span>
            <svg class="w-3 h-3 transition-transform stroke-current opacity-70 group-hover:opacity-100" :class="{ 'rotate-180': isOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Variant: Text (Default for stack and card footers) -->
        <button 
            v-else
            type="button" 
            @click.stop="toggleDropdown"
            class="group inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors select-none cursor-pointer focus:outline-none"
            :aria-expanded="isOpen"
        >
            <span>{{ label }}</span>
            <svg class="w-3.5 h-3.5 transition-transform stroke-current opacity-70 group-hover:opacity-100" :class="{ 'rotate-180': isOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Anchored Dropdown Menu (Directly contained, zero z-index conflicts with stacks) -->
        <div 
            v-if="isOpen" 
            :class="[
                'absolute w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-30 text-xs font-medium text-gray-700 dark:text-gray-200 divide-y divide-gray-100 dark:divide-gray-700/60',
                placement === 'top' ? 'right-0 bottom-full mb-2' : 'right-0 top-full mt-2'
            ]"
            @click.stop
        >
            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Open in Maps
            </div>
            <div class="py-1">
                <a 
                    v-for="service in services" 
                    :key="service.name"
                    :href="service.getUrl(location)" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    @click="isOpen = false"
                    class="flex items-center justify-between px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer no-underline"
                >
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: service.color }"></span>
                        {{ service.name }}
                    </span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import { mapServices } from '../../mapUtils';

export default {
    name: 'MapDirectionsDropdown',
    props: {
        location: {
            type: Object,
            required: true
        },
        label: {
            type: String,
            default: 'Directions'
        },
        placement: {
            type: String,
            default: 'top'
        },
        variant: {
            type: String,
            default: 'text' // 'text' | 'pill'
        }
    },
    data() {
        return {
            isOpen: false,
            services: mapServices
        };
    },
    mounted() {
        this.onClickOutside = (e) => {
            if (this.$refs.dropdownWrapper && !this.$refs.dropdownWrapper.contains(e.target)) {
                this.isOpen = false;
            }
        };
        document.addEventListener('click', this.onClickOutside);
    },
    beforeDestroy() {
        if (this.onClickOutside) {
            document.removeEventListener('click', this.onClickOutside);
        }
    },
    methods: {
        toggleDropdown() {
            this.isOpen = !this.isOpen;
        }
    }
}
</script>
