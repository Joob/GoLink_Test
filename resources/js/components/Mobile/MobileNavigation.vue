<template>
    <MenuMobile name="user-navigation">
        <!--User avatar-->
        <UserHeadline v-if="!clickedSubmenu" class="p-5 pb-3" />

        <!--User estimate-->
        <div
            v-if="config.subscriptionType === 'metered' && user && user.data && user.data.meta.usages && !clickedSubmenu"
            class="block px-5 pt-2"
        >
            <div class="rounded-lg bg-light-background px-3 py-1.5 dark:bg-4x-dark-foreground">
                <span class="text-sm font-semibold">
                    {{ $t('current_estimated_usage') }}
                </span>
                <span class="text-theme text-sm font-bold">
                    {{ user.data.meta.usages.costEstimate }}
                </span>
            </div>
        </div>

        <!--Go back button-->
        <div v-if="clickedSubmenu" @click.stop="showSubmenu(undefined)" class="flex items-center p-5 pb-4">
            <chevron-left-icon size="19" class="vue-feather text-theme mr-2 -ml-1" />
            <span class="text-theme text-sm font-bold">
                {{ backTitle }}
            </span>
        </div>

        <!--Menu links-->
        <MenuMobileGroup>
            <!--User Storage-->
            <div v-if="shouldShowStorageWarning" class="block px-5 pt-2">
                <div class="rounded-lg bg-light-background px-3 py-1.5 dark:bg-4x-dark-foreground">
                    <span class="text-sm font-semibold" style="color: orange">
                        WARNING 10%
                    </span>
                    <br>
                    -----
                    <br>
                    <span class="text-sm font-semibold" style="color: red">
                        {{ storage.data.attributes.used }}
                    </span>
                    <br>
                    <span class="text-sm font-bold">
                        {{ $t('total_of', {capacity: storage.data.attributes.capacity}) }}
                    </span>
                </div>
            </div>
            <!--Main navigation-->
            <OptionGroup v-if="!clickedSubmenu">
                <Option
                    @click.native="goToFiles"
                    :title="$t('menu.files')"
                    icon="hard-drive"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native.stop="showSubmenu('settings')"
                    :title="$t('settings')"
                    icon="user"
                    arrow="right"
                    :is-hover-disabled="true"
                />
                <Option
                    v-if="isAdmin"
                    @click.native.stop="showSubmenu('admin')"
                    :title="$t('administration')"
                    icon="settings"
                    arrow="right"
                    :is-hover-disabled="true"
                />
            </OptionGroup>
            <OptionGroup v-if="!clickedSubmenu">
                <Option @click.native="logOut" :title="$t('logout')" icon="power" :is-hover-disabled="true" />
            </OptionGroup>

            <!--Submenu: User settings-->
            <OptionGroup v-if="clickedSubmenu === 'settings'">
                <Option
                    @click.native="goToRoute('Profile')"
                    :title="$t('menu.profile')"
                    icon="user"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Password')"
                    :title="$t('menu.password')"
                    icon="lock"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Storage')"
                    :title="$t('storage')"
                    icon="hard-drive"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Billing')"
                    v-if="config.subscriptionType !== 'none'"
                    :title="$t('billing')"
                    icon="cloud"
                    :is-hover-disabled="true"
                />
            </OptionGroup>

            <!--Submenu: Admin settings-->
            <OptionGroup v-if="clickedSubmenu === 'admin'">
                <Option
                    @click.native="goToRoute('Dashboard')"
                    :title="$t('dashboard')"
                    icon="box"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Users')"
                    :title="$t('users')"
                    icon="users"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('AppOthers')"
                    :title="$t('settings')"
                    icon="settings"
                    :is-hover-disabled="true"
                />
            </OptionGroup>

            <!--Submenu: Content settings-->
            <OptionGroup v-if="clickedSubmenu === 'admin'">
                <Option
                    @click.native="goToRoute('Pages')"
                    :title="$t('pages')"
                    icon="monitor"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Language')"
                    :title="$t('languages')"
                    icon="globe"
                    :is-hover-disabled="true"
                />
            </OptionGroup>

            <!--Submenu: Billing settings-->
            <OptionGroup v-if="clickedSubmenu === 'admin' && config.subscriptionType !== 'none'">
                <Option
                    @click.native="goToRoute('AppPayments')"
                    :title="$t('payments')"
                    icon="credit-card"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Subscriptions')"
                    v-if="config.subscriptionType === 'fixed'"
                    :title="$t('subscriptions')"
                    icon="credit-card"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Plans')"
                    :title="$t('plans')"
                    icon="database"
                    :is-hover-disabled="true"
                />
                <Option
                    @click.native="goToRoute('Invoices')"
                    :title="$t('transactions')"
                    icon="file-text"
                    :is-hover-disabled="true"
                />
            </OptionGroup>
        </MenuMobileGroup>
    </MenuMobile>
