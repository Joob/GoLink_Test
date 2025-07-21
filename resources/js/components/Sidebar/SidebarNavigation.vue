<template>
    <nav
        v-if="isVisibleNavigationBars"
        class="menu-bar z-10 hidden w-16 flex-none select-none bg-light-background pt-7 dark:bg-dark-foreground lg:grid xl:w-20"
    >
        <!--Navigation-->
        <div v-if="user" class="mb-auto text-center">
            <MemberAvatar class="mx-auto" :size="44" :is-border="false" :member="user" />

            <!--Usage-->
            <div
                v-if="config.subscriptionType === 'metered' && user.data.meta.usages"
                class="mt-2.5 text-center leading-3"
            >
                <b class="text-theme block text-xs font-bold leading-3">
                    {{ user.data.meta.usages.costEstimate }}
                </b>
                <span class="text-xs text-gray-500">
                    {{ $t('usage') }}
                </span>
            </div>

            <!--Navigation-->
            <div class="mt-2 relative">
                <NotificationBell @click.native="$store.commit('TOGGLE_NOTIFICATION_CENTER')" class="hover:bg-light-300 dark:hover:bg-4x-dark-foreground" />
            </div>

			<NotificationCenter v-if="isVisibleNotificationCenter" />

            <!--Navigation-->
            <div class="mt-6">
                <router-link
                    v-for="(item, i) in navigation"
                    :to="{ name: item.route }"
                    :key="i"
                    :title="item.title"
                    :class="[{ 'router-link-active': isSection(item.section) }, item.icon]"
                    class="mb-1.5 block"
                >
                    <div
                        class="button-icon text-theme inline-block cursor-pointer rounded-xl p-3 hover:bg-light-300 dark:hover:bg-4x-dark-foreground"
                    >
                        <hard-drive-icon v-if="item.icon === 'home'" size="20" />
                        <settings-icon v-if="item.icon === 'settings'" size="20" />
                        <user-icon v-if="item.icon === 'user'" size="20" />
                    </div>
                </router-link>
            </div>

            <!--Toggle Dark/Light mode-->
            <div @click="$store.dispatch('toggleThemeMode')" :title="$t('dark_mode_toggle')" class="mt-6 block">
                <div
                    class="button-icon inline-block cursor-pointer rounded-xl p-3 hover:bg-light-300 dark:hover:bg-4x-dark-foreground"
                >
                    <sun-icon v-if="isDarkMode" size="20" />
                    <moon-icon v-if="!isDarkMode" size="20" />
                </div>
            </div>
        </div>

        <!--Logout-->
        <div class="mt-auto text-center">
            <!--Storage Warning - Corrigido-->
            <div v-if="shouldShowStorageWarning" class="storage-warning">
                <div 
                    class="storage-warning-content" 
                    :title="$t('total_warning', { remaining: remainingStorage, percentage: remainingPercentage })"
                >
                    <div class="warning-icon">⚠️</div>
                    <div class="storage-text">
                        <!--<span class="warning-text">{{ $t('storage_warning_short') }}</span>-->
                        <span class="usage-amount">{{ updatedStorageUsed }}</span>
                        <span class="capacity-text">{{ storage.data.attributes.capacity }}</span>
                        <!--<span class="remaining-text">{{ remainingStorage }} {{ $t('remaining') }}</span>-->
                    </div>
                </div>
            </div>

            <!--Apple/Android-->
            <div class="mt-6 block">
                <div :title="$t('Apple/Android')" class="button-icon text-theme inline-block cursor-pointer rounded-xl p-3 hover:bg-light-300 dark:hover:bg-4x-dark-foreground">
                    <a href="https://codeberg.org/GoLink/" target="_blank">
                        <smartphone-icon size="20" alt="Apple/Android" />
                    </a>
                </div>
            </div>

            <div class="mt-6 block">
                <div :title="$t('version')" class="inline-block rounded-xl p-3 hover:bg-light-300 dark:hover:bg-4x-dark-foreground">
                    <b class="text-theme block text-xs font-bold leading-3">
                    {{ $t('v2.3.66') }}
                    </b>
                </div>
            </div>

            <div class="mt-6 block"></div>

            <div
                @click="$store.dispatch('logOut')"
                :title="$t('logout')"
                class="button-icon inline-block cursor-pointer rounded-xl p-3 hover:bg-light-300 dark:hover:bg-4x-dark-foreground"
            >
                <power-icon size="20"/>
            </div>
        </div>
    </nav>
</template>

<script>
import MemberAvatar from '../UI/Others/MemberAvatar'
import {mapGetters} from 'vuex'
import {HardDriveIcon, SmartphoneIcon, MoonIcon, PowerIcon, SettingsIcon, SunIcon, UserIcon,} from 'vue-feather-icons'
import NotificationCenter from "../Notifications/NotificationCenter"
import NotificationBell from "../Notifications/Components/NotificationBell";

