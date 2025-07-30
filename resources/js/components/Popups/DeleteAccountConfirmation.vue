<template>
    <PopupWrapper name="delete-account-confirmation">
        <PopupHeader
            :title="$t('delete_permanently_account')"
            icon="user-plus"
        />

        <PopupContent>
            <ValidationObserver
                v-slot="{ invalid }"
                ref="deleteForm"
                @submit.prevent="deleteAccount"
                tag="form"
            >
                <div class="mb-6">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Para apagar a conta permanentemente, escreva <strong>{{ userEmail }}</strong>
                    </p>
                </div>

                <ValidationProvider
                    tag="div"
                    mode="passive"
                    name="Email Confirmation"
                    :rules="`required|email_match:${userEmail}`"
                    v-slot="{ errors }"
                >
                    <AppInputText
                        :title="'Confirme o email para apagar permanentemente'"
                        :error="errors[0]"
                    >
                        <input
                            v-model="emailConfirmation"
                            :placeholder="`Digite ${userEmail}`"
                            type="email"
                            class="focus-border-theme input-dark"
                            :class="{ '!border-rose-600': errors[0] }"
                            autocomplete="off"
                            @input="onInput"
                        />
                    </AppInputText>
                </ValidationProvider>

                <PopupActions>
                    <ButtonBase
                        class="w-full"
                        type="button"
                        button-style="secondary"
                        @click.native="onCancel"
                    >
                        Cancelar
                    </ButtonBase>
                    <!-- Garantir :disabled e também um fallback de style -->
                    <ButtonBase
                        class="w-full danger"
                        button-style="theme"
                        type="submit"
                        :loading="isLoading"
                        :disabled="isSubmitButtonDisabled"
                        :style="{ opacity: isSubmitButtonDisabled ? 0.6 : 1, pointerEvents: isSubmitButtonDisabled ? 'none' : 'auto' }"
                    >
                        Apagar Permanentemente
                    </ButtonBase>
                </PopupActions>
            </ValidationObserver>
        </PopupContent>
    </PopupWrapper>
</template>

<script>
import { ValidationProvider, ValidationObserver, extend } from 'vee-validate/dist/vee-validate.full'
import PopupActions from './Components/PopupActions'
import PopupWrapper from './Components/PopupWrapper'
import PopupHeader from './Components/PopupHeader'
import PopupContent from './Components/PopupContent'
import AppInputText from '../Forms/Layouts/AppInputText'
import ButtonBase from '../UI/Buttons/ButtonBase'
import { mapGetters } from 'vuex'

extend('email_match', {
    params: ['target'],
    validate(value, { target }) {
        return value.trim().toLowerCase() === target.trim().toLowerCase()
    },
    message: 'O email não coincide'
})

export default {
    name: 'DeleteAccountConfirmation',
    components: {
        ValidationProvider,
        ValidationObserver,
        PopupActions,
        PopupWrapper,
        PopupHeader,
        PopupContent,
        AppInputText,
        ButtonBase,
    },
    computed: {
        ...mapGetters(['user']),
        userEmail() {
            const user = this.user?.data?.attributes;
            return user?.email || 'email@example.com';
        },
        isSubmitButtonDisabled() {
            // Só habilita se o valor for exatamente igual ao email (case-insensitive, sem espaços extras)
            return (
                this.isLoading ||
                !this.emailConfirmation.trim() ||
                this.emailConfirmation.trim().toLowerCase() !== this.userEmail.trim().toLowerCase()
            );
        }
    },
    data() {
        return {
            emailConfirmation: '',
            isLoading: false
        }
    },
    methods: {
        async deleteAccount() {
            this.isLoading = true;
            try {
                await this.$store.dispatch('deleteUserAccount', {
                    email_confirmation: this.emailConfirmation,
                });
                this.isLoading = false;
                this.emailConfirmation = '';
                this.$closePopup();
                this.$toast?.success?.('Conta apagada com sucesso.');
            } catch (error) {
                this.isLoading = false;
                this.$toast?.error?.(error.response?.data?.message || 'Erro ao apagar conta');
            }
        },
        onCancel() {
            this.emailConfirmation = '';
            this.$closePopup();
        },
        onInput() {
            // Força reatividade para debug (podes remover depois)
            console.log('[DeleteAccount] Valor digitado:', this.emailConfirmation, '| Botão disabled:', this.isSubmitButtonDisabled);
        }
    },
    mounted() {
        this.emailConfirmation = '';
    }
}
</script>