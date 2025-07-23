<template>
    <div class="landing-page">
        <!--Navigation-->
        <Navigation class="page-wrapper medium"/>

        <br><br>

        <AuthContentWrapper ref="auth">
            <!--Log In by Email-->
            <AuthContent name="log-in" :visible="true">
                <Headline :title="$t('welcome_back')" :description="$t('page_login.subtitle')" />

                <ValidationObserver
                    @submit.prevent="logIn"
                    ref="log_in"
                    tag="form"
                    class="mb-12 items-start space-y-4 md:flex md:space-x-4 md:space-y-0"
                >
                    <ValidationProvider
                        class="w-full text-left"
                        tag="div"
                        mode="passive"
                        name="E-Mail"
                        rules="required"
                        v-slot="{ errors }"
                    >
                    <input
                        class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                        :class="{ '!border-rose-600': errors[0] }"
                        v-model="loginEmail"
                        :placeholder="$t('page_login.placeholder_email')"
                        type="email"
                    />
                    <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>

                    </ValidationProvider>

                    <AuthButton
                        class="w-full justify-center md:w-min"
                        icon="chevron-right"
                        :text="$t('next_step')"
                        :loading="isLoading"
                        :disabled="turnstileInvalid"
                    />

                </ValidationObserver>

                <SocialLoginButtons />

                <div class="center-element">
                    <!-- Add the Turnstile API token as a hidden input field -->
                    <div id="cloudFlare-Turnstile"></div>
                </div>

                <br>

                <span v-if="config.userRegistration" class="block">
                    {{ $t('page_login.registration_text') }}
                    <router-link class="text-theme font-bold" :to="{ name: 'SignUp' }">
                    {{ $t('page_login.registration_button') }}
                    </router-link>
                </span>

                </AuthContent>

            <!--Log in By Password-->
            <AuthContent name="sign-in" :visible="false">
                <Headline
                    v-if="checkedAccount"
                    :title="$t('page_sign_in.title', { name: maskName(checkedAccount.name) })"
                    :description="$t('page_sign_in.subtitle')"
                >
                    <img
                        v-if="checkedAccount.avatar"
                        class="user-avatar mx-auto mb-6 w-28 rounded-xl shadow-xl"
                        :src="checkedAccount.avatar.md"
                        :alt="checkedAccount.name"
                    />
                </Headline>

                <ValidationObserver
                    @submit.prevent="singIn"
                    ref="sign_in"
                    tag="form"
                    class="mb-12 items-start space-y-4 md:flex md:space-x-4 md:space-y-0"
                >
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="w-full text-left"
                        name="User Password"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="loginPassword"
                            :placeholder="$t('page_sign_in.placeholder_password')"
                            type="password"
                            ref="inputPassword"
                            class="dark:placeholder:text-gray-600 focus-border-theme h-full w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>

                    <AuthButton
                        class="w-full justify-center md:w-min"
                        icon="chevron-right"
                        :text="$t('log_in')"
                        :loading="isLoading"
                        :disabled="isLoading"
                    />
                </ValidationObserver>

                <span class="block">
                    {{ $t('page_sign_in.password_reset_text') }}
                    <router-link :to="{ name: 'ForgottenPassword' }" class="text-theme font-bold">
                        {{ $t('page_sign_in.password_reset_button') }}
                    </router-link>
                </span>
            </AuthContent>

            <!--Resend verification email-->
            <AuthContent name="not-verified" :visible="false">
                <Headline
                    v-if="checkedAccount"
                    :title="$t('page_sign_in_2fa_title', { name: maskName(checkedAccount.name) })"
                    :description="$t('page_not_verified.subtitle')"
                >
                    <img
                        v-if="checkedAccount.avatar"
                        class="user-avatar mx-auto mb-6 w-28 rounded-xl shadow-xl"
                        :src="checkedAccount.avatar.md"
                        :alt="checkedAccount.name"
                    />
                </Headline>

                <span class="block">
                    {{ $t('page_not_verified.resend_text') }}
                    <b @click="resendEmail" class="text-theme cursor-pointer">
                        {{ $t('page_not_verified.resend_button') }}
                    </b>
                </span>
            </AuthContent>


            <!--Log in With OTP code -->
            <AuthContent name="otp-auth" :visible="false">
                <Headline
                    class="user"
                    v-if="checkedAccount"
                    :title="$t('page_sign_in.title', { name: maskName(checkedAccount.name) })"
                    :description="$t('page_sign_in.subtitle_otp')"
                >
                    <img
                        v-if="checkedAccount.avatar"
                        class="user-avatar mx-auto mb-6 w-28 rounded-xl shadow-xl"
                        :src="checkedAccount.avatar.md"
                        :alt="checkedAccount.name"
                    />
                </Headline>

                <ValidationObserver
                    @submit.prevent="authOtp"
                    ref="auth_otp"
                    tag="form"
                    class="form inline-form"
                >
                <ValidationProvider
                    tag="div"
                    mode="passive"
                    class="input-wrapper"
                    name="Security Code"
                    rules="required"
                    v-slot="{ errors }"
                >
                    <input
                        v-model="otpCode"
                        :placeholder="$t('page_sign_in.placeholder_otp')"
                        type="text"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        ref="inputOtpCode"
                        class="dark:placeholder:text-gray-600 focus-border-theme h-full w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                        :class="{ '!border-rose-600': errors[0] }"
                    />
                    <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                </ValidationProvider>

                <br>

                    <AuthButton
                        class="w-full justify-center md:w-min"
                        icon="chevron-right"
                        :text="$t('page_sign_in.button_otp_code')"
                        :loading="isLoading"
                        :disabled="isLoading"
                    />
                </ValidationObserver>

                <br>

                <span class="block">{{ $t('page_sign_in.resend_otp_code_text') }}
                    <br>
                    <b class="text-theme block cursor-pointer" v-on:click="resendOtpCode">
                        {{ $t('page_sign_in.resend_otp_code_button') }}
                    </b>
                </span>

                <!-- Display OTP status messages -->
                <div v-if="otpStatus.is_in_cooldown" class="mt-4 text-center">
                    <span class="text-sm text-red-600">
                        Muitas tentativas. Aguarde {{ otpStatus.remaining_cooldown }} segundos antes de tentar novamente.
                    </span>
                </div>
                <div v-else-if="otpStatus.attempts_used > 0" class="mt-4 text-center">
                    <span class="text-sm text-gray-600">
                        {{ otpStatus.attempts_used }}/5 tentativas utilizadas
                    </span>
                </div>

                <div class="relative mt-10 h-12 w-full">
                    <Spinner v-if="isLoading" class="spinner" />
                </div>

            </AuthContent>


            <!-- Log in by 2fa -->
            <AuthContent name="two-factor-authentication" :visible="false">
                <Headline
                    v-if="checkedAccount"
                    :title="$t('page_sign_in_2fa_title', { name: maskName(checkedAccount.name) })"
                    :description="$t('page_sign_in_2fa_subtitle')"
                >
                    <img
                        v-if="checkedAccount.avatar"
                        class="user-avatar mx-auto mb-6 w-28 rounded-xl shadow-xl"
                        :src="checkedAccount.avatar.md"
                        :alt="checkedAccount.name"
                    />
                </Headline>

                <ValidationObserver ref="two_factor_authentication" tag="form" class="mb-12">
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="mx-auto"
                        name="Two Factor Authentication"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="twoFactorCode"
                            ref="twoFactorCodeInput"
                            :placeholder="$t('page_sign_in.placeholder_2fa')"
                            @input="twoFactorChallenge(false)"
                            type="text"
                            maxlength="6"
                            class="dark:placeholder:text-gray-600 focus-border-theme h-full w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 text-center font-bold dark:bg-2x-dark-foreground md:w-80"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="mt-2 block text-center text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </ValidationObserver>

                <span class="block">
                    {{ $t('page_sign_in.2fa_recovery_text') }}
                    <b @click="goToAuthPage('two-factor-recovery')" class="text-theme cursor-pointer cursor-pointer">
                        {{ $t('page_sign_in.2fa_recovery_button') }}
                    </b>
                </span>

                <div class="relative mt-10 h-12 w-full">
                    <Spinner v-if="isLoading" class="spinner" />
                </div>
            </AuthContent>

            <!-- Log in by 2fa with recovery code -->
            <AuthContent name="two-factor-recovery" :visible="false">
                <Headline
                    v-if="checkedAccount"
                    :title="$t('page_sign_in_2fa_title', { name: maskName(checkedAccount.name) })"
                    :description="$t('page_sign_in.2fa_recovery_subtitle')"
                >
                    <img
                        v-if="checkedAccount.avatar"
                        class="user-avatar mx-auto mb-6 w-28 rounded-xl shadow-xl"
                        :src="checkedAccount.avatar.md"
                        :alt="checkedAccount.name"
                    />
                </Headline>

                <ValidationObserver ref="two_factor_recovery" tag="form" class="mb-12">
                    <ValidationProvider
                        tag="div"
                        mode="passive"
                        class="mx-auto"
                        name="Two Factor Recovery"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <input
                            v-model="twoFactorRecoveryCode"
                            :placeholder="$t('page_sign_in.placeholder_2fa_recovery')"
                            @input="twoFactorChallenge(true)"
                            type="text"
                            maxlength="21"
                            class="dark:placeholder:text-gray-600 focus-border-theme h-full w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 text-center font-bold dark:bg-2x-dark-foreground md:w-80"
                            :class="{ '!border-rose-600': errors[0] }"
                        />
                        <span class="mt-2 block text-center text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                    </ValidationProvider>
                </ValidationObserver>

                <b @click="goToAuthPage('two-factor-authentication')" class="text-theme block cursor-pointer">
                    {{ $t('2fa.i_have_2fa_app') }}
                </b>

                <div v-if="isLoading" class="relative mt-10 h-12 w-full">
                    <Spinner class="spinner" />
                </div>

            </AuthContent>
        </AuthContentWrapper>

        <!--Footer-->
        <PageFooter/>

        </div>
    </template>

    <script>
    import AuthContentWrapper from '../../components/Layout/AuthPages/AuthContentWrapper'
    import { ValidationObserver, ValidationProvider } from 'vee-validate/dist/vee-validate.full'
    import SocialLoginButtons from '../../components/UI/Buttons/SocialLoginButtons'
    import AuthContent from '../../components/Layout/AuthPages/AuthContent'
    import PageFooter from '../../components/IndexPage/IndexPageFooter'
    import Navigation from '../../components/IndexPage/IndexNavigation'
    import AuthButton from '../../components/UI/Buttons/AuthButton'
    import Spinner from '../../components/UI/Others/Spinner'
    import { mapGetters } from 'vuex'
    import { events } from '../../bus'
    import axios from 'axios'
    import Headline from '../../components/UI/Labels/LogoHeadline'
    import { required } from 'vee-validate/dist/rules'

    export default {
        name: 'SignIn',
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
            Spinner,
            required,
        },
        computed: {
            ...mapGetters(['config']),
        },
        data() {
            return {
                isVisible: false,
                isLoading: false,
                validSignIn: false,
                checkedAccount: undefined,
                loginPassword: '',
                loginEmail: '',
                otpCode: '',
                token: '',
                twoFactorCode: '',
                twoFactorRecoveryCode: '',
                turnstileInvalid: true,
                otpSent: false,
                lastOtpSentTime: null,
                otpAttemptsUsed: 0,
                cooldownStartTime: null,
                cooldownDuration: 120000, // 2 minutes cooldown
                otpValidityDuration: 300000, // 5 minutes OTP validity (adjust as needed)
                otpStatus: {
                    attempts_used: 0,
                    is_in_cooldown: false,
                    remaining_cooldown: 0,
                    is_otp_valid: false,
                    remaining_validity: 0,
                    can_send_otp: true,
                    remaining_wait: 0,
                }
            }
        },
        methods: {
            maskName(name) {
                const firstCharacter = name.charAt(0);
                const maskedCharacters = name.slice(1).replace(/\S/g, '*');
                return firstCharacter + maskedCharacters;
            },
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
            onCaptchaVerified(response) {
                // Handle the response from the Cloudflare CAPTCHA verification
                console.log(response);
            },
            goToAuthPage(slug) {
                this.$refs.auth.$children.forEach((page) => {
                    // Hide current step
                    page.isVisible = page.$props.name === slug
                })
            },
            resendEmail() {
                axios
                    .post('/api/user/verify', {
                        email: this.loginEmail,
                    })
                    .then(() => {
                        this.$router.push({ name: 'SuccessfullySend' })
                    })
                    .catch(() => {
                        this.$isSomethingWrong()
                    })
            },
            // Check if OTP is still valid (within validity period)
            isOtpStillValid() {
                if (!this.lastOtpSentTime) return false;
                
                const now = new Date().getTime();
                const timeSinceOtp = now - this.lastOtpSentTime;
                
                return timeSinceOtp < this.otpValidityDuration;
            },
            // Get remaining OTP validity time
            getRemainingOtpValidity() {
                if (!this.lastOtpSentTime) return '';
                
                const now = new Date().getTime();
                const timeSinceOtp = now - this.lastOtpSentTime;
                const remaining = this.otpValidityDuration - timeSinceOtp;
                
                if (remaining <= 0) return '';
                
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                
                if (minutes > 0) {
                    return `${minutes}m ${seconds}s`;
                } else {
                    return `${seconds}s`;
                }
            },
            // Get how long ago OTP was sent
            getOtpAge() {
                if (!this.lastOtpSentTime) return '';
                
                const now = new Date().getTime();
                const timeSinceOtp = now - this.lastOtpSentTime;
                
                const minutes = Math.floor(timeSinceOtp / 60000);
                const seconds = Math.floor((timeSinceOtp % 60000) / 1000);
                
                if (minutes > 0) {
                    return `${minutes}m ${seconds}s`;
                } else {
                    return `${seconds}s`;
                }
            },
            // Get remaining cooldown time in milliseconds
            getRemainingCooldownTime() {
                if (!this.cooldownStartTime) return 0;
                
                const now = new Date().getTime();
                const timeSinceCooldown = now - this.cooldownStartTime;
                const remaining = this.cooldownDuration - timeSinceCooldown;
                
                return remaining > 0 ? remaining : 0;
            },
            // Check if we can send OTP
            canSendOtp() {
                // If still in cooldown period after 5 attempts
                if (this.getRemainingCooldownTime() > 0) {
                    return false;
                }
                
                // If cooldown period is over, reset attempts
                if (this.cooldownStartTime && this.getRemainingCooldownTime() === 0) {
                    this.resetOtpAttempts();
                }
                
                // If OTP is still valid, don't send new one
                if (this.isOtpStillValid()) {
                    return false;
                }
                
                // Check if we've hit the 5 attempt limit
                if (this.otpAttemptsUsed >= 5) {
                    // Start cooldown
                    this.cooldownStartTime = new Date().getTime();
                    return false;
                }
                
                // Basic rate limiting between attempts (30 seconds)
                if (this.lastOtpSentTime) {
                    const now = new Date().getTime();
                    const timeSinceLastOtp = now - this.lastOtpSentTime;
                    const minimumInterval = 30000; // 30 seconds between attempts
                    
                    return timeSinceLastOtp > minimumInterval;
                }
                
                return true;
            },
            // Reset attempts (but keep cooldown intact if active)
            resetOtpAttempts() {
                this.otpAttemptsUsed = 0;
                // Don't reset lastOtpSentTime if OTP is still valid
                if (!this.isOtpStillValid()) {
                    this.lastOtpSentTime = null;
                    this.otpSent = false;
                }
                // Only reset cooldown if it's expired
                if (this.getRemainingCooldownTime() === 0) {
                    this.cooldownStartTime = null;
                }
            },
            // Full reset for new login session
            fullResetOtpAttempts() {
                this.otpAttemptsUsed = 0;
                this.lastOtpSentTime = null;
                this.otpSent = false;
                this.cooldownStartTime = null;
            },
            async fetchOtpStatus() {
                if (!this.token) return;

                try {
                    const response = await axios.get('/api/user/otp-status', {
                        headers: {
                            Authorization: 'Bearer ' + this.token
                        }
                    });
                    this.otpStatus = response.data;
                } catch (error) {
                    console.error('Failed to fetch OTP status:', error);
                }
            },
            // Send OTP with smart validity checking
            async sendOtpCode() {
                try {
                    await axios.post('/api/user/send-otp-code', {}, {
                        headers: {
                            Authorization: 'Bearer ' + this.token
                        }
                    });
                    
                    this.goToAuthPage('otp-auth');
                    await this.fetchOtpStatus();
                    
                } catch (error) {
                    this.goToAuthPage('otp-auth');
                    
                    if (error.response?.data?.message) {
                        events.$emit('toaster', {
                            type: 'error',
                            message: error.response.data.message,
                        });
                    }
                    
                    await this.fetchOtpStatus();
                }
            },
            async resendOtpCode() {
                if (this.isLoading) return;

                this.isLoading = true;

                try {
                    await axios.post('/api/user/send-otp-code', {}, {
                        headers: {
                            Authorization: 'Bearer ' + this.token
                        }
                    });
                    
                    events.$emit('toaster', {
                        type: 'success',
                        message: 'Código OTP enviado com sucesso.',
                    });
                    
                } catch (error) {
                    let errorMessage = 'Erro ao enviar código OTP.';
                    
                    if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    events.$emit('toaster', {
                        type: 'error',
                        message: errorMessage,
                    });
                } finally {
                    this.isLoading = false;
                    await this.fetchOtpStatus();
                }
            },
            async logIn() {
                // Full reset on new login
                this.fullResetOtpAttempts();
                
                // Validate fields
                const isValid = await this.$refs.log_in.validate()

                if (!isValid) return

                // Start loading
                this.isLoading = true

                // Send request to get verify account
                axios
                    .post('/api/user/check', {
                        email: this.loginEmail,
                    })
                    .then((response) => {
                        // End loading
                        this.isLoading = false

                        this.checkedAccount = response.data

                        if (response.data.oauth_provider) {
                            // Redirect user to socialite login if he's accout is registered by socialite
                            this.$store.dispatch('socialiteRedirect', response.data.oauth_provider)
                        } else {
                            // Show sign in password page
                            this.goToAuthPage('sign-in')

                            this.$nextTick(() => {
                              this.$refs.inputPassword.focus()
                            })
                        }
                    })
                    .catch((error) => {
                        if (error.response.status == 404) {
                            this.$refs.log_in.setErrors({
                                'E-Mail': [error.response.data.message],
                            })
                        }

                        if (error.response.status == 500) {
                            events.$emit('toaster', {
                                type: 'error',
                                message: this.$t('popup_signup_error.message'),
                            })
                        }

                        // End loading
                        this.isLoading = false
                    })
            },
            async authOtp() {
                // Validate fields
                const isValid = await this.$refs.auth_otp.validate();
                if (!isValid) return;

                // Validate OTP format (6 digits)
                if (!/^\d{6}$/.test(this.otpCode)) {
                    this.$refs.auth_otp.setErrors({
                        'Security Code': ['OTP code must be 6 digits']
                    });
                    return;
                }

                // Start loading
                this.isLoading = true

                // Send request to get user token
                axios
                    .post('/api/user/validate-otp-code', {
                        otp_code: this.otpCode,
                        },
                    {
                        headers: {
                            Authorization: 'Bearer ' + this.token
                        }
                    })
                    .then(() => {
                        // End loading
                        this.isLoading = false
                        // Reset attempts on successful authentication
                        this.fullResetOtpAttempts();
                        // Set login state
                        this.$store.commit('SET_AUTHORIZED', true)
                        // Go to files page
                        this.$router.push({name: 'Files'})
                    })
                    .catch(error => {
                        // Already authed.
                        if (error.response.status === 400) {
                            this.$router.push({name: 'Files'})
                            return;
                        }
                        
                        // Handle validation errors
                        if (error.response.status === 422 && error.response.data.otp_code) {
                            this.$refs.auth_otp.setErrors({
                                'Security Code': [error.response.data.otp_code]
                            });
                        } else {
                            this.$refs.auth_otp.setErrors({
                                'Security Code': [this.$t('validation_errors.invalid_otp_code')]
                            });
                        }
                        
                        // End loading
                        this.isLoading = false
                    })
            },
            async singIn() {
                // Validate fields
                const isValid = this.validSignIn ? this.validSignIn : await this.$refs.sign_in.validate()

                if (!isValid) return

                if (!this.checkedAccount.verified) {
                    this.goToAuthPage('not-verified')
                    return
                }

                // Start loading
                this.isLoading = true

                // Send request to get user token
                axios
                    .post('/login', {
                        email: this.loginEmail,
                        password: this.loginPassword,
                    })
                    .then((response) => {
                        // End loading
                        this.isLoading = false
                        
                        this.token = response.data.token

                        // Se 2FA está ativo e não validou ainda, vai para 2FA e NÃO envia OTP
                        if (response.data.two_factor && !this.validSignIn) {
                            this.validSignIn = true
                            this.goToAuthPage('two-factor-authentication')
                            this.$nextTick(() => this.$refs.twoFactorCodeInput.focus())
                            return;
                        }

                        // Se NÃO tem 2FA, segue fluxo normal (OTP)
                        if (!response.data.two_factor) {
                            this.sendOtpCode();
                        }
                    })
                    .catch((error) => {
                        if (error.response.status == 422) {
                            this.$refs.sign_in.setErrors({
                                'User Password': [this.$t('validation_errors.incorrect_password')],
                            })
                        }

                        // End loading
                        this.isLoading = false
                        return
                    })
            },
            async twoFactorChallenge(recovery) {
                // Check if is normal authentication or recovery
                if (
                    (!recovery && this.twoFactorCode.length === 6) ||
                    (recovery && this.twoFactorRecoveryCode.length === 21)
                ) {
                    this.isLoading = true

                    let payload = recovery ? { recovery_code: this.twoFactorRecoveryCode } : { code: this.twoFactorCode }

                    axios
                        .post('/two-factor-challenge', payload)
                        .then(() => {
                            this.isLoading = false
                            // Após 2FA com sucesso, vai direto para Files SEM enviar OTP
                            this.$store.commit('SET_AUTHORIZED', true)
                            this.$router.push({ name: 'Files' })
                        })
                        .catch((error) => {
                            if (error.response.status == 422) {
                                //Authentication bad input
                                if (!recovery) {
                                    this.$refs.two_factor_authentication.setErrors({
                                        'Two Factor Authentication': this.$t('validation_errors.incorrect_2fa_code'),
                                    })
                                }

                                // Recovery bad input
                                if (recovery) {
                                    this.$refs.two_factor_recovery.setErrors({
                                        'Two Factor Recovery': this.$t('validation_errors.incorrect_2fa_recovery_code'),
                                    })
                                }
                            }

                            // Repeat the login for next try to type right 2fa code / recovery code
                            this.singIn()

                            this.isLoading = false
                        })
                }
            },
            proceedToAccount() {
                if (this.$route.query.redirect) {
                    this.$router.push(this.$route.query.redirect)
                } else {
                    this.$router.push({ name: 'Files' })
                }
            },
        },
        mounted() {
            this.loadTurnstile();

            // Redirect if user is authenticated
            if (this.$store.state.isAuthenticated) {
                this.$router.push({name: 'Files'})
            }
        },
        created() {
            // Show the page when user is not authenticated
            if (! this.$store.state.isAuthenticated) {
                this.isVisible = true
            }

            this.$scrollTop()
            this.$store.commit('PROCESSING_POPUP', undefined)

            if (this.config.isDemo || this.config.isDev) {
                this.loginEmail = 'private@golink.co'
                this.loginPassword = 'bestofgolink'
            }
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