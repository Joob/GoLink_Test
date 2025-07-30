<template>
    <div v-if="user">
        <Spinner v-if="isLoading" />
        
        <!--Delete Account-->
        <div class="card shadow-card danger-zone-card">
            <FormLabel icon="trash-2" class="danger-zone-header">
                Apagar Conta
            </FormLabel>

            <InfoBox class="danger-zone-info">
                <p>Apagar dados da conta, respectivos ficheiros, tudo o que tenha guardado na conta, será apagado permanentemente.</p>
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
.danger-zone-card {
    border: 2px solid #dc2626 !important;
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
    box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1), 0 2px 4px -1px rgba(220, 38, 38, 0.06) !important;
    
    .dark & {
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, var(--color-eel) 100%);
        border-color: #dc2626;
    }
}

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
    
    p {
        color: #7f1d1d !important;
        font-weight: 500;
        
        .dark & {
            color: #fca5a5 !important;
        }
    }
}

.danger-zone-button {
    border: 2px solid #dc2626 !important;
    background-color: #dc2626 !important;
    color: white !important;
    font-weight: bold;
    
    &:hover {
        background-color: #b91c1c !important;
        border-color: #b91c1c !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4) !important;
    }
    
    &:active {
        transform: translateY(0);
    }
}
</style>