<template>
    <transition name="popup">
        <div
            v-if="isVisibleWrapper"
            class="popup fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
        >
            <!-- Overlay -->
            <div 
                class="absolute inset-0 bg-black/30 backdrop-blur-sm" 
                @click="closePopup"
            ></div>
            
            <!-- Popup content -->
            <div class="popup-content relative z-10 m-4 w-full max-w-[490px] bg-white shadow-xl dark:bg-dark-foreground md:m-10 md:rounded-xl">
                <slot />
            </div>
        </div>
    </transition>
</template>

<script>
import { events } from '../../../bus'

export default {
    name: 'PopupWrapper',
    props: ['name'],
    data() {
        return {
            isVisibleWrapper: false,
        }
    },
    methods: {
        closePopup() {
            events.$emit('popup:close')
        },
    },
    created() {
        // Open called popup
        events.$on('popup:open', ({ name }) => {
            if (this.name === name) this.isVisibleWrapper = true

            if (this.name !== name) this.isVisibleWrapper = false
        })

        // Open called popup
        events.$on('confirm:open', ({ name }) => {
            if (this.name === name) this.isVisibleWrapper = true
        })

        // Close popup
        events.$on('popup:close', () => (this.isVisibleWrapper = false))
    },
}
</script>

<style lang="scss" scoped>
// Mobile styles
@media only screen and (max-width: 959px) {
    .popup-content {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
        border-radius: 0;
        max-width: 100%;
        max-height: 100%;
        overflow-y: auto;
    }

    .popup-enter-active .popup-content {
        animation: popup-slide-in-mobile 0.35s 0.15s ease both;
    }

    .popup-leave-active .popup-content {
        animation: popup-slide-in-mobile 0.15s ease reverse;
    }

    @keyframes popup-slide-in-mobile {
        0% {
            transform: translateY(100%);
        }
        100% {
            transform: translateY(0);
        }
    }
}

// Desktop styles
@media only screen and (min-width: 960px) {
    .popup-enter-active .popup-content {
        animation: popup-slide-in-desktop 0.25s 0.1s ease both;
    }

    .popup-leave-active .popup-content {
        animation: popup-slide-in-desktop 0.15s ease reverse;
    }

    @keyframes popup-slide-in-desktop {
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

// Fade animation for overlay
.popup-enter-active {
    transition: opacity 0.3s ease;
}

.popup-leave-active {
    transition: opacity 0.15s ease;
}

.popup-enter-from,
.popup-leave-to {
    opacity: 0;
}
</style>