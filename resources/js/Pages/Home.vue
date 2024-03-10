<template>
    <div class="container">
        <div class="text-4">
            <div class="title">Own your<br />decentralized domain</div>
            <div class="frame-2">
                <div class="caption">Powered by</div>
                <div class="frame-3">
                    <div class="handshake-logo-wrapper">
                        <div class="handshake-logo">
                            <img class="fill" src="https://c.animaapp.com/crkyC7VV/img/fill-1.svg" />
                        </div>
                    </div>
                    <div class="caption-2">Handshake</div>
                </div>
            </div>
        </div>
        <div class="search">
            <div class="frame-4">
                <div class="container-wrapper cursor-text" @click="$refs.search.focus()">
                    <div class="container-2">
                        <q-icon name="mdi-magnify" size="24px" color="primary" />
                        
                        <input
                            ref="search"
                            type="text"
                            v-model="search"
                            class="search-domains"
                            placeholder="Search Domains"
                        />
                    </div>
                </div>
                
                <q-separator color="grey-4" :vertical="$q.screen.width >= $q.screen.sizes.md" />

                <div class="dropdown cursor-pointer">
                    <div class="text-wrapper-2">{{ selectedTld }}</div>

                    <q-space />

                    <q-icon name="mdi-menu-down" size="34px" class="q-mr-sm text-grey-6" />
                    <!-- <img class="img q-mr-sm" src="https://c.animaapp.com/crkyC7VV/img/icon-r-3.svg" /> -->

                    <q-menu style="border-radius: 8px;" fit>
                        <q-list separator dense>
                            <q-item
                                clickable
                                v-close-popup
                                v-for="tld in tlds"
                                @click="selectedTld = tld"
                                :disable="selectedTld === tld"
                            >
                                <q-item-section>
                                    <q-item-label class="text-wrapper-2">
                                        {{ tld }}
                                    </q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </div>
            </div>

            <q-btn 
                flat
                no-caps
                class="bg-ez-purple text-white button-2"
            >
                <div class="text-5">Search</div>
            </q-btn>
        </div>
    </div>

    <Features />
</template>

<script>
import Guest from '@/Layouts/Guest.vue';
import Features from '@/Components/Features.vue';

export default {
    name: 'HomePage',
    layout: Guest,
    components: {
        Features,
    },
    data() {
        return {
            search: '',
            selectedTld: '.ez',
            tlds: [
                '.ez',
                '.easy',
                '.information',
                '.paid',
                '.motherboard',
            ]
        }
    },
}
</script>

<style lang="scss" scoped>
.container {
    transition: all 0.5s ease;

    @media (min-width: 1000px) {
        margin: 8rem 8rem;
    }

    @media (min-width: 600px) and (max-width: 999px) {
        margin: 2rem 2rem;
    }
}

.title {
    transition: all 0.5s ease;
}

.search {
    flex-direction: column;

    .frame-4 {
        width: 85vw;
        flex-direction: column;

        .container-wrapper {
            width: 100%;

            .container-2 {
                flex: 1;
            }

            .search-domains {
                width: 100%;
            }
        }
    }

    .q-btn {
        width: 85vw;
    }
}

@media (min-width: $breakpoint-md-min) {
    .search {
        flex-direction: row;

        .frame-4 {
            width: unset;
            flex-direction: row;

            .container-wrapper {
                width: unset;
            }
        }

        .q-btn {
            width: unset;
        }
    }
}
</style>