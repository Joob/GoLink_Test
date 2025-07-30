<template>
    <PopupWrapper name="delete-account-confirmation">
        <PopupHeader
            :title="$t('delete_permanently_account')"
            icon="user-plus"
        />

        <PopupContent>
            <!-- Show progress when deleting -->
            <div v-if="isDeleting" class="progress-container">
                <div class="progress-header">
                    <h3 class="progress-title">Estamos a apagar a conta</h3>
                    <div class="progress-percentage">{{ Math.round(safeProgress) }}%</div>
                </div>
                
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div 
                            class="progress-fill" 
                            :style="{ width: safeProgress + '%' }"
                        ></div>
                    </div>
                </div>
                
                <div class="progress-status">
                    <p class="current-step">{{ deletionProgress.current_step || 'A preparar eliminação...' }}</p>
                    <p v-if="deletionProgress.details" class="progress-details">{{ deletionProgress.details }}</p>
                    
                    <!-- File counter display -->
                    <div v-if="deletionProgress.files_remaining !== undefined" class="files-counter">
                        <span class="files-counter-text">
                            Ficheiros restantes: {{ deletionProgress.files_remaining }}
                        </span>
                    </div>
                    
                    <div class="remaining-info">
                        <span v-if="safeProgress < 100">
                            Faltam {{ Math.round(100 - safeProgress) }}%
                        </span>
                        <span v-else class="completion-message">
                            ✓ Concluído! A redirecionar...
                        </span>
                    </div>
                </div>
            </div>

            <!-- Show form when not deleting -->
            <ValidationObserver
                v-else
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
                        class="w-full danger-solid"
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
                this.isDeleting ||
                !this.emailConfirmation.trim() ||
                this.emailConfirmation.trim().toLowerCase() !== this.userEmail.trim().toLowerCase()
            );
        },
        safeProgress() {
            // Ensure we always have a valid number between 0 and 100
            const progress = this.deletionProgress?.percentage;
            if (typeof progress !== 'number' || isNaN(progress)) {
                return 0;
            }
            return Math.max(0, Math.min(100, progress));
        }
    },
    data() {
        return {
            emailConfirmation: '',
            isLoading: false,
            isDeleting: false,
            deletionProgress: {
                percentage: 0,
                current_step: 'Preparando eliminação...',
                completed: false,
                details: null
            },
            progressInterval: null
        }
    },
    methods: {
        async deleteAccount() {
            this.isLoading = true;
            this.isDeleting = true;
            
            // Initialize progress immediately with a small percentage to show green bar
            this.deletionProgress = {
                percentage: 1,
                current_step: 'Iniciando eliminação da conta...',
                completed: false,
                details: null
            };
            
            try {
                // Start progress monitoring before the actual delete request
                this.startProgressMonitoring();
                
                // Small delay to ensure the user sees the initial progress
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                await this.$store.dispatch('deleteUserAccount', {
                    email_confirmation: this.emailConfirmation,
                });
                
                // Progress monitoring will handle completion
                
            } catch (error) {
                this.isLoading = false;
                this.isDeleting = false;
                this.stopProgressMonitoring();
                this.$toast?.error?.(error.response?.data?.message || 'Erro ao apagar conta');
            }
        },
        
        startProgressMonitoring() {
            // Poll progress every 800ms (increased from 500ms to give more time between requests)
            this.progressInterval = setInterval(async () => {
                try {
                    const response = await this.$store.dispatch('getDeleteAccountProgress');
                    
                    // Ensure we have a valid response with proper defaults
                    this.deletionProgress = {
                        percentage: typeof response.percentage === 'number' ? response.percentage : 0,
                        current_step: response.current_step || 'A verificar progresso...',
                        completed: response.completed || false,
                        details: response.details || null,
                        files_remaining: response.files_remaining !== undefined ? response.files_remaining : undefined
                    };
                    
                    console.log('[Progress] Updated:', this.deletionProgress);
                    
                    // If deletion is completed
                    if (this.deletionProgress.completed || this.deletionProgress.percentage >= 100) {
                        this.stopProgressMonitoring();
                        
                        // Ensure we show 100% completion
                        this.deletionProgress.percentage = 100;
                        this.deletionProgress.current_step = 'Conta eliminada com sucesso!';
                        this.deletionProgress.completed = true;
                        
                        // Wait a moment to show completion then redirect
                        setTimeout(() => {
                            this.isLoading = false;
                            this.isDeleting = false;
                            this.emailConfirmation = '';
                            this.$closePopup();
                            this.$toast?.success?.('Conta apagada com sucesso.');
                        }, 3000); // Increased to 3 seconds to show completion message
                    }
                } catch (error) {
                    console.error('Error checking progress:', error);
                    
                    // If we get 401 or 403, it might mean the account was successfully deleted
                    if (error.response && [401, 403].includes(error.response.status)) {
                        console.log('Got auth error - account likely deleted, stopping monitoring');
                        this.stopProgressMonitoring();
                        
                        // Set completion state
                        this.deletionProgress = {
                            percentage: 100,
                            current_step: 'Conta eliminada com sucesso!',
                            completed: true
                        };
                        
                        setTimeout(() => {
                            this.isLoading = false;
                            this.isDeleting = false;
                            this.emailConfirmation = '';
                            this.$closePopup();
                            this.$toast?.success?.('Conta apagada com sucesso.');
                        }, 2000);
                    } else {
                        // For other errors, show error state but continue polling for a bit
                        this.deletionProgress = {
                            percentage: this.deletionProgress.percentage || 0,
                            current_step: 'Erro ao verificar progresso...',
                            completed: false
                        };
                    }
                }
            }, 800);
        },
        
        stopProgressMonitoring() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        },
        
        onCancel() {
            this.emailConfirmation = '';
            this.stopProgressMonitoring();
            this.isDeleting = false;
            this.isLoading = false;
            this.$closePopup();
        },
        onInput() {
            // Força reatividade para debug (podes remover depois)
            console.log('[DeleteAccount] Valor digitado:', this.emailConfirmation, '| Botão disabled:', this.isSubmitButtonDisabled);
        }
    },
    mounted() {
        this.emailConfirmation = '';
    },
    
    beforeDestroy() {
        // Clean up interval when component is destroyed
        this.stopProgressMonitoring();
    }
}
</script>

