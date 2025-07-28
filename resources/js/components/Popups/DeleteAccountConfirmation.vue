<template>
    <PopupWrapper name="delete-account-confirmation">
        <PopupHeader :title="$t('delete_permanently_account')" icon="user" />

        <PopupContent>
            <ValidationObserver 
                @submit.prevent="deleteAccount" 
                ref="deleteForm" 
                v-slot="{ invalid }" 
                tag="form"
            >
                <div class="mb-6">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Para apagar a conta permanentemente + dados + ficheiros escreva "<strong>{{ username }}</strong>"
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
                        :title="'Confirme o nome de utilizador'" 
                        :error="errors[0]"
                    >
                        <input
                            v-model="usernameConfirmation"
                            :placeholder="`Digite ${username}`"
                            type="text"
                            class="focus-border-theme input-dark"
                            :class="{ '!border-rose-600': errors[0] }"
                            autocomplete="off"
                        />
                    </AppInputText>
                </ValidationProvider>

                <PopupActions>
                    <ButtonBase 
                        class="w-full" 
                        @click.native="$closePopup()" 
                        button-style="secondary"
                    >
                        Cancelar
                    </ButtonBase>
                    
                    <ButtonBase
                        class="w-full danger"
                        button-style="theme"
                        @click.native="deleteAccount"
                        type="submit"
                        :loading="isLoading"
                        :disabled="invalid || usernameConfirmation !== username"
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
import { events } from '../../bus'
import axios from 'axios'

// Custom validation rule
extend('username_match', {
    params: ['target'],
    validate(value, { target }) {
        return value === target
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
            return this.user?.data?.attributes?.name || 
                   this.user?.data?.attributes?.username || 
                   this.user?.data?.attributes?.email?.split('@')[0] || 
                   'username'
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
            this.isLoading = true
            try {
                await this.$store.dispatch('deleteUserAccount', {
                    usernameConfirmation: this.usernameConfirmation
                })
                this.isLoading = false
                this.$closePopup()
                // Podes mostrar um toast/sucesso aqui
            } catch (error) {
                this.isLoading = false
                // Mostra erro ao utilizador
                // Por exemplo: this.$toast.error(error.response?.data?.message || 'Erro ao apagar conta')
            }
        }
    },
    mounted() {
        this.usernameConfirmation = ''
    }
}
</script>