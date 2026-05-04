import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    includeAbsolute: false,
                },
            },
        })
    ],
    server: {
        host: '0.0.0.0', // Принимать подключения с любого IP
        port: 5173,
        strictPort: false,
        hmr: {
            // Для работы с внешним доменом установите переменную VITE_HOST в .env
            // Например: VITE_HOST=aniarr.82.pet
            // Или используйте IP адрес сервера
            host: process.env.VITE_HOST || process.env.APP_URL?.replace(/^https?:\/\//, '').split(':')[0] || 'localhost',
            protocol: 'ws',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        cors: {
            origin: true, // Разрешить все источники
            credentials: true,
        },
    },
});
