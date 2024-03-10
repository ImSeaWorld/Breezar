<template>
    <div class="container">
        <div class="text-4 q-px-md q-mt-xl">
            <div class="title">Premium Domains</div>
            <p class="text-wrapper">A selection of premium domains that will help you standout.</p>
        </div>
        
        <div class="container q-my-lg">
            <q-card v-for="a in 5" style="border-radius: 8px;width: 85vw;max-width: 975px;" flat>
                <q-card-section>
                    <q-item dense>
                        <q-item-section>
                            <q-item-label class="title-2 text-left">
                                example.ez
                            </q-item-label>
                        </q-item-section>
                        <q-item-section class="q-mr-md" side>
                            <q-item-label class="title-3">$69.99</q-item-label>
                        </q-item-section>
                        <q-item-section side>
                            <q-btn flat no-caps class="bg-ez-purple-1" size="sm">
                                <span class="text-2 text-white">Register</span>
                            </q-btn>
                        </q-item-section>
                    </q-item>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';

export default {
    name: 'PremiumDomains',
    layout: Guest,
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