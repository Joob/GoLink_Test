<template>
    <transition name="notification-slide">
        <article
            v-if="!isDeleting"
            class="relative z-[11] mb-1.5 flex items-start space-x-4 rounded-xl p-2.5 transition-all duration-300"
            :class="{
                'dark:bg-4x-dark-foreground bg-light-background/80': isUnread
            }"
        >
            <gift-icon
                v-if="notification.data.attributes.category === 'gift'"
                size="22"
                class="vue-feather text-theme shrink-0"
            />
            <user-plus-icon
                v-if="notification.data.attributes.category === 'team-invitation'"
                size="22"
                class="vue-feather text-theme shrink-0"
            />
            <mail-icon
                v-if="notification.data.attributes.category === 'send-new-notifications'"
                size="22"
                class="vue-feather text-theme shrink-0"
            />
            <trending-up-icon
                v-if="notification.data.attributes.category === 'subscription-created'"
                size="22"
                class="vue-feather text-theme shrink-0"
            />
            <alert-triangle-icon
                v-if="['billing-alert', 'insufficient-balance', 'payment-alert'].includes(notification.data.attributes.category)"
                size="22"
                class="vue-feather text-theme shrink-0"
            />
            <upload-cloud-icon
                v-if="['file-request', 'remote-upload-done'].includes(notification.data.attributes.category)"
                size="22"
                class="vue-feather text-theme shrink-0"
            />

            <div class="flex-1 pr-10">
                <b class="mb-1.5 block font-extrabold">
                    {{ notification.data.attributes.title }}
                </b>

                <p class="mb-1.5 text-sm dark:text-gray-500">
                    {{ notification.data.attributes.description }}
                </p>

                <div class="flex items-center">
                    <div v-if="user" class="flex items-center">
                        <user-icon size="22" class="mr-1" />
                        <MemberAvatar class="mr-2" :size="22" :is-border="false" :member="user" />
                    </div>
                    <time class="block text-xs text-gray-400 dark:text-gray-400">
                        {{ notification.data.attributes.created_at }}
                    </time>
                </div>

                <!--Accept or decline team invitation-->
                <div v-if="action && action.type === 'invitation'" class="flex items-center space-x-3 mt-4">
                    <div
                        @click="acceptAction"
                        class="relative flex cursor-pointer items-center rounded-xl py-1.5 px-2 transition-colors bg-green-100 dark:bg-green-900"
                    >
                        <refresh-cw-icon v-if="isAccepting" size="16" class="animate-spin left-0 right-0 mx-auto vue-feather text-green-600 dark:text-green-600 absolute justify-center" />
                        <check-icon size="16" class="vue-feather mr-1 text-green-600 dark:text-green-600" :class="{'opacity-0': isAccepting}" />
                        <span class="text-sm font-bold text-green-600 dark:text-green-600" :class="{'opacity-0': isAccepting}">
                            {{ $t('accept') }}
                        </span>
                    </div>

                    <div
                        @click="declineAction"
                        class="relative flex cursor-pointer items-center rounded-xl py-1.5 px-2 transition-colors bg-rose-100 dark:bg-rose-900"
                    >
                        <refresh-cw-icon v-if="isDeclining" size="16" class="animate-spin left-0 right-0 mx-auto vue-feather text-rose-600 dark:text-rose-600 absolute justify-center" />
                        <x-icon size="16" class="vue-feather mr-1 text-rose-600 dark:text-rose-600" :class="{'opacity-0': isDeclining}" />
                        <span class="text-sm font-bold text-rose-600 dark:text-rose-600 capitalize" :class="{'opacity-0': isDeclining}">
                            {{ $t('decline') }}
                        </span>
                    </div>
                </div>

                <!--Go to route-->
                <router-link
                    @click="closeCenter"
                    v-if="action && action.type === 'route'"
                    :to="{ name: action.params.route, params: { id: action.params.id } }"
                    class="mt-4 inline-flex items-center"
                >
                    <span class="mr-2 whitespace-nowrap text-xs font-bold">
                        {{ action.params.button }}
                    </span>
                    <chevron-right-icon size="16" class="text-theme vue-feather" />
                </router-link>

                <!--Open Link-->
                <a
                    @click="closeCenter"
                    v-if="action && action.type === 'url'"
                    :target="action.params.target === 'blank' ? '_blank' : '_self'"
                    :href="action.params.url"
                    class="mt-4 inline-flex items-center"
                >
                    <span class="mr-2 whitespace-nowrap text-xs font-bold">
                        {{ action.params.button }}
                    </span>
                    <chevron-right-icon size="16" class="text-theme vue-feather" />
                </a>
            </div>
            
            <div class="absolute top-2 right-2">
                <button 
                    @click="deleteNotification" 
                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="isDeletingRequest"
                >
                    <x-icon size="16" />
                </button>
            </div>
        </article>
    </transition>
