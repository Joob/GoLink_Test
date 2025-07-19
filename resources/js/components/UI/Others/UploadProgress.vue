<template>
    <transition name="info-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress">
            <div class="progress-title">
                <!--Is processing-->
                <span v-if="isProcessingFile" class="flex items-center justify-center">
                    <refresh-cw-icon size="12" class="sync-alt text-theme" />
                    {{ $t('uploading.processing_file') }}
                </span>

                <!--Multi file upload-->
                <span v-if="!isProcessingFile && fileQueue.length > 0">
                    {{
                        $t('uploading.progress', {
                            current: filesInQueueUploaded,
                            total: filesInQueueTotal,
                            progress: uploadingProgress,
                        })
                    }}
                </span>
            </div>
            
            <!-- Enhanced Progress Info -->
            <div v-if="uploadStats" class="progress-details">
                <div class="upload-stats">
                    <span class="upload-size">{{ uploadStats.uploadedFormatted }} / {{ uploadStats.totalFormatted }}</span>
                    <span v-if="uploadStats.speed" class="upload-speed">{{ uploadStats.speed }}</span>
                    <span v-if="uploadStats.timeRemaining" class="upload-time">{{ uploadStats.timeRemaining }} remaining</span>
                </div>
            </div>
            
            <div class="progress-wrapper">
                <ProgressBar :progress="uploadingProgress" />
                <div class="progress-controls">
                    <span 
                        v-if="!isPaused" 
                        @click="pauseUpload" 
                        :title="$t('uploading.pause')" 
                        class="control-icon"
                    >
                        <pause-icon size="16" class="hover-text-theme" />
                    </span>
                    <span 
                        v-if="isPaused" 
                        @click="resumeUpload" 
                        :title="$t('uploading.resume')" 
                        class="control-icon"
                    >
                        <play-icon size="16" class="hover-text-theme" />
                    </span>
                    <span @click="cancelUpload" :title="$t('uploading.cancel')" class="control-icon cancel-icon">
                        <x-icon size="16" class="hover-text-theme" />
                    </span>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import ProgressBar from './ProgressBar'
import { RefreshCwIcon, XIcon, PauseIcon, PlayIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'UploadProgress',
    components: {
        RefreshCwIcon,
        ProgressBar,
        XIcon,
        PauseIcon,
        PlayIcon,
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
            'uploadStats',
            'isPaused',
        ]),
    },
    methods: {
        cancelUpload() {
            this.$cancelCurrentUpload()
            setTimeout(() => {
                events.$emit('toaster', { 
                    type: 'danger',
                    message: this.$t('uploaded_canceled'),
                })
            }, 1000) // 1 seconds delay
        },
        pauseUpload() {
            this.$pauseUpload()
            events.$emit('toaster', {
                type: 'info',
                message: this.$t('uploading.paused'),
            })
        },
        resumeUpload() {
            this.$resumeUpload()
            events.$emit('toaster', {
                type: 'info',
                message: this.$t('uploading.resumed'),
            })
        },
    },
}
</script>

<style scoped lang="scss">
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

.sync-alt {
    animation: spin 1s linear infinite;
    margin-right: 5px;

    polyline,
    path {
        color: inherit;
    }
}

@keyframes spin {
    0% {
        transform: rotate(0);
    }
    100% {
        transform: rotate(360deg);
    }
}

.info-panel-enter-active,
.info-panel-leave-active {
    transition: all 0.3s ease;
}

.info-panel-enter,
.info-panel-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}

.upload-progress {
    width: 100%;
    position: relative;
    z-index: 1;

    .progress-title {
        font-weight: 700;
        text-align: center;

        span {
            @include font-size(14);
        }
    }

    .progress-details {
        margin: 0.5rem 0;
        
        .upload-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: $text_muted;
            
            .upload-size {
                font-weight: 500;
            }
            
            .upload-speed {
                color: $theme;
            }
            
            .upload-time {
                font-style: italic;
            }
        }
    }

    .progress-wrapper {
        display: flex;
        align-items: center;

        .progress-controls {
            display: flex;
            gap: 0.5rem;
            padding: 0 7px 0 13px;

            .control-icon {
                cursor: pointer;
                padding: 0.25rem;
                border-radius: 4px;
                transition: all 0.2s ease;
                
                &:hover {
                    background: rgba($theme, 0.1);
                    
                    line,
                    polyline,
                    circle {
                        color: $theme;
                    }
                }

                &.cancel-icon:hover {
                    background: rgba($danger, 0.1);
                    
                    line {
                        color: $danger;
                    }
                }
            }
        }
    }
}

.dark {
    .progress-bar {
        background: $dark_mode_foreground;
    }
    
    .progress-details {
        .upload-stats {
            color: $dark_mode_text_muted;
            
            .upload-speed {
                color: $theme;
            }
        }
    }
    
    .control-icon {
        &:hover {
            background: rgba($theme, 0.2);
        }
        
        &.cancel-icon:hover {
            background: rgba($danger, 0.2);
        }
    }
}

@media (max-width: 768px) {
    .progress-details {
        .upload-stats {
            flex-direction: column;
            gap: 0.25rem;
            text-align: center;
        }
    }
    
    .progress-controls {
        flex-direction: column;
        gap: 0.25rem !important;
    }
}
</style>