export default {
    name: 'SidebarNavigation',
    components: {
		NotificationBell,
		NotificationCenter,
		HardDriveIcon,
        SmartphoneIcon,
        SettingsIcon,
        MemberAvatar,
        PowerIcon,
        UserIcon,
        MoonIcon,
        SunIcon,
    },
    computed: {
        ...mapGetters(['isVisibleNavigationBars', 'isDarkMode', 'config', 'user', 'isVisibleNotificationCenter']),
        
        storageUsedPercentage() {
            if (!this.storage?.data?.attributes?.used || !this.storage?.data?.attributes?.capacity) {
                return 0;
            }
            
            const used = parseFloat(this.storage.data.attributes.used);
            const capacity = parseFloat(this.storage.data.attributes.capacity);
            
            return (used / capacity) * 100;
        },
        
        remainingPercentage() {
            return 100 - this.storageUsedPercentage;
        },
        
        remainingStorage() {
            if (!this.storage?.data?.attributes?.used || !this.storage?.data?.attributes?.capacity) {
                return '0';
            }
            
            const used = parseFloat(this.storage.data.attributes.used);
            const capacity = parseFloat(this.storage.data.attributes.capacity);
            const remaining = capacity - used;
            
            // Formatar o valor com a mesma unidade da capacidade
            return this.formatStorageSize(remaining);
        },
        
        shouldShowStorageWarning() {
            // Mostrar aviso quando restam 10% ou menos do espaço disponível
            return this.config?.subscriptionType === 'fixed' && 
                this.config?.storageLimit && 
                this.storage?.data?.attributes && 
                this.remainingPercentage <= 10 &&
                this.user; // Só mostrar se o usuário estiver logado
        },
        
        updatedStorageUsed() {
            return this.storage?.data?.attributes?.used || '';
        },
        
        navigation() {
            if (this.user?.data?.attributes?.role === 'admin') {
                return [
                    {
                        route: 'Files',
                        section: 'Platform',
                        title: this.$t('locations.home'),
                        icon: 'home',
                    },
                    {
                        route: 'Profile',
                        section: 'User',
                        title: this.$t('locations.profile'),
                        icon: 'user',
                    },
                    {
                        route: 'Dashboard',
                        section: 'Admin',
                        title: this.$t('locations.settings'),
                        icon: 'settings',
                    },
                ];
            }

            return [
                {
                    route: 'Files',
                    section: 'Platform',
                    title: this.$t('locations.home'),
                    icon: 'home',
                },
                {
                    route: 'Profile',
                    section: 'User',
                    title: this.$t('locations.profile'),
                    icon: 'settings',
                },
            ];
        },
    },
    
	data() {
		return {
            isNotificationCenter: false,
            storage: { data: { attributes: { used: 0, capacity: 0 } } },
            isLoading: true,
            storageInterval: null,
            storageRequest: null,
            isLoggingOut: false,
        };
	},
    
    methods: {
        isSection(section) {
            return this.$route.matched[0].name === section
        },
        
        fetchStorageData() {
            // Não fazer fetch se não houver usuário, se estiver fazendo logout ou se o componente foi destruído
            if (!this.user || this.isLoggingOut || this._isDestroyed || this.$isServer) {
                return;
            }

            // Cancelar requisição anterior se ainda estiver pendente
            if (this.storageRequest) {
                this.storageRequest.cancel('New request initiated');
            }

            // Criar uma nova requisição cancelável
            const CancelToken = axios.CancelToken;
            this.storageRequest = CancelToken.source();

            axios.get('/api/user/storage', {
                cancelToken: this.storageRequest.token
            })
            .then((response) => {
                // Verificações adicionais antes de atualizar os dados
                if (!this.isLoggingOut && !this._isDestroyed && this.user) {
                    this.storage = response.data || { data: { attributes: { used: 0, capacity: 0 } } };
                    this.distribution = this.$mapStorageUsage(response.data);
                    this.isLoading = false;
                }
            })
            .catch((error) => {
                // Ignorar erros de cancelamento
                if (axios.isCancel(error)) {
                    return;
                }
                
                // Só mostrar erro se não for 401 (não autenticado) ou se não estiver fazendo logout
                if (error.response?.status !== 401 && !this.isLoggingOut && !this._isDestroyed) {
                    console.error('Error fetching storage data:', error);
                }
                
                // Resetar dados em caso de erro (mas só se ainda estiver logado)
                if (!this.isLoggingOut && !this._isDestroyed && this.user) {
                    this.storage = { data: { attributes: { used: 0, capacity: 0 } } };
                }
            })
            .finally(() => {
                // Limpar a referência da requisição
                this.storageRequest = null;
            });
        },
        
        formatStorageSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const k = 1024;
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + units[i];
        },

        // Método para limpar o interval
        clearStorageInterval() {
            if (this.storageInterval) {
                clearInterval(this.storageInterval);
                this.storageInterval = null;
            }
            
            // Cancelar requisição pendente se existir
            if (this.storageRequest) {
                this.storageRequest.cancel('Interval cleared');
                this.storageRequest = null;
            }
        },

        // Método para iniciar o fetch de storage
        startStorageFetch() {
            // Só iniciar se houver usuário logado e não estiver fazendo logout
            if (this.user && !this.isLoggingOut && !this._isDestroyed) {
                this.fetchStorageData();
                
                // Configurar interval apenas se não existir e se houver usuário
                if (!this.storageInterval) {
                    this.storageInterval = setInterval(() => {
                        // Verificação adicional antes de cada execução do interval
                        if (this.user && !this.isLoggingOut && !this._isDestroyed) {
                            this.fetchStorageData();
                        } else {
                            // Se as condições não forem mais atendidas, limpar o interval
                            this.clearStorageInterval();
                        }
                    }, 10000);
                }
            }
        }
    },
    
    watch: {
        // Watch para o usuário - parar/iniciar o fetch baseado no status de login
        user: {
            handler(newUser, oldUser) {
                if (newUser && !this.isLoggingOut && !this._isDestroyed) {
                    // Usuário logou - iniciar fetch
                    this.startStorageFetch();
                } else {
                    // Usuário deslogou ou está fazendo logout - parar fetch
                    this.clearStorageInterval();
                    // Resetar dados de storage
                    this.storage = { data: { attributes: { used: 0, capacity: 0 } } };
                    this.isLoading = true;
                }
            },
            immediate: true
        }
    },
    
    mounted() {
        this.$store.dispatch('getAppData')
    },
    
    created() {
        // Só iniciar o fetch se houver usuário
        if (this.user) {
            this.startStorageFetch();
        }
    },

    beforeDestroy() {
        // Limpar interval quando o componente for destruído
        this.clearStorageInterval();
        this.isLoggingOut = true; // Marcar que está sendo destruído
    }
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';

