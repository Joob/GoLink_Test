<template>
    <div class="progress-bar-container">
        <div 
            class="progress-bar-track"
            :class="trackClass"
        >
            <div 
                class="progress-bar-fill"
                :class="fillClass"
                :style="progressStyle"
            >
                <div 
                    v-if="showPercentage" 
                    class="progress-bar-text"
                    :class="textClass"
                >
                    {{ Math.round(progress) }}%
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProgressBar',
    props: {
        progress: {
            type: Number,
            default: 0,
            validator: (value) => value >= 0 && value <= 100
        },
        height: {
            type: String,
            default: 'h-2'
        },
        color: {
            type: String,
            default: 'bg-theme',
            validator: (value) => [
                'bg-theme', 'bg-green-500', 'bg-blue-500', 
                'bg-red-500', 'bg-yellow-500', 'bg-purple-500'
            ].includes(value)
        },
        trackColor: {
            type: String,
            default: 'bg-gray-200 dark:bg-gray-700'
        },
        rounded: {
            type: Boolean,
            default: true
        },
        animated: {
            type: Boolean,
            default: true
        },
        showPercentage: {
            type: Boolean,
            default: false
        },
        striped: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        progressStyle() {
            return {
                width: `${Math.min(Math.max(this.progress, 0), 100)}%`,
                transition: this.animated ? 'width 0.3s ease-in-out' : 'none'
            }
        },
        trackClass() {
            return [
                'relative w-full overflow-hidden',
                this.height,
                this.trackColor,
                this.rounded ? 'rounded-full' : 'rounded-sm'
            ]
        },
        fillClass() {
            return [
                'h-full flex items-center justify-center text-xs font-medium text-white relative',
                this.color,
                this.rounded ? 'rounded-full' : 'rounded-sm',
                this.striped ? 'progress-bar-striped' : '',
                this.animated && this.striped ? 'progress-bar-animated' : ''
            ]
        },
        textClass() {
            return [
                'absolute inset-0 flex items-center justify-center text-xs font-bold',
                this.progress > 50 ? 'text-white' : 'text-gray-900 dark:text-white'
            ]
        }
    }
}
</script>

<style lang="scss" scoped>
.progress-bar-container {
    .progress-bar-striped {
        background-image: linear-gradient(
            45deg,
            rgba(255, 255, 255, 0.15) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, 0.15) 50%,
            rgba(255, 255, 255, 0.15) 75%,
            transparent 75%,
            transparent
        );
        background-size: 1rem 1rem;
    }

    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite;
    }

    @keyframes progress-bar-stripes {
        from {
            background-position: 1rem 0;
        }
        to {
            background-position: 0 0;
        }
    }
}
</style>