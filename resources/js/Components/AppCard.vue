<template>
    <q-card flat square bordered v-bind="$attrs">
        <slot />
    </q-card>
</template>

<script>
export default {
    name: 'AppCard',
    inheritAttrs: false,
};
</script>