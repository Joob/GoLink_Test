<template>
    <div>
        <div class="card shadow-card">
            <div class="mb-6">
                <router-link :to="{ name: 'UserCreate' }">
                    <MobileActionButton icon="user-plus">
                        {{ $t('admin_page_user.create_user.submit') }}
                    </MobileActionButton>
                </router-link>

                <MobileActionButton @click.native="$openSpotlight('users')" icon="search">
                    {{ $t('search') }}
                </MobileActionButton>
            </div>

            <!--Datatable-->
            <DatatableWrapper
                @init="isLoading = false"
                api="/api/admin/users"
                :paginator="true"
                :columns="columns"
                class="overflow-x-auto"
            >
                <template slot-scope="{ row }">
                    <!--Not a subscription-->
                    <tr
                        v-if="config.subscriptionType === 'none'"
                        class="whitespace-nowrap border-b border-dashed border-light dark:border-opacity-5"
                    >
                        <td class="py-3 pr-3 md:pr-1">
                            <router-link
                                :to="{
                                    name: 'UserDetail',
                                    params: { id: row.data.id },
                                }"
                            >
                                <div class="flex items-center">
                                    <MemberAvatar
                                        :is-border="false"
                                        :size="44"
                                        :member="row"
                                    />
                                    <div class="ml-3 pr-10">
                                        <b
                                            class="max-w-1 block overflow-hidden text-ellipsis whitespace-nowrap text-sm font-bold"
                                            style="max-width: 155px"
                                        >
                                            {{ row.data.relationships.settings.data.attributes.name }}
                                        </b>
                                        <span class="block text-xs text-gray-600 dark:text-gray-500">
                                            {{ row.data.attributes.email }}
                                        </span>
                                    </div>
                                </div>
                            </router-link>
                        </td>
                        <td class="px-3 md:px-1">
                            <ColorLabel :color="$getUserRoleColor(row.data.attributes.role)">
                                {{ $t(row.data.attributes.role) }}
                            </ColorLabel>
                        </td>
                        <td class="px-3 md:px-1">
                            <span v-if="row.data.attributes.storage.capacity !== 0" class="text-sm font-bold">
                                {{ row.data.attributes.storage.used_formatted }}
                            </span>
                            <span v-if="row.data.attributes.storage.capacity === 0" class="text-sm font-bold"> - </span>
                        </td>
                        <td class="px-3 md:px-1" v-if="config.storageLimit">
                            <span v-if="row.data.attributes.storage.capacity !== 0" class="text-sm font-bold">
                                {{ row.data.attributes.storage.capacity_formatted }}
                            </span>
                            <span v-if="row.data.attributes.storage.capacity === 0" class="text-sm font-bold"> - </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.created_at }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.updated_at || '-' }}
                            </span>
                        </td>
                        <td class="pl-3 text-right md:pl-1">
                            <div class="flex w-full justify-end space-x-2">
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-green-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDetail',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Edit2Icon size="15" class="opacity-75" />
                                </router-link>
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-red-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDelete',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Trash2Icon size="15" class="opacity-75" />
                                </router-link>
                            </div>
                        </td>
                    </tr>

                    <!--Fixed subscription-->
                    <tr
                        v-if="config.subscriptionType === 'fixed'"
                        class="whitespace-nowrap border-b border-dashed border-light dark:border-opacity-5"
                    >
                        <td class="py-3 pr-3 md:pr-1">
                            <router-link
                                :to="{
                                    name: 'UserDetail',
                                    params: { id: row.data.id },
                                }"
                            >
                                <div class="flex items-center">
                                    <MemberAvatar
                                        :is-border="false"
                                        :size="44"
                                        :member="row"
                                    />
                                    <div class="ml-3 pr-10">
                                        <b
                                            class="max-w-1 block overflow-hidden text-ellipsis whitespace-nowrap text-sm font-bold"
                                            style="max-width: 155px"
                                        >
                                            {{ row.data.relationships.settings.data.attributes.name }}
                                        </b>
                                        <span class="block text-xs text-gray-600 dark:text-gray-500">
                                            {{ row.data.attributes.email }}
                                        </span>
                                    </div>
                                </div>
                            </router-link>
                        </td>
                        <td class="px-3 md:px-1">
                            <ColorLabel :color="$getUserRoleColor(row.data.attributes.role)">
                                {{ $t(row.data.attributes.role) }}
                            </ColorLabel>
                        </td>
                        <td class="px-3 md:px-1">
                            <user-check-icon stroke="green" size="1.5x" class="custom-class colorCheck" v-if="row.data.attributes.email_verified_at"></user-check-icon>
                            <user-x-icon stroke="red" size="1.5x" class="custom-class colorX" v-if="! row.data.attributes.email_verified_at"></user-x-icon>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.relationships.subscription ? $t('premium') : $t('free') }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span v-if="row.data.attributes.storage.capacity !== 0" class="text-sm font-bold">
                                {{ row.data.attributes.storage.used_formatted }}
                            </span>
                            <span v-if="row.data.attributes.storage.capacity === 0" class="text-sm font-bold"> - </span>
                        </td>
                        <td class="px-3 md:px-1" v-if="config.storageLimit">
                            <span v-if="row.data.attributes.storage.capacity !== 0" class="text-sm font-bold">
                                {{ row.data.attributes.storage.capacity_formatted }}
                            </span>
                            <span v-if="row.data.attributes.storage.capacity === 0" class="text-sm font-bold"> - </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.created_at }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.updated_at || '-' }}
                            </span>
                        </td>
                        <td class="pl-3 text-right md:pl-1">
                            <div class="flex w-full justify-end space-x-2">
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-green-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDetail',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Edit2Icon size="15" class="opacity-75" />
                                </router-link>
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-red-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDelete',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Trash2Icon size="15" class="opacity-75" />
                                </router-link>
                            </div>
                        </td>
                    </tr>

                    <!--Metered subscription-->
                    <tr
                        v-if="config.subscriptionType === 'metered'"
                        class="whitespace-nowrap border-b border-dashed border-light dark:border-opacity-5"
                    >
                        <td class="py-3 pr-3 md:pr-1">
                            <router-link
                                :to="{
                                    name: 'UserDetail',
                                    params: { id: row.data.id },
                                }"
                            >
                                <div class="flex items-center">
                                    <MemberAvatar
                                        :is-border="false"
                                        :size="44"
                                        :member="row"
                                    />
                                    <div class="ml-3 pr-10">
                                        <b
                                            class="max-w-1 block overflow-hidden text-ellipsis whitespace-nowrap text-sm font-bold"
                                            style="max-width: 155px"
                                        >
                                            {{ row.data.relationships.settings.data.attributes.name }}
                                        </b>
                                        <span class="block text-xs text-gray-600 dark:text-gray-500">
                                            {{ row.data.attributes.email }}
                                        </span>
                                    </div>
                                </div>
                            </router-link>
                        </td>
                        <td class="px-3 md:px-1">
                            <ColorLabel :color="$getUserRoleColor(row.data.attributes.role)">
                                {{ $t(row.data.attributes.role) }}
                            </ColorLabel>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.meta.usages ? row.data.meta.usages.featureEstimates.storage.usage : '-' }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.meta.usages ? row.data.meta.usages.costEstimate : '-' }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.created_at }}
                            </span>
                        </td>
                        <td class="px-3 md:px-1">
                            <span class="text-sm font-bold">
                                {{ row.data.attributes.updated_at || '-' }}
                            </span>
                        </td>
                        <td class="pl-3 text-right md:pl-1">
                            <div class="flex w-full justify-end space-x-2">
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-green-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDetail',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Edit2Icon size="15" class="opacity-75" />
                                </router-link>
                                <router-link
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-light-background transition-colors hover:bg-red-100 dark:bg-2x-dark-foreground"
                                    :to="{
                                        name: 'UserDelete',
                                        params: { id: row.data.id },
                                    }"
                                >
                                    <Trash2Icon size="15" class="opacity-75" />
                                </router-link>
                            </div>
                        </td>
                    </tr>
                </template>
            </DatatableWrapper>
        </div>
        <div id="loader" v-if="isLoading">
            <Spinner />
        </div>
    </div>
