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
        host: '0.0.0.0', // allows access from your local network
        port: 5173,
        hmr: {
            host: '192.168.3.175', // your machine's IP on the VPN or LAN
        },
    },
});
