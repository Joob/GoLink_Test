<template>
    <PopupWrapper>
        <div class="flex h-full -translate-y-7 transform items-center justify-center px-8 text-center md:translate-y-0">
            <div>
                <img src="https://cdnjs.cloudflare.com/ajax/libs/twemoji/16.0.1/svg/1f914.svg" alt="" class="mx-auto mb-4 w-20 md:mt-6 min-h-[80px]" />

                <h1 v-if="title" class="mb-2 text-2xl font-bold">
                    {{ title }}
                </h1>
                <p v-if="message" class="mb-4 text-sm">
                    {{ message }}
                </p>
            </div>
        </div>

        <PopupActions>
            <ButtonBase @click.native="closePopup" button-style="secondary" class="w-full" :disabled="isProcessing"
                >{{ $t('cancel') }}
            </ButtonBase>
            <ButtonBase @click.native="confirm" :button-style="buttonColor" class="w-full" :disabled="isProcessing"
                >{{ $t('yes_iam_sure') }}
            </ButtonBase>
        </PopupActions>
    </PopupWrapper>
</template>

<script>
import PopupWrapper from './Components/PopupWrapper'
import PopupActions from './Components/PopupActions'
import ButtonBase from '../UI/Buttons/ButtonBase'
import { events } from '../../bus'

export default {
    name: 'ConfirmPopup',
    components: {
        PopupWrapper,
        PopupActions,
        ButtonBase,
    },
    data() {
        return {
            confirmationData: [],
            message: undefined,
            title: undefined,
            buttonColor: undefined,
            isProcessing: false,
        }
    },
    methods: {
        closePopup() {
            // Prevent rapid multiple clicks
            if (this.isProcessing) return;
            this.isProcessing = true;
            
            events.$emit('popup:close');
            
            // Reset processing state after a short delay
            setTimeout(() => {
                this.isProcessing = false;
            }, 300);
        },
        confirm() {
            // Prevent rapid multiple clicks
            if (this.isProcessing) return;
            this.isProcessing = true;
            
            // Close popup
            events.$emit('popup:close');

            // Confirmation popup
            events.$emit('action:confirmed', this.confirmationData);

            // Clear confirmation data
            this.confirmationData = [];
            
            // Reset processing state after a short delay
            setTimeout(() => {
                this.isProcessing = false;
            }, 300);
        },
    },
    mounted() {
        // Store event listener reference for cleanup
        this.confirmOpenHandler = (args) => {
            this.title = args.title;
            this.message = args.message;
            this.confirmationData = args.action;
            this.buttonColor = 'danger';
            this.isProcessing = false;

            if (args.buttonColor) {
                this.buttonColor = args.buttonColor;
            }
        };

        // Register event listener
        events.$on('confirm:open', this.confirmOpenHandler);
    },
    beforeDestroy() {
        // Clean up event listeners to prevent memory leaks
        if (this.confirmOpenHandler) {
            events.$off('confirm:open', this.confirmOpenHandler);
        }
    },
}
</script>