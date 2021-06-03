<template>
    <div class="absolute-full">
        <q-layout view="hHh lpR fFf">
            <q-header elevated v-if="$slots.header">
                <q-toolbar class="q-pa-none">
                    <div class="q-ma-sm">
                        <q-btn :icon="`mdi-${sidebar.left ? 'backburger' : 'menu'}`" dense @click="sidebar.left = !sidebar.left" />
                    </div>
                    <q-toobar-title>
                        <slot name="header" :sidebar="sidebar" />
                    </q-toobar-title>
                    <q-space />

                    <q-btn-dropdown flat no-caps>
                        <template #label>
                            <q-avatar rounded style="background-color: #009688;" class="q-mr-sm">
                                {{ auth.user.name.charAt(0) }}
                            </q-avatar>

                            <q-list>
                                <q-item class="q-pl-none text-center">
                                    <q-item-section>
                                        <q-item-label>{{ auth.user.name }}</q-item-label>
                                        <q-item-label caption class="text-blue-grey-2">Administrator</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </template>
                        <q-btn label="logout" @click="$inertia.post(route('logout'))" />
                    </q-btn-dropdown>
                    <!--q-btn label="logout" @click="$inertia.post(route('logout'))" /-->
                </q-toolbar>
            </q-header>

            <q-drawer v-model="sidebar.left" side="left" class="bg-grey-3" :width="300" :breakpoint="800" overlay elevated content-class="relative-position" content-style="width: 100%;height: 100%;">
                <q-list>
                    <q-item clickable :active="route().current('dashboard')" v-ripple @click="$intertia.view('dashboard')">
                        <q-item-section avatar>
                            <q-icon name="mdi-home" />
                        </q-item-section>
                        <q-item-section>
                            Dashboard
                        </q-item-section>
                    </q-item>
                </q-list>
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
    props: {
        auth: Object,
        errors: Object,
    },
    data() {
        return {
            sidebar: {
                left: false,
            },
        };
    },
    mounted() {
        //console.log(this.$inertia);
    },
};
</script>
