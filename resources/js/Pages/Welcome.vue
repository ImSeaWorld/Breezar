<template>
    
    <q-toolbar class="bg-secondary text-white">
        <inertia-link href="/" class="text-primary q-pt-xs">
            <ApplicationLogo color="#fff" style="height: 45px;width: auto;" />
        </inertia-link>

        <q-toolbar-title>
            Breezar
        </q-toolbar-title>
    </q-toolbar>

    <div class="q-pa-md">
        <div class="row justify-center">
            <div class="col-12 col-md-6">
                <q-card square bordered flat>
                    <q-card-section class="text-center">
                        <ApplicationLogo color="#f6362e" style="height: 185px;width: auto;" />

                        <div class="text-h2 text-center text-secondary">
                            Breezar
                        </div>

                        <div class="text-subtitle1 text-center text-grey-3">
                            Laravel v{{ versions.laravel }} + Inertia {{ versions.inertia.server }}/v{{ versions.inertia.client }} + Vue v{{ versions.vue.client }} + Quasar v{{ versions.quasar.client.quasar }}
                        </div>
                        <div class="text-caption">
                            PHP v{{ versions.php }}
                        </div>
                    </q-card-section>

                    <q-card-section style="width: 85%;margin: auto;" >
                        <q-separator />

                        <q-list separator>
                            <q-item>
                                <q-item-section avatar>
                                    <ApplicationLogo color="#f6362e" style="height: 35px;width: auto;" />
                                </q-item-section>
                                <q-item-section />
                                <q-item-section side>
                                    <q-item-label caption>
                                        Server: v{{ versions.laravel }}
                                        <q-tooltip>
                                            laravel/framework
                                        </q-tooltip>
                                    </q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-item-label>
                                        
                                        <a
                                            href="https://inertiajs.com"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-link-box-outline" /> Website
                                        </a>
                                    </q-item-label>
                                    <q-item-label>
                                        <a
                                            href="https://github.com/inertiajs"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-github" /> Github
                                        </a>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section avatar>
                                    <ApplicationLogo type="inertia" color="#fff" style="height: 25px;width: auto;" />
                                </q-item-section>
                                <q-item-section />
                                <q-item-section side>
                                    <q-item-label caption>
                                        Client: v{{ versions.inertia.client }}
                                        <q-tooltip>
                                            @inertiajs/vue3
                                        </q-tooltip>
                                    </q-item-label>
                                    <q-item-label caption>
                                        Server: {{ versions.inertia.server }}
                                        <q-tooltip>
                                            @inertiajs/inertia-laravel
                                        </q-tooltip>
                                    </q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-item-label>
                                        
                                        <a
                                            href="https://inertiajs.com"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-link-box-outline" /> Website
                                        </a>
                                    </q-item-label>
                                    <q-item-label>
                                        <a
                                            href="https://github.com/inertiajs"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-github" /> Github
                                        </a>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section side>
                                    <ApplicationLogo type="vue" style="height: 30px;width: auto;" />
                                </q-item-section>
                                <q-item-section/>
                                <q-item-section side>
                                    <q-item-label caption>
                                        Client: v{{ versions.vue.client }}
                                        <q-tooltip>vue</q-tooltip>
                                    </q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-item-label>
                                        <a
                                            href="https://vuejs.org/"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-link-box-outline" /> Website
                                        </a>
                                    </q-item-label>
                                    <q-item-label>
                                        <a
                                            href="https://github.com/vuejs/core"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-github" /> Github
                                        </a>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section side>
                                    <ApplicationLogo type="quasar" color="#fff" style="height: 30px;width: auto;" />
                                </q-item-section>
                                <q-item-section side>
                                    <ApplicationLogo type="quasar-type" color="#fff" style="height: 20px;width: auto;" />
                                </q-item-section>
                                <q-item-section />
                                <q-item-section side>
                                    <q-item-label caption>
                                        Client: v{{ versions.quasar.client.quasar }}
                                        <q-tooltip>quasar</q-tooltip>
                                    </q-item-label>
                                    <q-item-label caption>
                                        Client(extras): v{{ versions.quasar.client.extras }}
                                        <q-tooltip>@quasar/extras</q-tooltip>
                                    </q-item-label>
                                    <q-item-label caption>
                                        Client(vite-plugin): v{{ versions.quasar.client['vite-plugin'] }}
                                        <q-tooltip>@quasar/vite-plugin</q-tooltip>
                                    </q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-item-label>                                        
                                        <a
                                            href="https://quasar.dev/"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-link-box-outline" /> Website
                                        </a>
                                    </q-item-label>
                                    <q-item-label>
                                        <a
                                            href="https://github.com/quasarframework/quasar"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-grey-4"
                                        >
                                            <q-icon name="mdi-github" /> Github
                                        </a>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>

                    <q-card-actions align="center">
                        <q-btn
                            square
                            no-caps
                            color="secondary"
                            label="Get Started"
                            @click="$inertia.visit('login')"
                        />

                        <q-btn
                            square
                            no-caps
                            v-if="auth.user"
                            color="secondary"
                            label="Dashboard"
                            @click="$inertia.visit('dashboard')"
                        />

                        <q-btn
                            square
                            outline
                            no-caps
                            color="primary"
                            label="Documentation"
                            @click="windowOpen('https://laravel.com/docs/10.x')"
                        />
                    </q-card-actions>
                </q-card>
            </div>
        </div>
    </div>
</template>

<script>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

export default {
    name: 'Welcome',
    props: {
        auth: Object,
        canLogin: Boolean,
        canRegister: Boolean,
        errors: Object,
        versions: Object,
    },
    components: {
        ApplicationLogo,
    },
    methods: {
        windowOpen(url) {
            window.open(url, '_blank');
        },
    },
};
</script>
