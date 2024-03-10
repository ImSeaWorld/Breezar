<template>
    <div class="container">
        <div class="text-4 q-px-md q-mt-xl">
            <div class="title">Contact Us</div>
            <p class="text-wrapper">Get in touch with us using the form below.</p>
        </div>
        
        <q-card class="bg-transparent q-my-lg" style="border-radius: 8px;width: 85vw;max-width: 975px;" flat>
            <q-card-section>
                <q-form>
                    <div class="form-input">
                        <div class="label">
                            Name
                        </div>

                        <div class="container-wrapper full-width cursor-text q-pa-md" style="border-radius: 8px;" @click="$refs.search.focus()">
                            <div class="container-2">
                                <input
                                    type="text"
                                    v-model="form.name"
                                    class="search-domains"
                                    placeholder="Enter your name"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-input">
                        <div class="label">
                            Email
                        </div>

                        <div class="container-wrapper full-width cursor-text q-pa-md" style="border-radius: 8px;" @click="$refs.search.focus()">
                            <div class="container-2">
                                <input
                                    type="text"
                                    v-model="form.email"
                                    class="search-domains"
                                    placeholder="Enter your email"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-input">
                        <div class="label">
                            Message
                        </div>

                        <div class="container-wrapper full-width cursor-text q-pa-md" style="border-radius: 8px;" @click="$refs.search.focus()">
                            <div class="container-2">
                                <textarea
                                    type="text"
                                    v-model="form.message"
                                    class="search-domains text-area"
                                    placeholder="Enter your message"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-subtitle1 text-center q-py-xs" style="font-size: 0.65rem; opacity: 0.5;">
                        This site is protected by reCAPTCHA and the Google <a rel="noopener" role="link" aria-haspopup="true" data-ux="Link" target="_blank" href="https://policies.google.com/privacy">Privacy Policy</a> and <a rel="noopener" role="link" aria-haspopup="true" data-ux="Link" target="_blank" href="https://policies.google.com/terms">Terms of Service</a> apply.
                    </div>
                    
                    <q-btn
                        flat
                        no-caps
                        class="bg-ez-purple text-white button-2 full-width"
                    >
                        <div class="text-5">Send Message</div>
                    </q-btn>

                </q-form>
            </q-card-section>
        </q-card>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';

export default {
    name: 'Contact',
    layout: Guest,
    data() {
        return {
            form: {
                name: '',
                email: '',
                message: '',
                recaptchaResponse: '',
            }
        }
    },
}
</script>

<style lang="scss" scoped>
.form-input {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    width: 100%;
    margin-bottom: 1.5rem;

    .label {
        font-weight: lighter;
        margin-bottom: 0.5rem;
    }

    &:nth-child(3) {
        margin-bottom: 0 !important;
    }

    .container-wrapper {
        border: 1px solid;
        border-color: var(--color-mode-mode-base-200);
        box-shadow: var(--shadow-sm);

        .container-2 {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;

            .text-area {
                min-height: 28px;
                height: 100px;
                resize: vertical;
            }
        }
    }
}
</style>