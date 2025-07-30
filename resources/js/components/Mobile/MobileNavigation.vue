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
            <!--User Storage Warning - SÓ aparece quando resta 10% ou menos-->
            <div v-if="shouldShowStorageWarning && !clickedSubmenu" class="storage-warning-mobile">
                <div 
                    class="storage-warning-content-mobile"
                    :title="`Atenção: Apenas ${remainingPercentage.toFixed(1)}% de espaço livre (${remainingStorage} restantes)`"
                >
                    <div class="warning-header">
                        <span class="warning-icon">⚠️</span>
                        <span class="warning-title">{{ $t('storage_warning') }}</span>
                    </div>
                    
                    <div class="storage-details">
                        <div class="usage-info">
                            <span class="label">{{ $t('used') }}:</span>
                            <span class="value-used">{{ storageUsed }}</span>
                        </div>
                        
                        <div class="capacity-info">
                            <span class="label">{{ $t('total') }}:</span>
                            <span class="value-total">{{ storageCapacity }}</span>
                        </div>
                        
                        <div class="remaining-info">
                            <span class="label">{{ $t('remaining') }}:</span>
                            <span class="value-remaining">{{ remainingStorage }}</span>
                        </div>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" :style="`width: ${storageUsedPercentage}%`"></div>
                    </div>
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
                <Option
                    @click.native="goToRoute('DangerZone')"
                    title="Zona Perigosa"
                    icon="trash-2"
                    :is-hover-disabled="true"
                    class="danger-zone-option"
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
        
        storageUsedBytes() {
            if (!this.storage?.data?.attributes?.used) return 0;
            return this.parseStorageValue(this.storage.data.attributes.used);
        },
        
        storageCapacityBytes() {
            if (!this.storage?.data?.attributes?.capacity) return 0;
            return this.parseStorageValue(this.storage.data.attributes.capacity);
        },
        
        storageUsed() {
            return this.storage?.data?.attributes?.used || '0 B';
        },
        
        storageCapacity() {
            return this.storage?.data?.attributes?.capacity || '0 B';
        },
        
        storageUsedPercentage() {
            if (this.storageCapacityBytes === 0) return 0;
            return (this.storageUsedBytes / this.storageCapacityBytes) * 100;
        },
        
        remainingBytes() {
            return Math.max(0, this.storageCapacityBytes - this.storageUsedBytes);
        },
        
        remainingPercentage() {
            if (this.storageCapacityBytes === 0) return 100;
            return (this.remainingBytes / this.storageCapacityBytes) * 100;
        },
        
        remainingStorage() {
            return this.formatStorageSize(this.remainingBytes);
        },
        
        shouldShowStorageWarning() {
            // Só verificar se tem plano fixo
            if (this.config?.subscriptionType !== 'fixed' || !this.config?.storageLimit) {
                return false;
            }
            
            // Verificar se tem dados válidos
            if (!this.storage?.data?.attributes?.used || !this.storage?.data?.attributes?.capacity) {
                return false;
            }
            
            // Verificar se usuário está logado
            if (!this.user) {
                return false;
            }
            
            // MOSTRAR WARNING APENAS QUANDO RESTA 10% OU MENOS
            const showWarning = this.remainingPercentage <= 10;

            return showWarning;
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
            storage: { data: { attributes: { used: 0, capacity: 0 } } },
            isLoading: true,
            isNavigating: false,
            storageInterval: null,
            isLoggingOut: false,
            storageRequest: null,
        }
    },
    
    methods: {
        parseStorageValue(value) {
            // Se já for número, retornar direto
            if (typeof value === 'number') {
                return value;
            }
            
            // Se for string, fazer parse
            if (typeof value === 'string') {
                // Remover espaços e converter para uppercase
                const cleanValue = value.trim().toUpperCase();
                
                // Regex para capturar número e unidade
                const match = cleanValue.match(/^([\d.]+)\s*([KMGT]?B)?$/);
                if (!match) return 0;
                
                const num = parseFloat(match[1]);
                const unit = match[2] || 'B';
                
                // Multiplicadores para cada unidade
                const multipliers = {
                    'B': 1,
                    'KB': 1024,
                    'MB': 1024 ** 2,
                    'GB': 1024 ** 3,
                    'TB': 1024 ** 4
                };
                
                return num * (multipliers[unit] || 1);
            }
            
            return 0;
        },
        
        formatStorageSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const k = 1024;
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + units[i];
        },
        
        async goToRoute(route) {
            if (this.isNavigating || this.isLoggingOut) return;
            
            try {
                this.isNavigating = true;
                this.clickedSubmenu = undefined;
                
                await this.$nextTick();
                
                if (this.$route.name !== route) {
                    await this.$router.push({ name: route });
                }
                
                this.closeMobileMenu();
                await this.$nextTick();
                this.$emit('navigation-completed', route);
                
            } catch (error) {
                console.error('Erro durante a navegação:', error);
            } finally {
                setTimeout(() => {
                    this.isNavigating = false;
                }, 300);
            }
        },
        
        showSubmenu(name) {
            this.clickedSubmenu = name;
        },
        
        async goToFiles() {
            if (this.isNavigating || this.isLoggingOut) return;
            
            try {
                this.isNavigating = true;
                this.closeMobileMenu();
                
                if (this.$route.name !== 'Files') {
                    await this.$router.push({ name: 'Files' });
                }
                
                await this.$nextTick();
                this.$getDataByLocation(1);
                
            } catch (error) {
                console.error('Erro ao navegar para Files:', error);
            } finally {
                setTimeout(() => {
                    this.isNavigating = false;
                }, 300);
            }
        },
        
        fetchStorageData() {
            // Não fazer fetch se estiver fazendo logout, se não houver usuário, ou se o componente foi destruído
            if (this.isLoggingOut || !this.user || this._isDestroyed || this.$isServer) {
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

        async logOut() {
            if (this.isNavigating || this.isLoggingOut) return;
            
            try {
                this.isLoggingOut = true;
                this.isNavigating = true;
                
                // Cancelar qualquer requisição de storage pendente
                if (this.storageRequest) {
                    this.storageRequest.cancel('User logging out');
                }
                
                // Limpar o interval antes do logout
                this.clearStorageInterval();
                
                // Resetar dados de storage imediatamente
                this.storage = { data: { attributes: { used: 0, capacity: 0 } } };
                
                this.closeMobileMenu();
                await this.$nextTick();
                
                // Chamar a action de logout do Vuex
                await this.$store.dispatch('logOut');
                
            } catch (error) {
                console.error('Erro durante logout:', error);
            } finally {
                // Não resetar as flags aqui, pois o componente será destruído
            }
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
                    }, 10000); // Atualiza a cada 10 segundos
                }
            }
        },
        
        closeMobileMenu() {
            if (this.$parent && typeof this.$parent.closeMobileMenu === 'function') {
                this.$parent.closeMobileMenu();
            } else {
                this.$emit('close-mobile-menu');
            }
        },
    },
    
    watch: {
        '$route'(to, from) {
            if (to.name !== from.name) {
                this.clickedSubmenu = undefined;
            }
        },

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
                }
            },
            immediate: true
        }
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
    }
}
</script>

