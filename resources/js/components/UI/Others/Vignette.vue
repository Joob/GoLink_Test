<template>
    <transition name="vignette">
        <div
            v-if="isVisible"
            class="vignette bg-opacity-[0.35] dark:bg-opacity-[0.70]"
            @click="closePopup"
        ></div>
    </transition>
</template>

<script>
import { events } from '../../../bus'
import { mapGetters } from 'vuex'

export default {
    name: 'Vignette',
    computed: {
        ...mapGetters(['processingPopup']),
        isVisible() {
            return this.processingPopup || this.isVisibleVignette
        },
    },
    data() {
        return {
            isVisibleVignette: false,
        }
    },
    methods: {
        closePopup() {
            // Fecha primeiro o vignette
            this.isVisibleVignette = false
            
            // Depois emite os eventos
            events.$emit('popup:close')
            events.$emit('spotlight:hide')
            events.$emit('mobile-menu:hide')
        },
    },
    created() {
        // Store event listener references for cleanup
        this.showVignetteHandler = () => {
            this.isVisibleVignette = true
        };
        
        this.hideVignetteHandler = () => {
            this.isVisibleVignette = false
        };

        // Show vignette
        events.$on('popup:open', this.showVignetteHandler)
        events.$on('alert:open', this.showVignetteHandler)
        events.$on('success:open', this.showVignetteHandler)
        events.$on('confirm:open', this.showVignetteHandler)

        // Hide vignette
        events.$on('popup:close', this.hideVignetteHandler)
    },
    beforeDestroy() {
        // Clean up all event listeners to prevent memory leaks
        events.$off('popup:open', this.showVignetteHandler)
        events.$off('alert:open', this.showVignetteHandler)
        events.$off('success:open', this.showVignetteHandler)
        events.$off('confirm:open', this.showVignetteHandler)
        events.$off('popup:close', this.hideVignetteHandler)
    },
}
</script>

<style lang="scss" scoped>
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

.vignette {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    z-index: 40;
}

.vignette-enter-active {
    animation: vignette-in 0.15s linear;
}

.vignette-leave-active {
    animation: vignette-in 0.15s cubic-bezier(0.4, 0, 1, 1) reverse;
}

@keyframes vignette-in {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}
</style>