// Storage Warning Styles - Atualizado
.storage-warning {
    margin-top: 1rem;
    padding: 0.25rem;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    
    .storage-warning-content {
        background: rgba(220, 38, 38, 0.1);
        border: 1px solid rgba(220, 38, 38, 0.3);
        border-radius: 0.5rem;
        padding: 0.4rem 0.2rem;
        text-align: center;
        position: relative;
        box-sizing: border-box;
        width: 100%;
        cursor: pointer;
        transition: all 0.2s ease;
        
        &:hover {
            background: rgba(220, 38, 38, 0.15);
            border-color: rgba(220, 38, 38, 0.4);
        }
        
        .dark & {
            background: rgba(220, 38, 38, 0.15);
            border-color: rgba(220, 38, 38, 0.4);
            
            &:hover {
                background: rgba(220, 38, 38, 0.2);
                border-color: rgba(220, 38, 38, 0.5);
            }
        }
        
        .warning-icon {
            font-size: 0.75rem;
            margin-bottom: 0.2rem;
            animation: pulse 2s infinite;
        }
        
        .storage-text {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.1rem;
            
            .warning-text {
                font-size: 0.55rem;
                font-weight: bold;
                color: #dc2626;
                line-height: 1;
                text-transform: uppercase;
            }
            
            .usage-amount {
                font-size: 0.6rem;
                font-weight: bold;
                color: #dc2626;
                line-height: 1;
            }
            
            .capacity-text {
                font-size: 0.5rem;
                color: #6b7280;
                line-height: 1;
                word-break: break-all;
            }
            
            .remaining-text {
                font-size: 0.45rem;
                color: #dc2626;
                font-weight: 600;
                line-height: 1;
                margin-top: 0.1rem;
            }
        }
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.router-link-active {
    &.home .button-icon {
        path,
        line,
        polyline,
        rect,
        circle {
            color: inherit;
        }
    }

    &.trash .button-icon {
        background: rgba($red, 0.1);

        path,
        line,
        polyline,
        rect,
        circle {
            stroke: $red;
        }
    }

    &.settings .button-icon {
        background: rgba($purple, 0.1);

        path,
        line,
        polyline,
        rect,
        circle {
            stroke: $purple;
        }
    }

    &.user .button-icon {
        background: rgba($pink, 0.1);

        path,
        line,
        polyline,
        rect,
        circle {
            stroke: $pink;
        }
    }
}
</style>