<style scoped lang="scss">
// Storage Warning Styles para Mobile - Aparece APENAS quando resta 10% ou menos
.storage-warning-mobile {
    margin: 0.75rem 1.25rem;
    padding: 0;
    
    .storage-warning-content-mobile {
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(255, 165, 0, 0.1) 100%);
        border: 1px solid rgba(220, 38, 38, 0.3);
        border-radius: 0.75rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        
        &:hover {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(255, 165, 0, 0.15) 100%);
            border-color: rgba(220, 38, 38, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }
        
        .dark & {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(255, 165, 0, 0.15) 100%);
            border-color: rgba(220, 38, 38, 0.4);
            
            &:hover {
                background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(255, 165, 0, 0.2) 100%);
                border-color: rgba(220, 38, 38, 0.5);
            }
        }
        
        .warning-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            
            .warning-icon {
                font-size: 1.125rem;
                margin-right: 0.5rem;
                animation: pulse 2s infinite;
            }
            
            .warning-title {
                font-size: 0.875rem;
                font-weight: bold;
                color: #dc2626;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        }
        
        .storage-details {
            margin-bottom: 0.75rem;
            
            > div {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.375rem;
                
                &:last-child {
                    margin-bottom: 0;
                }
                
                .label {
                    font-size: 0.75rem;
                    color: #6b7280;
                    font-weight: 500;
                }
                
                .value-used {
                    font-size: 0.75rem;
                    font-weight: bold;
                    color: #dc2626;
                }
                
                .value-total {
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: #374151;
                    
                    .dark & {
                        color: #d1d5db;
                    }
                }
                
                .value-remaining {
                    font-size: 0.75rem;
                    font-weight: bold;
                    color: #dc2626;
                    animation: blink 1.5s infinite;
                }
            }
        }
        
        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background-color: rgba(229, 231, 235, 0.8);
            border-radius: 0.25rem;
            overflow: hidden;
            position: relative;
            
            .dark & {
                background-color: rgba(55, 65, 81, 0.8);
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #dc2626 0%, #f59e0b 50%, #dc2626 100%);
                border-radius: 0.25rem;
                transition: width 0.5s ease;
                position: relative;
                
                &::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(
                        90deg,
                        transparent 0%,
                        rgba(255, 255, 255, 0.3) 50%,
                        transparent 100%
                    );
                    animation: shimmer 2s infinite;
                }
            }
        }
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes blink {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

// Estados de navegação
.navigating {
    pointer-events: none;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

// Estado de logout
.logging-out {
    pointer-events: none;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

// Danger zone styling
.danger-zone-option {
    background-color: #ef4444 !important;
    border: 2px solid #ef4444 !important;
    border-radius: 8px !important;
    margin: 4px !important;
    color: white !important;
    
    &:hover {
        background-color: #f87171 !important;
        border-color: #f87171 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
    }
    
    &:active,
    &:focus,
    &.router-link-active {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        color: white !important;
        transform: none;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
    }
    
    .dark & {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        color: white !important;
        
        &:hover {
            background-color: #f87171 !important;
            border-color: #f87171 !important;
        }
        
        &:active,
        &:focus,
        &.router-link-active {
            background-color: #ef4444 !important;
            border-color: #ef4444 !important;
            color: white !important;
        }
    }
}
</style>