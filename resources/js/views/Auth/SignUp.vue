<template>
<div class="landing-page">

    <!--Navigation-->
    <Navigation class="page-wrapper medium"/>

    <br><br>

    <AuthContentWrapper ref="auth">
        <!--Registration-->
        <AuthContent name="sign-up" :visible="true" class="mt-4 mb-12">
            <Headline :title="$t('page_registration.title')" :description="$t('page_registration.subtitle')" />

            <ValidationObserver
                @submit.prevent="signUp"
                ref="sign_up"
                tag="form"
                class="mb-12 space-y-4 text-left"
            >
                <div class="mx-auto mb-5 md:flex md:max-w-lg md:items-center">
                    <label class="mb-1.5 block font-bold md:mb-0 md:w-72 md:pr-4 md:text-right">
                        {{ $t('email') }}:
                    </label>
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="w-full text-left"
                        name="E-Mail"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="register.email"
                            :placeholder="$t('page_registration.placeholder_email')"
                            type="email"
                            class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <div class="mx-auto mb-5 md:flex md:max-w-lg md:items-center">
                    <label class="mb-1.5 block font-bold md:mb-0 md:w-72 md:pr-4 md:text-right">
                        {{ $t('full_name') }}:
                    </label>
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="w-full text-left"
                        name="Full Name"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="register.name"
                            :placeholder="$t('page_registration.placeholder_name')"
                            type="text"
                            class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <div class="mx-auto mb-5 md:flex md:max-w-lg md:items-center">
                    <label class="mb-1.5 block font-bold md:mb-0 md:w-72 md:pr-4 md:text-right">
                        {{ $t('page_registration.label_pass') }}:
                    </label>
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="w-full text-left"
                        name="Your New Password"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="register.password"
                            :placeholder="$t('new_password')"
                            type="password"
                            class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <div class="mx-auto mb-5 md:flex md:max-w-lg md:items-center">
                    <label class="mb-1.5 block font-bold md:mb-0 md:w-72 md:pr-4 md:text-right">
                        {{ $t('confirm_password') }}:
                    </label>
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="w-full text-left"
                        name="Confirm Your Password"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="register.password_confirmation"
                            :placeholder="$t('page_registration.placeholder_confirm_pass')"
                            class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                            type="password"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </div>

                <br>

                <div class="center-element">
                    <!-- Add the Turnstile API token as a hidden input field -->
                    <div id="cloudFlare-Turnstile"></div>
                </div>


                <div class="text-center">
<!-- GoLink CHANGE HERE -->
                    <i18n v-if="$i18n.locale === 'pt'" path="page_registration.agreement" tag="p" class="mx-auto mt-12 mb-6 w-96 font-bold">
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'termos-e-servicos' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ termsOfService_pt.title }}
                        </router-link>
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'privacidade' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ privacyPolicy_pt.title }}
                        </router-link>
                    </i18n>
                    <i18n v-if="$i18n.locale === 'fr'" path="page_registration.agreement" tag="p" class="mx-auto mt-12 mb-6 w-96 font-bold">
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'termes-et-services' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ termsOfService_fr.title }}
                        </router-link>
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'politique-de-confidentialite' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ privacyPolicy_fr.title }}
                        </router-link>
                    </i18n>
                    <i18n v-if="$i18n.locale === 'en'" path="page_registration.agreement" tag="p" class="mx-auto mt-12 mb-6 w-96 font-bold">
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'terms-of-services' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ termsOfService_en.title }}
                        </router-link>
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'privacy-policy' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ privacyPolicy_en.title }}
                        </router-link>
                    </i18n>
                    <i18n v-if="$i18n.locale === 'es'" path="page_registration.agreement" tag="p" class="mx-auto mt-12 mb-6 w-96 font-bold">
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'terminos-de-servicios' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ termsOfService_es.title }}
                        </router-link>
                        <router-link
                            :to="{
                                name: 'DynamicPage',
                                params: { slug: 'politica-privacidade' },
                            }"
                            target="_blank"
                            class="text-theme"
                        >
                            {{ privacyPolicy_es.title }}
                        </router-link>
                    </i18n>

                    <AuthButton
                        class="w-full justify-center md:w-min"
                        icon="chevron-right"
                        :text="$t('create_account')"
                        :loading="isLoading"
                        :disabled="turnstileInvalid"
                    />

                </div>
            </ValidationObserver>

            <SocialLoginButtons />

            <span class="block"
                >{{ $t('page_registration.have_an_account') }}
                <router-link :to="{ name: 'SignIn' }" class="text-theme font-bold">
                    {{ $t('log_in') }}
                </router-link>
            </span>
        </AuthContent>
    </AuthContentWrapper>

    <!--Footer-->
    <PageFooter/>

    </div>
