<template>
    <div class="progress-bar" :class="{ 'has-animation': animated }">
        <div class="progress-track">
            <div 
                class="progress-fill bg-theme" 
                :class="stateClass"
                :style="{ width: displayProgress + '%' }"
            >
                <div v-if="animated" class="progress-shine"></div>
            </div>
        </div>
        <div v-if="showDetails" class="progress-details">
            <span class="progress-text">{{ progressText }}</span>
            <span v-if="speed" class="progress-speed">{{ speed }}</span>
            <span v-if="timeRemaining" class="progress-time">{{ timeRemaining }}</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProgressBar',
    props: {
        progress: {
            type: Number,
            default: 0
        },
        state: {
            type: String,
            default: 'loading', // loading, paused, error, success
            validator: value => ['loading', 'paused', 'error', 'success'].includes(value)
        },
        animated: {
            type: Boolean,
            default: true
        },
        showDetails: {
            type: Boolean,
            default: false
        },
        speed: {
            type: String,
            default: null
        },
        timeRemaining: {
            type: String,
            default: null
        },
        progressText: {
            type: String,
            default: null
        }
    },
    computed: {
        displayProgress() {
            return Math.min(Math.max(this.progress, 0), 100)
        },
        stateClass() {
            return `progress-${this.state}`
        }
    }
}
</script>

<style scoped lang="scss">
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

.progress-bar {
    width: 100%;
    
    .progress-track {
        width: 100%;
        height: 5px;
        background: $light_background;
        margin-top: 6px;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }

    .progress-fill {
        display: block;
        height: 100%;
        border-radius: 10px;
        max-width: 100%;
        transition: width 0.3s ease;
        position: relative;
        overflow: hidden;
        
        &.progress-loading {
            background: $theme;
        }
        
        &.progress-paused {
            background: #fbbf24; // amber
        }
        
        &.progress-error {
            background: #ef4444; // red
        }
        
        &.progress-success {
            background: #10b981; // green
        }
    }

    .progress-shine {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shine 2s infinite;
    }

    &.has-animation .progress-fill {
        .progress-shine {
            animation: shine 2s infinite;
        }
    }

    .progress-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
        @include font-size(12);
        color: $text;
        opacity: 0.7;

        .progress-text {
            font-weight: 600;
        }

        .progress-speed,
        .progress-time {
            font-weight: 400;
        }
    }
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

.dark {
    .progress-bar {
        .progress-track {
            background: $dark_mode_foreground;
        }
        
        .progress-details {
            color: $dark_mode_text_primary;
        }
    }
}

@media only screen and (min-width: 680px) {
    .dark .progress-bar .progress-track {
        background: $dark_mode_foreground;
    }
}
</style>