</template>

<script>
import MemberAvatar from '../../components/UI/Others/MemberAvatar'
import DatatableCellImage from '../../components/UI/Table/DatatableCellImage'
import DatatableWrapper from '../../components/UI/Table/DatatableWrapper'
import MobileActionButton from '../../components/UI/Buttons/MobileActionButton'
import MobileHeader from '../../components/Mobile/MobileHeader'
import SectionTitle from '../../components/UI/Labels/SectionTitle'
import ButtonBase from '../../components/UI/Buttons/ButtonBase'
import ColorLabel from '../../components/UI/Labels/ColorLabel'
import Spinner from '../../components/UI/Others/Spinner'
import { Trash2Icon, Edit2Icon, UserCheckIcon, UserXIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'Users',
    components: {
        DatatableCellImage,
        MobileActionButton,
        DatatableWrapper,
        UserCheckIcon,
        UserXIcon,
        MemberAvatar,
        SectionTitle,
        MobileHeader,
        Trash2Icon,
        ButtonBase,
        ColorLabel,
        Edit2Icon,
        Spinner,
    },
    computed: {
        ...mapGetters(['config']),
        columns() {
            return {
                metered: [
                    {
                        label: this.$t('user'),
                        field: 'email',
                        sortable: true,
                    },
                    {
                        label: this.$t('role'),
                        field: 'role',
                        sortable: true,
                    },
                    {
                        label: this.$t('storage_used'),
                        sortable: false,
                    },
                    {
                        label: this.$t('billing_est.'),
                        sortable: false,
                    },
                    {
                        label: this.$t('created_at'),
                        field: 'created_at',
                        sortable: true,
                    },
                    {
                        label: this.$t('last_login'),
                        field: 'updated_at',
                        sortable: true,
                    },
                    {
                        label: this.$t('action'),
                        sortable: false,
                    },
                ],
                fixed: [
                    {
                        label: this.$t('user'),
                        field: 'email',
                        sortable: true,
                    },
                    {
                        label: this.$t('role'),
                        field: 'role',
                        sortable: true,
                    },
                    {
                        label: this.$t('Email Verified'),
                        field: 'settings.email_verified',
                        sortable: true
                    },
                    {
                        label: this.$t('account'),
                        sortable: false,
                    },
                    {
                        label: this.$t('storage_used'),
                        sortable: false,
                    },
                    {
                        label: this.$t('max_storage'),
                        sortable: false,
                        hidden: !this.config.storageLimit,
                    },
                    {
                        label: this.$t('created_at'),
                        field: 'created_at',
                        sortable: true,
                    },
                    {
                        label: this.$t('last_login'),
                        field: 'updated_at',
                        sortable: false,
                    },
                    {
                        label: this.$t('action'),
                        sortable: false,
                    },
                ],
                none: [
                    {
                        label: this.$t('user'),
                        field: 'email',
                        sortable: true,
                    },
                    {
                        label: this.$t('role'),
                        field: 'role',
                        sortable: true,
                    },
                    {
                        label: this.$t('storage_used'),
                        sortable: false,
                    },
                    {
                        label: this.$t('max_storage'),
                        sortable: false,
                        hidden: !this.config.storageLimit,
                    },
                    {
                        label: this.$t('created_at'),
                        field: 'created_at',
                        sortable: true,
                    },
                    {
                        label: this.$t('last_login'),
                        field: 'updated_at',
                        sortable: false,
                    },
                    {
                        label: this.$t('action'),
                        sortable: false,
                    },
                ],
            }[this.config.subscriptionType]
        },
    },
    data() {
        return {
            isLoading: true,
        }
    },
}
</script>