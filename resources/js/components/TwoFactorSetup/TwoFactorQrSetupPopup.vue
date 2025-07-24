<template>
    <PopupWrapper name="two-factor-qr-setup">
        <PopupHeader :title="$t('set_up_2fa_app')" icon="edit" />

        <PopupContent>
            <!-- Loading State -->
            <div v-if="isLoadingQr" class="flex justify-center my-8">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-400 mx-auto"></div>
                    <p class="text-gray-500 mt-3">Loading QR Code...</p>
                </div>
            </div>

            <!-- QR Code Display -->
            <div v-else-if="qrCode" class="qr-code-wrapper">
                <div class="qr-code-container" v-html="qrCode"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="qrError" class="text-center my-8">
                <p class="text-red-500 mb-3">Failed to load QR Code</p>
                <ButtonBase @click.native="retrySetup" button-style="secondary" size="sm">
                    Try Again
                </ButtonBase>
            </div>

            <InfoBox class="mb-0">
                <p v-html="$t('popup_2fa.help')"></p>
            </InfoBox>

            <ValidationObserver @submit.prevent="confirm2FaSetup" ref="codeForm" v-slot="{ invalid }" tag="form" class="mt-5">
                <ValidationProvider tag="div" mode="passive" name="Code" rules="required" v-slot="{ errors }">
                    <AppInputText :title="$t('confirm')" :error="errors[0]" :is-last="true">
                        <input
                            v-model="code"
                            :class="{ '!border-rose-600': errors[0] }"
                            type="text"
                            ref="input"
                            class="focus-border-theme input-dark"
                            :placeholder="$t('paste_code_from_2fa_app')"
                        />
                    </AppInputText>
                </ValidationProvider>
            </ValidationObserver>
        </PopupContent>

        <PopupActions>
            <ButtonBase @click.native="confirm2FaSetup" class="w-full" button-style="theme" :loading="isLoading">
                {{ $t('confirm_your_code') }}
            </ButtonBase>
        </PopupActions>
    </PopupWrapper>
</template>

<script>
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import { required } from 'vee-validate/dist/rules'
import ButtonBase from '../UI/Buttons/ButtonBase'
import AppInputText from '../Forms/Layouts/AppInputText'
import PopupWrapper from '../Popups/Components/PopupWrapper'
import PopupActions from '../Popups/Components/PopupActions'
import PopupContent from '../Popups/Components/PopupContent'
import PopupHeader from '../Popups/Components/PopupHeader'
import InfoBox from '../UI/Others/InfoBox'
import { events } from '../../bus'
import axios from 'axios'

export default {
    name: 'TwoFactorQrSetupPopup',
    components: {
        ValidationProvider,
        ValidationObserver,
        AppInputText,
        PopupWrapper,
        PopupActions,
        PopupContent,
        PopupHeader,
        ButtonBase,
        required,
        InfoBox,
    },
    data() {
        return {
            qrCode: null,
            isLoading: false,
            isLoadingQr: false,
            qrError: false,
            code: ''
        }
    },
    methods: {
        async confirm2FaSetup() {
            // Validate fields
            const isValid = await this.$refs.codeForm.validate()

            if (!isValid) return

            this.isLoading = true

            try {
                await axios.post('/user/confirmed-two-factor-authentication', { code: this.code })
                
                this.$store.commit('CHANGE_TWO_FACTOR_AUTHENTICATION_STATE', true)
                this.$closePopup()
                
                events.$emit('toaster', {
                    type: 'success',
                    message: this.$t('popup_2fa.toaster_enabled'),
                })
            } catch (error) {
                if (error.response?.status === 422) {
                    this.$refs.codeForm.setErrors({
                        'Code': error.response.data.errors['code'][0],
                    })
                }
            } finally {
                this.isLoading = false
            }
        },
        
        async setupTwoFactor() {
            this.resetState()
            this.isLoadingQr = true
            
            try {
                // Enable 2FA
                await axios.post('/user/two-factor-authentication')
                
                // Get QR Code
                const response = await axios.get('/user/two-factor-qr-code')
                
                if (response.data && response.data.svg) {
                    this.qrCode = response.data.svg
                } else {
                    throw new Error('Invalid QR code response')
                }
            } catch (error) {
                console.error('2FA Setup Error:', error)
                this.qrError = true
                this.$isSomethingWrong()
            } finally {
                this.isLoadingQr = false
            }
        },
        
        retrySetup() {
            this.setupTwoFactor()
        },
        
        resetState() {
            this.qrCode = null
            this.qrError = false
            this.code = ''
            if (this.$refs.codeForm) {
                this.$refs.codeForm.reset()
            }
        }
    },
    
    created() {
        events.$on('popup:open', (args) => {
            if (args.name !== 'two-factor-qr-setup') return
            this.setupTwoFactor()
        })
    },
    
    beforeDestroy() {
        events.$off('popup:open')
    }
}
</script>

<style scoped>
.qr-code-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
}

.qr-code-container {
    width: 200px;
    height: 200px;
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Make SVG responsive */
.qr-code-container :deep(svg) {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
}

/* Mobile responsive */
@media (max-width: 480px) {
    .qr-code-container {
        width: 160px;
        height: 160px;
        padding: 0.75rem;
    }
}
</style>