<style scoped lang="scss">
.progress-container {
    padding: 2rem 0;
    text-align: center;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.progress-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin: 0;
    
    .dark & {
        color: #f9fafb;
    }
}

.progress-percentage {
    font-size: 1.5rem;
    font-weight: bold;
    color: #059669;
    min-width: 60px;
    text-align: right;
    
    .dark & {
        color: #34d399;
    }
}

.progress-bar-container {
    margin-bottom: 1.5rem;
}

.progress-bar {
    width: 100%;
    height: 12px;
    background-color: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    
    .dark & {
        background-color: #374151;
    }
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
    border-radius: 6px;
    transition: width 0.3s ease;
    position: relative;
    
    &::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background-image: linear-gradient(
            45deg,
            rgba(255, 255, 255, 0.2) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, 0.2) 50%,
            rgba(255, 255, 255, 0.2) 75%,
            transparent 75%,
            transparent
        );
        background-size: 20px 20px;
        animation: progress-stripes 1s linear infinite;
    }
}

@keyframes progress-stripes {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 20px 0;
    }
}

.progress-status {
    text-align: left;
}

.current-step {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 0.5rem 0;
    font-weight: 500;
    
    .dark & {
        color: #d1d5db;
    }
}

.progress-details {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0 0 0.5rem 0;
    font-style: italic;
    
    .dark & {
        color: #6b7280;
    }
}

.files-counter {
    margin: 0.5rem 0;
    padding: 0.5rem;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    
    .dark & {
        background-color: #374151;
        border-color: #4b5563;
    }
    
    .files-counter-text {
        font-size: 0.875rem;
        font-weight: 600;
        color: #059669;
        
        .dark & {
            color: #34d399;
        }
    }
}

.remaining-info {
    font-size: 0.875rem;
    color: #9ca3af;
    
    .dark & {
        color: #9ca3af;
    }
}

.completion-message {
    color: #059669 !important;
    font-weight: 600;
    
    .dark & {
        color: #34d399 !important;
    }
}
</style>