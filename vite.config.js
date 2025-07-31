import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',  // Accept connections from any IP
        port: 5173,
        cors: true,
        hmr: {
            host: '192.168.3.175'  // Or your server IP
        },
        origin: ['http://192.168.3.175:8000', 'http://localhost:8000']
    },
});
