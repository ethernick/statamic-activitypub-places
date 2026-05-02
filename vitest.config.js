import { defineConfig } from 'vitest/config';
import vue2 from '@vitejs/plugin-vue2';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue2()],
    test: {
        globals: true,
        environment: 'jsdom',
        alias: {
            'vue': 'vue/dist/vue.common.js',
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
        },
    },
});
