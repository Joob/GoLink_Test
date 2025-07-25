<template>
    <div class="landing-page">
        <!--Navigation-->
        <Navigation class="page-wrapper medium" />

        <!--Page content-->
        <div class="page-wrapper small">
            <!--Headline-->
            <PageTitle
                class="headline"
                :title="$t('contact_us')"
                :description="$t('page_contact_us.description')"
            ></PageTitle>

            <ValidationObserver
                v-if="!isSuccess"
                @submit.prevent="contactForm"
                ref="contactForm"
                tag="form"
                class="form block-form"
            >
                <div class="block-wrapper">
                    <label>{{ $t('page_contact_us.name') }}:</label>
                    <ValidationProvider tag="div" mode="passive" name="Full Name" rules="required" v-slot="{ errors }">
                    <input
                            v-model="contact.name"
                            :placeholder="$t('page_contact_us.form.name')"
                            type="name"
                            class="focus-border-theme input-dark"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                    <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>
                
                <div class="block-wrapper">
                    <label>{{ $t('email') }}:</label>
                    <ValidationProvider tag="div" mode="passive" name="E-Mail" rules="required" v-slot="{ errors }">
                        <input
                            v-model="contact.email"
                            :placeholder="$t('page_contact_us.form.email_plac')"
                            type="email"
                            class="focus-border-theme input-dark"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <div class="block-wrapper">
                    <label>{{ $t('message') }}:</label>
                    <ValidationProvider tag="div" mode="passive" name="Message" rules="required" v-slot="{ errors }">
                        <textarea
                            v-model="contact.message"
                            :placeholder="$t('page_contact_us.form.message_plac')"
                            rows="6"
                            class="focus-border-theme input-dark"
                            :class="{ '!border-rose-600': errors[0] }"
                        ></textarea>
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <InfoBox v-if="isError">
                    <p>{{ $t('page_contact_us.error_message') }}</p>
                </InfoBox>

                <br>
                <div class="center-element">
                    <!-- Add the Turnstile API token as a hidden input field -->
                    <div id="cloudFlare-Turnstile"></div>
                </div>
                <br>

                <div>
                    <AuthButton
                        class="submit-button"
                        icon="chevron-right"
                        :text="$t('send_message')"
                        :loading="isLoading"
                        :disabled="turnstileInvalid"
                    />
                </div>

            </ValidationObserver>

            <InfoBox v-if="isSuccess" class="!mb-0">
                <p>{{ $t('page_contact_us.success_message') }}</p>
            </InfoBox>
        </div>

        <!--Footer-->
        <PageFooter />
    </div>
</template>

<script>
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import PageTitle from '../../components/IndexPage/Components/PageTitle'
import PageFooter from '../../components/IndexPage/IndexPageFooter'
import Navigation from '../../components/IndexPage/IndexNavigation'
import InfoBox from '../../components/UI/Others/InfoBox'
import AuthButton from '../../components/UI/Buttons/AuthButton'
import { required } from 'vee-validate/dist/rules'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'ContactUs',
    components: {
        ValidationProvider,
        ValidationObserver,
        AuthButton,
        PageFooter,
        Navigation,
        PageTitle,
        required,
        InfoBox,
    },
    computed: {
        ...mapGetters(['config']),
    },
    data() {
        return {
            isLoading: false,
            isSuccess: false,
            isError: false,
            contact: {
                email: '',
                message: '',
                reCaptcha: null,
            },
            turnstileInvalid: true,
        }
    },
    methods: {
        loadTurnstile() {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            script.async = true;
            script.defer = true;
            script.onload = () => {
                //console.debug('_turnstileCb called');
                turnstile.render('#cloudFlare-Turnstile', {
                    sitekey: '0x4AAAAAAABrsaU6zxxb7KoQ',
                    theme: 'light',
                    callback: (isValid) => {
                        this.turnstileInvalid = !isValid;
                    },
                });
            };
            document.head.appendChild(script);
        },
        async contactForm() {
            // Validate fields
            const isValid = await this.$refs.contactForm.validate()

            if (!isValid) return

            // Start loading
            this.isLoading = true

            // Get ReCaptcha token
			if (this.config.allowedRecaptcha) {
				this.contact.reCaptcha = await this.$reCaptchaToken('register')
					.then((response) => response)
			}

            // Send request to get user token
            axios
                .post('/api/contact', this.contact)
                .then(() => {
                    this.isSuccess = true
                })
                .catch(() => {
                    this.isError = true
                })
                .finally(() => {
                    // End loading
                    this.isLoading = false
                })
        },
    },
    created() {
        this.$scrollTop()
    },
    mounted() {
        this.loadTurnstile();
    },
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/landing-page';
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';
@import '../../../sass/vuefilemanager/forms';

.form {
    max-width: 100%;
}

.headline {
    padding-top: 70px;
    padding-bottom: 50px;
}

.form.block-form {
    .submit-button {
        margin-top: 20px;
        margin-left: 0;
        margin-right: 0;
    }
}

.dark {
}

@media only screen and (max-width: 960px) {
    .headline {
        padding-top: 50px;
        padding-bottom: 30px;
    }
}

@media only screen and (max-width: 530px) {
    .center-element {
        display: flex;
        justify-content: center;
        align-items: center;
    }
}
</style>
