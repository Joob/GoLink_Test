<template>
    <transition name="popup" @after-leave="onAfterLeave">
        <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-10">
            <!-- Overlay with blur only -->
            <div 
                class="absolute inset-0 backdrop-blur-md bg-transparent" 
                @click="closePopup"
            ></div>
            
            <!-- Popup content -->
            <div class="relative z-10 w-full max-w-[490px] bg-white shadow-xl dark:bg-dark-foreground md:rounded-xl">
                <slot />
            </div>
        </div>
    </transition>
</template>

<script>
import { events } from '../../../bus'

export default {
    name: 'PopupWrapper',
    props: {
        name: {
            type: String,
            default: 'confirm'
        }
    },
    data() {
        return {
            isVisible: false
        }
    },
    methods: {
        closePopup() {
            this.isVisible = false
            events.$emit('popup:close', this.name)
        },
        onAfterLeave() {
            // Ensures any cleanup happens after transition
            this.$emit('closed')
        }
    },
    mounted() {
        // Open handlers
        events.$on('confirm:open', () => {
            if (this.name === 'confirm') {
                this.isVisible = true
            }
        })
        
        events.$on('popup:open', (data) => {
            if (data && data.name === this.name) {
                this.isVisible = true
            }
        })
        
        // Close handlers
        events.$on('popup:close', (closeName) => {
            if (!closeName || closeName === this.name) {
                this.isVisible = false
            }
        })
    },
    beforeDestroy() {
        events.$off('confirm:open')
        events.$off('popup:open')
        events.$off('popup:close')
    }
}
</script>

<style lang="scss" scoped>
.popup-leave-active {
    animation: popup-slide-in 0.15s ease reverse;
}

@media only screen and (min-width: 960px) {
    .popup-enter-active {
        animation: popup-slide-in 0.25s 0.1s ease both;
    }

    @keyframes popup-slide-in {
        0% {
            opacity: 0;
            transform: translateY(100px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
}

@media only screen and (max-width: 960px) {
    .popup-enter-active {
        animation: popup-slide-in 0.35s 0.15s ease both;
    }

    @keyframes popup-slide-in {
        0% {
            transform: translateY(100%);
        }
        100% {
            transform: translateY(0);
        }
    }
}
</style>