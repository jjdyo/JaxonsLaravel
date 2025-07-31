import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '192.168.3.175',
        port: 5173,
        cors: {
            origin: ['http://192.168.3.175:8000', 'http://localhost:8000'],
            credentials: true,
        },
        hmr: {
            host: '192.168.3.175',
        },
    },
});
