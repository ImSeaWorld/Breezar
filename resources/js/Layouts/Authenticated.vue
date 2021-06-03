<template>
    <div class="absolute-full">
        <q-layout view="hHh lpR fFf">
            <q-header elevated v-if="$slots.header">
                <q-toolbar>
                    <div class="q-ma-sm">
                        <q-btn :icon="`mdi-${sidebar.left ? 'backburger' : 'menu'}`" dense @click="sidebar.left = !sidebar.left" />
                    </div>
                    <q-toobar-title>
                        <slot name="header" :sidebar="sidebar" />
                    </q-toobar-title>
                </q-toolbar>
                <q-toolbar float="right">
                    <q-btn label="logout" @click="$inertia.post(route('logout'))" />
                </q-toolbar>
            </q-header>

            <q-drawer v-model="sidebar.left" side="left" :width="300" :breakpoint="800" overlay elevated content-class="relative-position" content-style="width: 100%;height: 100%;">
                <div class="q-pa-md">
                    <q-list>
                        <q-item clickable :active="route().current('dashboard')">
                            <q-item-section avatar>
                                <q-icon name="mdi-home" />
                            </q-item-section>
                            <q-item-section>
                                <inertia-link :href="route('dashboard')">Dashboard</inertia-link>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </div>
            </q-drawer>

            <q-page-container >
                <q-page class="q-pa-md">
                    <q-scroll-area visible class="absolute-full fit">
                        <main>
                            <slot/>
                        </main>
                    </q-scroll-area>
                </q-page>
            </q-page-container>
        </q-layout>
    </div>
</template>

<script>
export default {
    data() {
        return {
            sidebar: {
                left: false,
            },
        };
    },
};
</script>