</template>

<script>
import MenuMobileGroup from './MenuMobileGroup'
import OptionGroup from '../Menus/Components/OptionGroup'
import UserHeadline from '../UI/Others/UserHeadline'
import MenuMobile from './MenuMobile'
import Option from '../Menus/Components/Option'
import { ChevronLeftIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'

export default {
    name: 'MobileNavigation',
    components: {
        ChevronLeftIcon,
        MenuMobileGroup,
        UserHeadline,
        OptionGroup,
        MenuMobile,
        Option,
    },
    computed: {
        ...mapGetters(['config', 'user']),
        storageUsedPercentage() {
            if (!this.storage?.data?.attributes?.used || !this.storage?.data?.attributes?.capacity) {
                return 0;
            }
            
            const used = parseFloat(this.storage.data.attributes.used);
            const capacity = parseFloat(this.storage.data.attributes.capacity);
            
            return (used / capacity) * 100;
        },
        
        shouldShowStorageWarning() {
            return this.config?.subscriptionType === 'fixed' && 
                this.config?.storageLimit && 
                this.storage?.data?.attributes && 
                this.storageUsedPercentage >= 90;
        },
        isAdmin() {
            return this.user && this.user.data.attributes.role === 'admin'
        },
        backTitle() {
            let location = {
                settings: this.$t('settings'),
                admin: this.$t('administration'),
            }

            return this.$t('go_back_from_x', {location: location[this.clickedSubmenu]})
        },
    },
    data() {
        return {
            clickedSubmenu: undefined,
            storage: { data: { attributes: { used: 0, capacity: 0 } } }, // Initialize with default values
            isLoading: true,
        }
    },
    methods: {
        goToRoute(route) {
            this.$router.push({ name: route })
            this.clickedSubmenu = undefined
        },
        showSubmenu(name) {
            this.clickedSubmenu = name
        },
        goToFiles() {
            if (this.$route.name !== 'Files') this.$router.push({ name: 'Files' })

            this.$getDataByLocation(1)
            
            //this.$store.dispatch('getFolder')
        },
        fetchStorageData() {
            axios.get('/api/user/storage')
                .then((response) => {
                    this.storage = response.data || { data: { attributes: { used: 0, capacity: 0 } } }; // Fallback to default
                })
                .catch((error) => {
                    console.error('Error fetching storage data:', error);
                    this.storage = { data: { attributes: { used: 0, capacity: 0 } } }; // Fallback on error
                });
        },
        logOut() {
            // Dispatch the logout action to the store
            this.$store.dispatch('logOut');
        }
    },
// GoLink CHANGE HERE
    created() {
        // Call the function to fetch the storage data initially
        this.fetchStorageData();

        // Refresh the storage data and notifications every 10 second
        setInterval(() => {
            this.fetchStorageData();
        }, 10000);

        axios.get('/api/user/storage').then((response) => {
            this.distribution = this.$mapStorageUsage(response.data)

            this.storage = response.data

            this.isLoading = false
        })
    },
// GoLink END
}
</script>
