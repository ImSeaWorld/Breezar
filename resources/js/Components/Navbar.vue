<template>
    <header>
        <q-expansion-item
            v-model="open"
            :header-class="`q-pa-none ${overBreakpoint ? 'q-px-xl q-py-sm' : ''}`"
            expand-icon-toggle
            :expand-icon-class="`${overBreakpoint ? 'hidden' : ''} ${open ? 'rotate-360' : ''}`"
            expand-icon="mdi-menu"
        >
            <template #header>
                <q-toolbar style="max-width: 1500px; margin: 0 auto;">
                    <div class="logo cursor-pointer" @click="$inertia.get('/')">
                        <div class="text-wrapper text-white">EZ</div>
                        <div>domains</div>
                    </div>

                    <q-toolbar-title />

                    <ul v-if="overBreakpoint" class="nav-links q-ml-md display-inline">
                        <li v-for="link in links">
                            <a :href="link.href" @click.prevent="$inertia.get(link.href)" class="q-mr-md">
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>

                    <q-btn
                        v-if="overBreakpoint"
                        no-caps
                        class="bg-grey-3 text-bold"
                        color="grey-13"
                        outline
                        @click="windowOpen('https://twitter.com/ez_domains')"
                    >
                        <twitter
                            class="q-mr-sm" 
                            style="height: auto;width: 28px;" 
                        />
                        <span class="text-grey-8">
                            Follow Us
                        </span>
                    </q-btn>
                </q-toolbar>
            </template>

            <q-list>
                <q-item 
                    v-ripple 
                    clickable 
                    v-for="link in links"
                    @click="$inertia.get(link.href)"
                    @mousedown.middle.prevent
                    @mouseup.middle="() => windowOpen(link.href)"
                >
                    <q-item-section>
                        <q-item-label class="text-right q-pr-xs text-dark text-h6">
                            {{ link.label }}
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </q-list>
        </q-expansion-item>
    </header>
</template>

<script>
import twitter from '@/Components/Logos/twitter.vue';

export default {
    name: 'Navbar',
    components: {
        twitter,
    },
    props: {
        auth: Object,
    },
    data() {
        return {
            open: false,
            breakpoint: 1000,
            links: [
                {
                    label: 'Premium Domains',
                    href: '/premium-domains',
                },
                {
                    label: 'Learn',
                    href: '/learn',
                },
                {
                    label: 'Contact',
                    href: '/contact',
                },
            ]
        }
    },
    methods: {
        windowOpen(link) {
            window.open(link, '_blank');
        }
    },
    computed: {
        overBreakpoint() {
            return this.$q.screen.width > this.breakpoint;
        },
    },
    watch: {
        '$q.screen.width'(width) {
            if (width > 750) {
                this.open = false;
            }
        },
    },
}
</script>

<style lang="scss" scoped>
.logo {
    display: flex;
    align-items: center;
    font-size: 1.5rem;
    color: #5216be;
    
    font-family: var(--text-5xl-font-bold-font-family);
    font-weight: var(--text-5xl-font-bold-font-weight);
    letter-spacing: -2px;
    font-style: var(--text-5xl-font-bold-font-style);
}

.text-wrapper {
    background-color: #5216be;
    padding: 0.2rem 0.5rem;
    border-radius: 50%;
    margin-right: 0.25rem;
    font-weight: bolder;
    font-family: var(--text-5xl-font-bold-font-family);
}

ul.nav-links {
    height: 100%;
    position: relative;

    li {
        height: inherit;
        display: inline-flex;
        padding: 0 0.5rem;

        a {
            height: inherit;
            display: flex;
            align-items: center;

            font-size: 16px;
            font-family: "Inter", Helvetica;
            font-weight: 600;
            color: var(--color-mode-mode-alpha-700);
        }
    }
}
</style>