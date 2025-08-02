// Import modules...
import { createApp, h } from 'vue';

import { createInertiaApp, Link } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import { Quasar, Notify } from 'quasar';
import iconSet from 'quasar/icon-set/mdi-v7.js';
import '@quasar/extras/mdi-v7/mdi-v7.css';
import 'quasar/dist/quasar.css';

import axios from 'axios';
import VueAxios from 'vue-axios';

axios.defaults.baseURL = import.meta.env.AXIOS_BASE_URL;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

createInertiaApp({
    progress: {
        color: '#4B5563',
    },
    resolve: async (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .mixin({ 
                components: { InertiaLink: Link },
                methods: { route } 
            })
            .use(plugin)
            .use(Quasar, {
                iconSet: iconSet,
                plugins: {
                    Notify
                },
                config: {
                    dark: 'auto',
                    brand: {
                        secondary: '#f6362e',
                    }
                }
            })
            .use(VueAxios, axios)
            .mount(el);
    }
});