</template>

<script>
import { RefreshCwIcon, TrendingUpIcon, GiftIcon, CheckIcon, XIcon, MailIcon, UserPlusIcon, UserIcon, UploadCloudIcon, ChevronRightIcon, AlertTriangleIcon } from 'vue-feather-icons'
import MemberAvatar from '../../UI/Others/MemberAvatar'
import { events } from "../../../bus";
import axios from 'axios';

export default {
    name: 'Notification',
    props: ['notification'],
    components: {
        MemberAvatar,
        AlertTriangleIcon,
        ChevronRightIcon,
        UploadCloudIcon,
        TrendingUpIcon,
        RefreshCwIcon,
        UserPlusIcon,
        UserIcon,
        CheckIcon,
        GiftIcon,
        MailIcon,
        XIcon,
    },
    computed: {
        action() {
            return this.notification.data.attributes.action
        },
        user() {
            return this.notification.data.attributes.user || null
        }
    },
    data() {
        return {
            isUnread: false,
            isAccepting: false,
            isDeclining: false,
            isDeleting: false,
            isDeletingRequest: false,
        }
    },
    methods: {
        acceptAction() {
            this.isAccepting = true

            axios.put(`/api/teams/invitations/${this.notification.data.attributes.action.params.id}`)
                .then(() => {
                    this.$store.commit('CLEAR_NOTIFICATION_ACTION_DATA', this.notification.data.id)

                    events.$emit('toaster', {
                        type: 'success',
                        message: this.$t('you_accepted_invitation'),
                    })
                })
                .finally(() => this.isAccepting = false)
        },
        declineAction() {
            this.isDeclining = true

            axios.delete(`/api/teams/invitations/${this.notification.data.attributes.action.params.id}`)
                .then(() => {
                    this.$store.commit('CLEAR_NOTIFICATION_ACTION_DATA', this.notification.data.id)

                    events.$emit('toaster', {
                        type: 'success',
                        message: this.$t('you_decline_invitation'),
                    })
                })
                .finally(() => this.isDeclining = false)
        },
        deleteNotification() {
            this.isDeletingRequest = true;
            this.isDeleting = true;
            
            // Faz a requisição imediatamente
            axios.post(`/api/notifications/${this.notification.data.id}/delete`)
                .then(() => {
                    // Remove do store após sucesso
                    this.$store.commit('REMOVE_SINGLE_NOTIFICATION', this.notification.data.id);

                    events.$emit('toaster', {
                        type: 'success',
                        message: this.$t('notification_deleted'),
                    });
                })
                .catch((error) => {
                    // Reverte a animação em caso de erro
                    this.isDeleting = false;
                    
                    console.error('Delete error:', error.response);
                    
                    events.$emit('toaster', {
                        type: 'error',
                        message: error.response?.data?.message || this.$t('delete_notification_error'),
                    });
                })
                .finally(() => {
                    this.isDeletingRequest = false;
                });
        },
        closeCenter() {
            this.$store.commit('CLOSE_NOTIFICATION_CENTER')
            this.$closePopup()
        },
    },
    created() {
        this.isUnread = this.notification.data.attributes.read_at === null;
        setTimeout(() => this.isUnread = false, 1000);
    },
}
</script>

<style scoped>
.notification-slide-leave-active {
    transition: all 0.3s ease;
}

.notification-slide-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>