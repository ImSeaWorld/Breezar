<template>
    <div class="absolute-full" data-color-mode-mode="dark">
        <Navbar />

        <slot />
    </div>
</template>

<script>
import Navbar from '@/Components/Navbar.vue';

export default {
    name: 'GuestLayout',
    components: {
        Navbar,
    },
}
</script>

<style>
body {
    background-color: rgba(255, 255, 255, 1);
    background: linear-gradient(
        180deg, 
        rgb(255, 255, 255) 15%, 
        rgb(175, 147, 225) 100%, 
        rgba(82, 22, 190, 0.44) 100%
    );
    
    /* background-size: cover; */
    /* background-position: center; */
    background-repeat: no-repeat;
    background-attachment: fixed;
}
</style>
