import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ command }) => {
    // Load app-level env vars to node-level env vars.
    // process.env = { ...process.env, ...loadEnv(command, process.cwd()) };

    return {
        base: command === 'build' ? '/build/' : '',
        publicDir: false,
        build: {
            manifest: true,
            outDir: 'public/build',
            rollupOptions: {
                input: 'resources/js/app.js',
            },
        },
        server: {
            strictPort: true,
            port: 3000,
            // https: true,
            hmr: {
                host: 'localhost',
            },
        },
        plugins: [
            laravel({
                input: [
                    'resources/sass/app.scss',
                    'resources/js/app.js',
                ],
                // buildDirectory: 'bundle',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            {
                name: 'blade',
                handleHotUpdate({ file, server }) {
                    if (file.endsWith('.blade.php')) {
                        server.ws.send({
                            type: 'full-reload',
                            path: '*',
                        });
                    }
                },
            },
        ],
        resolve: {
            alias: {
                '~': '/resources',
                '@': '/resources/js',
            },
        },
        optimizeDeps: {
            include: [
                '@inertiajs/vue3',
                'quasar',
                'axios',
                'vue-axios',
                'vue',
            ],
        },
        css: {
            preprocessorOptions: {
                scss: {
                    additionalData: '@import "./node_modules/quasar/src/css/variables.sass";',
                }
            }
        }
    };
});
