<template>
    <div v-if="user">
        <Spinner v-if="isLoading" />
        
        <!--Delete Account-->
        <div class="card shadow-card">
            <FormLabel icon="trash-2" class="danger-zone-header">
                Apagar Conta
            </FormLabel>

            <InfoBox class="danger-zone-info">
                <p class="danger-zone-text">Apagar dados da conta, respectivos ficheiros, tudo o que tenha guardado na conta, será apagado permanentemente.</p>
            </InfoBox>

            <ButtonBase
                @click.native="openDeleteAccountPopup"
                type="submit"
                button-style="danger"
                class="button-base w-full danger sm:w-auto danger-zone-button"
            >
                Apagar conta
            </ButtonBase>
        </div>
    </div>
</template>

<script>
import FormLabel from '../../components/UI/Labels/FormLabel'
import ButtonBase from '../../components/UI/Buttons/ButtonBase'
import InfoBox from '../../components/UI/Others/InfoBox'
import Spinner from '../../components/UI/Others/Spinner'
import { events } from '../../bus'
import { mapGetters } from 'vuex'

export default {
    name: 'DangerZone',
    components: {
        ButtonBase,
        FormLabel,
        InfoBox,
        Spinner,
    },
    computed: {
        ...mapGetters(['user']),
    },
    data() {
        return {
            isLoading: false,
        }
    },
    methods: {
        openDeleteAccountPopup() {
            events.$emit('popup:open', { 
                name: 'delete-account-confirmation'
            })
        },
    },
}
</script>

<style scoped lang="scss">
.danger-zone-header {
    color: #dc2626 !important;
    font-weight: bold;
    
    .dark & {
        color: #f87171 !important;
    }
}

.danger-zone-info {
    border-color: #dc2626 !important;
    background-color: rgba(220, 38, 38, 0.05) !important;
    
    .dark & {
        background-color: rgba(220, 38, 38, 0.1) !important;
        border-color: #dc2626 !important;
    }
}

.danger-zone-text {
    color: #7f1d1d !important;
    font-weight: 500;
    
    .dark & {
        color: #fca5a5 !important;
    }
}

.danger-zone-button {
    border: 2px solid #ef4444 !important;
    background-color: #ef4444 !important;
    color: white !important;
    font-weight: bold;
    
    * {
        color: white !important;
    }
    
    &:hover {
        background-color: #f87171 !important;
        border-color: #f87171 !important;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
        
        * {
            color: white !important;
        }
    }
    
    &:active {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        transform: translateY(0);
        color: white !important;
        
        * {
            color: white !important;
        }
    }
    
    &:focus {
        color: white !important;
        
        * {
            color: white !important;
        }
    }
    
    .dark & {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        color: white !important;
        
        * {
            color: white !important;
        }
        
        &:hover {
            background-color: #f87171 !important;
            border-color: #f87171 !important;
            color: white !important;
            
            * {
                color: white !important;
            }
        }
        
        &:active {
            background-color: #ef4444 !important;
            border-color: #ef4444 !important;
            color: white !important;
            
            * {
                color: white !important;
            }
        }
        
        &:focus {
            color: white !important;
            
            * {
                color: white !important;
            }
        }
    }
}
</style>