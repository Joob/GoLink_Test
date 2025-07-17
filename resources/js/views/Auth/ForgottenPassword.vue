<template>
<div class="landing-page">

    <!--Navigation-->
    <Navigation class="page-wrapper medium"/>

    <br><br>

    <AuthContentWrapper ref="auth">
        <!--Forgotten your password?-->
        <AuthContent name="forgotten-password" :visible="true">
            <Headline
                :title="$t('page_forgotten_password.title')"
                :description="$t('page_forgotten_password.subtitle')"
            />

            <ValidationObserver
                @submit.prevent="forgottenPassword"
                ref="forgotten_password"
                tag="form"
                class="mb-12 items-start space-y-4 md:flex md:space-x-4 md:space-y-0"
            >
                <ValidationProvider
                    tag="div"
                    mode="passive"
                    class="relative w-full text-left"
                    name="E-Mail"
                    rules="required"
                    v-slot="{ errors }"
                >
                    <input
                        v-model="recoverEmail"
                        :placeholder="$t('page_login.placeholder_email')"
                        type="email"
                        class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                        :class="{ '!border-rose-600': errors[0] }"
                    />
                    <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                </ValidationProvider>

                <AuthButton
                    class="w-full justify-center md:w-min"
                    icon="chevron-right"
                    :text="$t('get_link')"
                    :loading="isLoading"
                    :disabled="turnstileInvalid"
                />
            </ValidationObserver>

            <div class="center-element">
                <!-- Add the Turnstile API token as a hidden input field -->
                <div id="cloudFlare-Turnstile"></div>
            </div>

            <br>

            <span class="block">
                {{ $t('page_forgotten_password.password_remember_text') }}
                <router-link :to="{ name: 'SignIn' }" class="text-theme font-bold">
                    {{ $t('log_in') }}
                </router-link>
            </span>
            
        </AuthContent>

        <!--Password reset link send-->
        <AuthContent name="password-reset-link-sended" :visible="false">
            <Headline
                :title="$t('page_forgotten_password.pass_sennded_title')"
                :description="$t('page_forgotten_password.pass_sennded_subtitle')"
            />

            <span class="block">
                {{ $t('page_forgotten_password.password_remember_text') }}
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
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import AuthContentWrapper from '../../components/Layout/AuthPages/AuthContentWrapper'
import AuthContent from '../../components/Layout/AuthPages/AuthContent'
import PageFooter from '../../components/IndexPage/IndexPageFooter'
import Navigation from '../../components/IndexPage/IndexNavigation'
import AuthButton from '../../components/UI/Buttons/AuthButton'
import { required } from 'vee-validate/dist/rules'
import Headline from '../../components/UI/Labels/LogoHeadline'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'ForgottenPassword',
    components: {
        AuthContentWrapper,
        ValidationProvider,
        ValidationObserver,
        AuthContent,
        AuthButton,
        PageFooter,
        Navigation,
        required,
        Headline,
    },
    computed: {
        ...mapGetters(['config']),
    },
    data() {
        return {
            isLoading: false,
            recoverEmail: '',
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
        goToAuthPage(slug) {
            this.$refs.auth.$children.forEach((page) => {
                // Hide current step
                page.isVisible = false

                if (page.$props.name === slug) {
                    // Go to next step
                    page.isVisible = true
                }
            })
        },
        async forgottenPassword() {
            // Validate fields
            const isValid = await this.$refs.forgotten_password.validate()

            if (!isValid) return

            // Start loading
            this.isLoading = true

            // Send request to get user reset link
            axios
                .post('/api/password/recover', {
                    email: this.recoverEmail,
                })
                .then(() => {
                    this.goToAuthPage('password-reset-link-sended')
                })
                .catch((error) => {
                    if (error.response.status === 422) {
                        this.$refs.forgotten_password.setErrors({
                            'E-Mail': error.response.data.message,
                        })
                    }
                })
                .finally(() => (this.isLoading = false))
        },
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