</template>

<script>
import Headline from '../../components/UI/Labels/LogoHeadline'
import AuthContentWrapper from '../../components/Layout/AuthPages/AuthContentWrapper'
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import AuthContent from '../../components/Layout/AuthPages/AuthContent'
import PageFooter from '../../components/IndexPage/IndexPageFooter'
import Navigation from '../../components/IndexPage/IndexNavigation'
import AuthButton from '../../components/UI/Buttons/AuthButton'
import SocialLoginButtons from '../../components/UI/Buttons/SocialLoginButtons'
import { required } from 'vee-validate/dist/rules'
import { mapGetters } from 'vuex'
import { events } from '../../bus'
import axios from 'axios'

export default {
    name: 'SignUp',
    components: {
        SocialLoginButtons,
        AuthContentWrapper,
        ValidationProvider,
        ValidationObserver,
        AuthContent,
        PageFooter,
        Navigation,
        AuthButton,
        Headline,
        required,
    },
    computed: {
        ...mapGetters(['config']),
// GoLink CHANGE HERE
        privacyPolicy_pt() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'privacidade'
            })
        },
        termsOfService_pt() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'termos-e-servicos'
            })
        },
        privacyPolicy_fr() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'politique-de-confidentialite'
            })
        },
        termsOfService_fr() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'termes-et-services'
            })
        },
        privacyPolicy_en() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'privacy-policy'
            })
        },
        termsOfService_en() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'terms-of-services'
            })
        },
        privacyPolicy_es() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'politica-privacidade'
            })
        },
        termsOfService_es() {
            return this.config.legal.find((legal) => {
                return legal.slug === 'terminos-de-servicios'
            })
        },
// GoLink END
    },
    data() {
        return {
            isLoading: false,
            register: {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
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

        async signUp() {
            // Validate fields
            const isValid = await this.$refs.sign_up.validate()

            if (!isValid) return

            // Start loading
            this.isLoading = true

            // Get Cloudflare Captcha token
            if (this.config.allowedCloudflareCaptcha) {
                this.register.cfCaptchaToken = await this.$cfCaptchaToken()
                    .then((response) => response)
            }

            // Get ReCaptcha token
            if (this.config.allowedRecaptcha) {
				this.register.reCaptcha = await this.$reCaptchaToken('register')
					.then((response) => response)
			}

            // Send request to get user token
            axios
                .post('/api/register', this.register)
                .then(() => {
                    if (!this.config.userVerification) {
                        // Set login state
                        this.$store.commit('SET_AUTHORIZED', true)

                        // Go to files page
                        this.$router.push({ name: 'Files' })
                    } else {
                        // Go to SuccessfullySend page
                        this.$router.push({ name: 'SuccessfullySend' })
                    }
                })
                .catch((error) => {
                    if (error.response.status === 500) {
						events.$emit('alert:open', {
							title: this.$t('popup_error.title'),
							message: this.$t('popup_error.message'),
						})
                    }

					if (error.response.status === 409) {

						events.$emit('alert:open', {
							title: error.response.data.message,
						})
					}

                    if (error.response.status === 422) {
                        if (error.response.data.errors['email']) {
                            this.$refs.sign_up.setErrors({
                                'E-Mail': error.response.data.errors['email'],
                            })
                        }

                        if (error.response.data.errors['password']) {
                            this.$refs.sign_up.setErrors({
                                'Your New Password': error.response.data.errors['password'],
                            })
                        }

                        if (error.response.data.errors['reCaptcha']) {
							events.$emit('alert:open', {
								title: error.response.data.message,
							})
                        }
                    }
                })
				.finally(() => {
                    // End loading
                    this.isLoading = false
				})
        },
    },
    created() {
        this.$scrollTop()

		// Redirect if user is authenticated
		if (this.$root.$data.config.isAuthenticated) {
			this.$router.push({name: 'Files'})
		}

		if (this.config.isPrefilledUsers) {
			this.register = {
				name: 'John Doe',
				email: 'demo-' + Math.floor(Math.random() * 100000) + '@doe.com',
				password: 'pupu',
				password_confirmation: 'pupu',
				reCaptcha: null,
			}
		}
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

  .center-element {
    display: flex;
    justify-content: center;
    align-items: center;
  }

</style>