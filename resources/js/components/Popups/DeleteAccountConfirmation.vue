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
                        Para apagar a conta permanentemente, escreva <strong>{{ username }}</strong>
                    </p>
                </div>

                <ValidationProvider
                    tag="div"
                    mode="passive"
                    name="Username Confirmation"
                    :rules="`required|username_match:${username}`"
                    v-slot="{ errors }"
                >
                    <AppInputText
                        :title="'Confirme o nome de utilizador apagar permanentemente'"
                        :error="errors[0]"
                    >
                        <input
                            v-model="usernameConfirmation"
                            :placeholder="`Digite ${username}`"
                            type="text"
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

extend('username_match', {
    params: ['target'],
    validate(value, { target }) {
        return value.trim().toLowerCase() === target.trim().toLowerCase()
    },
    message: 'O nome de utilizador não coincide'
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
        username() {
            const user = this.user?.data?.attributes;
            const settings = this.user?.data?.relationships?.settings?.data?.attributes;
            // Use the name from settings (first_name + last_name) or fallback to email
            const name = settings?.name || user?.email?.split('@')[0] || user?.email || 'username';
            return name;
        },
        isSubmitButtonDisabled() {
            // Só habilita se o valor for exatamente igual ao username (case-insensitive, sem espaços extras)
            return (
                this.isLoading ||
                !this.usernameConfirmation.trim() ||
                this.usernameConfirmation.trim().toLowerCase() !== this.username.trim().toLowerCase()
            );
        }
    },
    data() {
        return {
            usernameConfirmation: '',
            isLoading: false
        }
    },
    methods: {
        async deleteAccount() {
            this.isLoading = true;
            try {
                await this.$store.dispatch('deleteUserAccount', {
                    username_confirmation: this.usernameConfirmation,
                });
                this.isLoading = false;
                this.usernameConfirmation = '';
                this.$closePopup();
                this.$toast?.success?.('Conta apagada com sucesso.');
            } catch (error) {
                this.isLoading = false;
                this.$toast?.error?.(error.response?.data?.message || 'Erro ao apagar conta');
            }
        },
        onCancel() {
            this.usernameConfirmation = '';
            this.$closePopup();
        },
        onInput() {
            // Força reatividade para debug (podes remover depois)
            console.log('[DeleteAccount] Valor digitado:', this.usernameConfirmation, '| Botão disabled:', this.isSubmitButtonDisabled);
        }
    },
    mounted() {
        this.usernameConfirmation = '';
    }
}
</script>