<template>
    <div class="absolute-full">
        <q-layout view="hHh lpR fFf">
            <q-header flat bordered class="bg-primary">
                <q-toolbar>
                    <q-btn 
                        flat 
                        dense 
                        round 
                        :icon="leftDrawerOpen ? 'mdi-backburger' : 'mdi-menu'"
                        @click="toggleLeftDrawer"
                    />

                    <q-toolbar-title>
                        {{ $page.props.appName || 'LMS Manager' }}
                    </q-toolbar-title>

                    <q-space />

                    <q-btn-dropdown flat no-caps stretch>
                        <template #label>
                            <q-avatar size="32px" color="white" text-color="primary">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </q-avatar>
                            <div class="q-ml-sm text-left">
                                <div class="text-weight-medium">{{ user.name }}</div>
                                <div class="text-caption text-blue-grey-2">{{ user.role }}</div>
                            </div>
                        </template>

                        <q-list>
                            <q-item clickable v-close-popup @click="navigateTo('profile.edit')">
                                <q-item-section avatar>
                                    <q-icon name="mdi-account" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>Profile</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item clickable v-close-popup @click="navigateTo('2fa.show')">
                                <q-item-section avatar>
                                    <q-icon name="mdi-shield-account" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>2FA Settings</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-separator />

                            <q-item clickable v-close-popup @click="logout">
                                <q-item-section avatar>
                                    <q-icon name="mdi-logout" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>Logout</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-btn-dropdown>
                </q-toolbar>
            </q-header>

            <q-drawer
                v-model="leftDrawerOpen"
                show-if-above
                :width="260"
                :breakpoint="1024"
                bordered
                class="bg-grey-10"
            >
                <q-scroll-area class="fit">
                    <q-list>
                        <q-item-label header class="text-grey-4">
                            Navigation
                        </q-item-label>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('dashboard')"
                            @click="navigateTo('dashboard')"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-view-dashboard" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Dashboard</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('clients.*')"
                            @click="navigateTo('clients.index')"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-domain" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Clients</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('instances.*')"
                            @click="navigateTo('instances.index')"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-server" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Instances</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('reports.*')"
                            @click="navigateTo('reports.index')"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-chart-box" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Reports</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('scripts.*')"
                            @click="navigateTo('scripts.index')"
                            v-if="user.role === 'admin'"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-code-tags" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Scripts</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-separator spaced />

                        <q-item-label header class="text-grey-4">
                            System
                        </q-item-label>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('users.*')"
                            @click="navigateTo('users.index')"
                            v-if="user.role === 'admin'"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-account-multiple" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Users</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('activity.*')"
                            @click="navigateTo('activity.index')"
                            v-if="user.role === 'admin'"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-history" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Activity Logs</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('fly-sync')"
                            @click="navigateTo('fly-sync')"
                            v-if="user.role === 'admin'"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-sync" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Fly.io Sync</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item 
                            clickable 
                            v-ripple 
                            :active="route().current('settings.*')"
                            @click="navigateTo('settings.index')"
                            v-if="user.role === 'admin'"
                        >
                            <q-item-section avatar>
                                <q-icon name="mdi-cog" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label>Settings</q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-scroll-area>
            </q-drawer>

            <q-page-container>
                <q-page padding>
                    <slot />
                </q-page>
            </q-page-container>
        </q-layout>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const leftDrawerOpen = ref(false);

const toggleLeftDrawer = () => {
    leftDrawerOpen.value = !leftDrawerOpen.value;
};

const navigateTo = (routeName) => {
    router.visit(route(routeName));
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<style scoped>
.q-drawer .q-item {
    color: rgba(255, 255, 255, 0.7);
}

.q-drawer .q-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.q-drawer .q-item.q-router-link--active,
.q-drawer .q-item.q-item--active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.q-drawer .q-item.q-router-link--active .q-icon,
.q-drawer .q-item.q-item--active .q-icon {
    color: #1976d2;
}

.q-drawer .q-separator {
    background: rgba(255, 255, 255, 0.1);
}

/* Dark mode card styles - Override ALL Quasar defaults */
.q-dark .q-card,
.q-card--dark,
body.body--dark .q-card,
.q-card.q-card--dark {
    box-shadow: none !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 0 !important;
}

/* Force override the specific q-card--dark class */
.q-card--dark,
.q-dark .q-card--dark {
    box-shadow: none !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}

/* Header styles */
.q-dark .q-header,
body.body--dark .q-header,
.q-header.q-header--dark {
    box-shadow: none !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

body.body--dark .q-toolbar {
    background: var(--q-dark);
}

/* Override ALL card shadow variations */
.q-dark .q-card,
.q-dark .q-card--dark,
.q-dark .q-card--bordered,
body.body--dark .q-card,
body.body--dark .q-card--dark,
body.body--dark .q-card--bordered {
    box-shadow: none !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 0 !important;
}

/* Match drawer border style */
.q-dark .q-drawer--bordered,
body.body--dark .q-drawer--bordered {
    border-right: 1px solid rgba(255, 255, 255, 0.12);
}

/* Dialog cards should also be flat and square */
.q-dark .q-dialog__inner .q-card,
body.body--dark .q-dialog__inner .q-card {
    box-shadow: none !important;
    border-radius: 0 !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
}
</style>

<style>
/* GLOBAL OVERRIDE - Absolute last word on dark mode styling */
.q-card--dark,
body.body--dark .q-card,
body.q-dark .q-card,
.q-dark .q-card {
    box-shadow: none !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 0 !important;
}

/* Force flat header */
.q-header,
.q-header--dark,
body.body--dark .q-header,
body.q-dark .q-header {
    box-shadow: none !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
}
</style>
