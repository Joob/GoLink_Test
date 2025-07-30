<template>
    <div class="h-screen lg:overflow-hidden lg:flex w-full">
        <!--On Top of App Components-->
        <FilePreview />
        <Spotlight />

        <!--Spotlight Addons-->
        <CreateUploadRequestPopup />
        <CreateTeamFolderPopup />
        <NotificationsPopup />
        <RemoteUploadPopup />

        <ConfirmPopup v-if="!isLoading && user" />
        <ConfirmPassword v-if="!isLoading && user" />

        <!--2FA popups-->
        <TwoFactorRecoveryCodesPopup v-if="!isLoading && user" />
        <TwoFactorQrSetupPopup v-if="!isLoading && user" />

        <!--Access Token Popup-->
        <CreatePersonalTokenPopup v-if="!isLoading && user" />

        <!--Reset CSRF ID-->
        <ResetCSRFIdPopup v-if="!isLoading && user" />

        <!--Delete Permanently Account-->
        <DeleteAccountConfirmation v-if="!isLoading && user" />

        <!--Payments Popup-->
        <SubscribeAccountPopup v-if="!isLoading && user && config.subscriptionType === 'fixed'" />
        <ChangeSubscriptionPopup v-if="!isLoading && user && config.subscriptionType === 'fixed'" />
        <ChargePaymentPopup v-if="!isLoading && user && config.subscriptionType === 'metered'" />

        <!--Navigations-->
        <MobileNavigation />

        <MobileNavigationToolbar />

        <div
            v-if="user"
            class="relative mx-auto w-full overflow-x-hidden px-2.5 pb-12 md:max-w-4xl md:px-6 lg:pt-6 lg:pb-0 xl:max-w-screen-lg z-[5]"
        >
            <div v-if="!isLoading" id="page-content">
                <div class="card sticky top-0 z-10 shadow-card" style="padding-bottom: 0">
                    <!--User thumbnail-->
                    <div class="mb-3 flex items-center">
                        <!--Image input for replace avatar-->
                        <AvatarInput
                            v-model="avatar"
                            :avatar="user.data.relationships.settings.data.attributes.avatar ? user.data.relationships.settings.data.attributes.avatar.md : undefined"
                        />

                        <!--User name & email-->
                        <div class="ml-4">
                            <b class="text-md block font-bold sm:text-lg">
                                {{ user.data.relationships.settings.data.attributes.first_name }}
                                {{ user.data.relationships.settings.data.attributes.last_name }}

                                <ColorLabel v-if="config.subscriptionType === 'fixed'" :color="subscriptionColor">
                                    {{ subscriptionStatus }}
                                </ColorLabel>
                            </b>
                            <small class="block text-xs dark:text-gray-500 text-gray-600 sm:text-sm">
                                {{ user.data.attributes.email }}
                            </small>
                        </div>
                    </div>

                    <CardNavigation :pages="pages" class="-mx-1" />
                </div>

                <!--Router Content-->
                <router-view :user="user" />
            </div>
            <Spinner v-if="isLoading" />
        </div>
    </div>
</template>

<script>
import MobileNavigation from '../components/Mobile/MobileNavigation'
import ChargePaymentPopup from '../components/Subscription/Popups/ChargePaymentPopup'
import SubscribeAccountPopup from '../components/Subscription/Popups/SubscribeAccountPopup'
import ConfirmPopup from '../components/Popups/ConfirmPopup'
import FilePreview from '../components/FilePreview/FilePreview'
import Spotlight from '../components/Spotlight/Spotlight'
import TwoFactorRecoveryCodesPopup from '../components/TwoFactorSetup/TwoFactorRecoveryCodesPopup'
import CreatePersonalTokenPopup from '../components/Popups/CreatePersonalTokenPopup'
import ResetCSRFIdPopup from '../components/Popups/ResetCSRFIdPopup'
import DeleteAccountConfirmation from '../components/Popups/DeleteAccountConfirmation'
import TwoFactorQrSetupPopup from '../components/TwoFactorSetup/TwoFactorQrSetupPopup'
import AvatarInput from '../components/Inputs/AvatarInput'
import ColorLabel from '../components/UI/Labels/ColorLabel'
import Spinner from '../components/UI/Others/Spinner'
import { mapGetters } from 'vuex'
import CardNavigation from '../components/UI/Others/CardNavigation'
import ConfirmPassword from '../components/TwoFactorSetup/ConfirmPassword'
import MobileNavigationToolbar from '../components/Mobile/MobileNavigationToolbar'
import CreateUploadRequestPopup from "../components/UploadRequest/CreateUploadRequestPopup";
import CreateTeamFolderPopup from "../components/Teams/CreateTeamFolderPopup";
import ChangeSubscriptionPopup from "../components/Subscription/Popups/ChangeSubscriptionPopup";
import NotificationsPopup from "../components/Notifications/NotificationsPopup";
import RemoteUploadPopup from "../components/RemoteUpload/RemoteUploadPopup";

export default {
    name: 'Settings',
    components: {
        RemoteUploadPopup,
        NotificationsPopup,
        ChangeSubscriptionPopup,
        CreateTeamFolderPopup,
        CreateUploadRequestPopup,
        MobileNavigationToolbar,
        MobileNavigation,
        ConfirmPassword,
        ChargePaymentPopup,
        SubscribeAccountPopup,
        ConfirmPopup,
        CardNavigation,
        FilePreview,
        Spotlight,
        TwoFactorRecoveryCodesPopup,
        CreatePersonalTokenPopup,
        ResetCSRFIdPopup,
        DeleteAccountConfirmation,
        TwoFactorQrSetupPopup,
        AvatarInput,
        ColorLabel,
        Spinner
    },
    computed: {
        ...mapGetters(['config', 'user']),
        subscriptionStatus() {
            return this.user.data.relationships.subscription ? this.$t('premium') : this.$t('free')
        },
        subscriptionColor() {
            return this.user.data.relationships.subscription ? 'green' : 'purple'
        },
        pages() {
            let list = [
                {
                    title: this.$t('menu.profile'),
                    route: 'Profile',
                },
                {
                    title: this.$t('menu.password'),
                    route: 'Password',
                },
                {
                    title: this.$t('storage'),
                    route: 'Storage',
                },
            ]

            // Push billing item if subscription is set
            if (this.config.subscriptionType !== 'none') {
                list.push({
                    title: this.$t('billing'),
                    route: 'Billing',
                })
            }

            // Add danger zone
            list.push({
                title: 'Zona Perigosa',
                route: 'DangerZone',
                isDanger: true
            })

            return list
        },
    },
    data() {
        return {
            avatar: undefined,
            isLoading: true, // Inicia como true
        }
    },
    async mounted() {
        // Aguarda a inicialização completa antes de renderizar os popups
        await this.$nextTick()
        
        // Pequeno delay para garantir que tudo está carregado
        setTimeout(() => {
            this.isLoading = false
        }, 100)
    },
    watch: {
        // Observa mudanças no user para garantir que os popups sejam renderizados
        user: {
            handler(newUser) {
                if (newUser && this.isLoading) {
                    this.$nextTick(() => {
                        this.isLoading = false
                    })
                }
            },
            immediate: true
        }
    }
}
</script>