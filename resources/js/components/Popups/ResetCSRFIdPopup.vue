<template>
    <PopupWrapper name="reset-csrf">
        <PopupHeader :title="$t('delete_all_sessions')" icon="key" />

        <PopupContent>
            <ValidationObserver @submit.prevent="ResetCSRFID" ref="passwordForm" tag="form">
                <ValidationProvider tag="div" mode="passive" rules="required" v-slot="{ errors }">
                    <AppInputText :title="$t('enter_password_delete_all_sessions')" :error="passwordError || errors[0]">
                        <input
                            v-model="password"
                            :class="{ '!border-rose-600': passwordError || errors[0] }"
                            type="password"
                            ref="input"
                            class="focus-border-theme input-dark"
                            :placeholder="$t('page_sign_in.placeholder_password')"
                        />
                    </AppInputText>
                </ValidationProvider>
            </ValidationObserver>
        </PopupContent>

        <PopupActions>
            <ButtonBase class="w-full" @click.native="$closePopup()" button-style="secondary">
                {{ $t('cancel') }}
            </ButtonBase>
            <ButtonBase
                class="w-full danger"
                @click.native="ResetCSRFID"
                button-style="theme"
                :loading="isLoading"
                :disabled="isLoading"
            >
                {{ $t('delete_all_sessions') }}
            </ButtonBase>
        </PopupActions>
    </PopupWrapper>
</template>

<script>
import AppInputText from '../Forms/Layouts/AppInputText'
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import PopupWrapper from './Components/PopupWrapper'
import PopupActions from './Components/PopupActions'
import PopupContent from './Components/PopupContent'
import PopupHeader from './Components/PopupHeader'
import ButtonBase from '../UI/Buttons/ButtonBase'
import InfoBox from '../UI/Others/InfoBox'
import { required } from 'vee-validate/dist/rules'
import { events } from '../../bus'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'ResetCSRFIdPopup',
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
    computed: {
        ...mapGetters(['user']),
    },
    data() {
        return {
            isLoading: false,
            password: undefined,
            args: undefined,
            passwordError: undefined,
        }
    },
    methods: {
        async ResetCSRFID() {
            // Validar o formulário primeiro
            const isValid = await this.$refs.passwordForm.validate();
            if (!isValid) {
                this.isLoading = false;
                return;
            }

            this.passwordError = undefined;
            this.isLoading = true;

            // Armazenar a senha temporariamente
            const currentPassword = this.password;

            axios.post('/api/ResetCSRF', { password: this.password })
                .then((response) => {                    
                    // Limpar a senha imediatamente
                    this.password = '';
                    
                    // Emitir eventos
                    events.$emit('popup:close', 'reset-csrf');
                    
                    events.$emit('toaster', {
                        type: 'success',
                        message: this.$t('csrf_reset_success'),
                    });
                    
                    events.$emit('password:confirmed', this.args);

                    // Resetar estado de carregamento
                    this.isLoading = false;
                })
                .catch((error) => {                    
                    // Tratamento de erro mais robusto
                    if (error.response) {
                        switch (error.response.status) {
                            case 422:
                            case 403:
                                this.passwordError = this.$t('validation_errors.incorrect_password');
                                events.$emit('toaster', {
                                    type: 'error',
                                    message: this.$t('csrf_reset_failed'),
                                });
                                break;
                            default:
                                events.$emit('toaster', {
                                    type: 'error',
                                    message: this.$t('general_error'),
                                });
                        }
                    } else {
                        events.$emit('toaster', {
                            type: 'error',
                            message: this.$t('network_error'),
                        });
                    }
                    
                    // Sempre resetar o estado de carregamento em caso de erro
                    this.isLoading = false;
                    
                    // Limpar a senha em caso de erro
                    this.password = '';
                });
        },
    },

    created() {
        // Show popup
        events.$on('popup:open', (args) => {
            if (args.name !== 'reset-csrf') return;

            this.password = '';
            this.passwordError = undefined;
            this.args = args;
        });
    },
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/inapp-forms';
@import '../../../sass/vuefilemanager/forms';

.dark {
    .info-box {
        background: lighten($dark_mode_foreground, 3%);
    }
}
</style>
