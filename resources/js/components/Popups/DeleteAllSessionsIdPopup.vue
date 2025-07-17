<template>
    <PopupWrapper name="delete-all-sessions-id">
        <PopupHeader :title="$t('delete_all_sessions')" icon="key" />

        <PopupContent>
            <ValidationObserver @submit.prevent="deleteAllSessionsId" ref="passwordForm" tag="form">
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
                @click.native="deleteAllSessionsId"
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
    name: 'DeleteAllSessionsIdPopup',
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
        async deleteAllSessionsId() {
            // Clear previous errors
            this.passwordError = undefined;

            this.isLoading = true;

            axios.post('/api/logoutAllSessions', { password: this.password })
                .then(() => {
                    events.$emit('password:confirmed', this.args);
                    window.location.reload(); // Refresh the page
                })
                .catch((error) => {
                    if (error.response && (error.response.status === 422 || error.response.status === 403)) {
                        this.passwordError = this.$t('validation_errors.incorrect_password');
                        // Set isLoading to false to enable the button again
                        this.isLoading = false;
                    }
                })
                .finally(() => {
                    if (this.isLoading) {
                        // Set isLoading to false to enable the button again
                        this.isLoading = false;
                    }
                    this.password = undefined;
                });
        },
    },
    created() {
        // Show popup
        events.$on('popup:open', (args) => {
            if (args.name !== 'delete-all-sessions-id') return;

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
