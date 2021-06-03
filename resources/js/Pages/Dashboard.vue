<template>
    <breeze-authenticated-layout>
        <template #header>
            <span class="text-h6 text-center">
                Dashboard
            </span>
        </template>

        <div class="q-py-md">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
    </breeze-authenticated-layout>
</template>

<script>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated';

export default {
    components: {
        BreezeAuthenticatedLayout,
    },

    props: {
        auth: Object,
        errors: Object,
    },
};
</script>
