<template>
    <div class="container">
        <div class="text-4 q-px-md q-mt-xl">
            <div class="title">Learn about</div>


            <div class="frame-3">
                <div class="handshake-logo">
                    <img class="fill" src="https://c.animaapp.com/crkyC7VV/img/fill-1.svg" />
                </div>

                <div class="title-alt">Handshake</div>
            </div>
        </div>

        <div class="frame-4 bg-ez-purple">
            <iframe 
                @load="iframeLoaded = true"
                width="100%"
                height="100%"
                src="https://www.youtube-nocookie.com/embed/Q3QbXMFjro0"
                title="YouTube video player"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            />
            
            <q-inner-loading :showing="!iframeLoaded">
                <q-spinner size="50px" color="ez-purple-1" />
            </q-inner-loading>
        </div>

        <q-separator class="q-mt-md divider full-width" style="max-width: 700px;" />

        <q-card class="bg-transparent q-mb-lg" style="border-radius: 8px;width: 85vw;max-width: 700px;" flat>
            <p>
                <span class="text-wrapper">
                    Owning a .ez subdomain on the Handshake network is not just a practical choice, but also an exciting
                    opportunity to be part of a revolutionary new way of thinking about domain ownership.
                    <br /><br />
                    By embracing Handshake, you join a growing community of forward-thinking individuals and organizations
                    who are eager to break free from the limitations and restrictions of traditional DNS systems. With
                    Handshake, you have complete control over your domain name, and you can use it as you see fit without
                    worrying about centralized authorities or third-party intermediaries.
                    <br /><br />
                    This level of ownership and control is a game-changer for individuals and businesses alike, offering 
                    unparalleled security, privacy, and flexibility. By owning a .ez subdomain on the Handshake network, 
                    you can take advantage of this new level of ownership and control, secure in the knowledge that you 
                    are part of a community that is changing the way we think about domain names.
                    <br /><br />
                    What&#39;s more, Handshake is an open and permission less system that encourages innovation and 
                    collaboration. By owning a .ez subdomain, you have access to a wide range of new applications and 
                    services that are being built on top of the Handshake network. This means that your domain can be 
                    much more than just a simple address, but rather a gateway to a whole new world of possibilities and 
                    opportunities.
                    <br />
                    So if you&#39;re ready to take control of your domain and be part of a revolutionary new way of thinking 
                    about domain ownership, then owning a .ez subdomain on the Handshake network is the way to go.
                    <br /><br />
                    If you would like to go further into detail &amp; learn more you can do so at 
                    <a href="https://handshake.org/" target="_blank" rel="noopener noreferrer">handshake.org</a>
                </span>
            </p>
        </q-card>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';

export default {
    name: 'Learn',
    layout: Guest,
    data() {
        return {
            iframeLoaded: false,
        };
    },
}
</script>

<style lang="scss" scoped>
.handshake-logo {
    position: relative;
    width: 32px;
    height: 32px;
    // box-shadow: 0.72px 0.72px 1.44px #00000014;
    box-shadow: none;
}

.fill {
    position: absolute;
    width: 31px;
    height: 32px;
    top: 0;
    left: 1px;
}

.frame-4 {
    position: relative;
    width: 395.25px;
    height: 222px;
    border-radius: 16px;
    box-shadow: var(--shadow-xl);
    background: var(--color-mode-mode-base-100);
    // background-image: url(https://c.animaapp.com/ync7isyI/img/image-7@2x.png);
    background-size: 100% 100%;
}

.divider {
    background-color: var(--color-mode-mode-base-200) !important;
}

.text-wrapper {
    font-family: var(--text-base-font-regular-font-family);
    font-weight: var(--text-base-font-regular-font-weight);
    color: rgb(26, 26, 26);
    font-size: var(--text-base-font-regular-font-size);
    letter-spacing: var(--text-base-font-regular-letter-spacing);
    line-height: var(--text-base-font-regular-line-height);
    font-style: var(--text-base-font-regular-font-style);
}

.title-alt {
    position: relative;
    width: fit-content;
    margin-top: -1px;
    font-family: var(--text-3xl-font-bold-font-family);
    font-weight: var(--text-3xl-font-bold-font-weight);
    color: var(--color-mode-mode-alpha-900);
    font-size: var(--text-3xl-font-bold-font-size);
    text-align: center;
    letter-spacing: var(--text-3xl-font-bold-letter-spacing);
    line-height: var(--text-3xl-font-bold-line-height);
    white-space: nowrap;
    font-style: var(--text-3xl-font-bold-font-style);
}
</style>