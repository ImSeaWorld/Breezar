require('./bootstrap');

// Import modules...
import { createApp, h } from 'vue';
import { App as InertiaApp, plugin as InertiaPlugin } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';

import { Quasar } from 'quasar';
import iconSet from 'quasar/icon-set/mdi-v4.js';
import '@quasar/extras/mdi-v4/mdi-v4.css';

const el = document.getElementById('app');

createApp({
    render: () =>
        h(InertiaApp, {
            initialPage: JSON.parse(el.dataset.page),
            resolveComponent: (name) => require(`./Pages/${name}`).default,
        }),
})
    .mixin({ methods: { route } })
    .use(InertiaPlugin)
    .use(Quasar, {
        iconSet: iconSet
    })
    .mount(el);

InertiaProgress.init({ color: '#4B5563' });
