<template>
    <transition name="upload-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress-bar">
            <div class="upload-header">
                <div class="upload-title">
                    <refresh-cw-icon v-if="isProcessingFile" size="14" class="sync-icon" />
                    <upload-icon v-else size="14" />
                    <span class="title-text">
                        <span v-if="isProcessingFile">{{ $t('uploading.processing_file') }}</span>
                        <span v-else>{{ $t('uploading.uploading_file') }} ({{ filesInQueueUploaded + 1 }}/{{ filesInQueueTotal }})</span>
                    </span>
                </div>
                <button @click="toggleMinimize" class="minimize-btn" :title="isMinimized ? $t('uploading.expand') : $t('uploading.minimize')">
                    <chevron-up-icon v-if="!isMinimized" size="16" />
                    <chevron-down-icon v-else size="16" />
                </button>
            </div>

            <div v-show="!isMinimized" class="upload-content">
                <!-- Current Upload Progress -->
                <div class="current-upload">
                    <div class="file-info">
                        <div class="file-name">{{ currentFileName }}</div>
                        <div class="upload-stats">
                            <span class="progress-text">{{ uploadingProgress }}%</span>
                            <span v-if="uploadSpeedFormatted" class="upload-speed">{{ uploadSpeedFormatted }}</span>
                            <span v-if="timeRemainingFormatted" class="time-remaining">{{ timeRemainingFormatted }}</span>
                        </div>
                    </div>
                    <div class="progress-wrapper">
                        <ProgressBar :progress="uploadingProgress" />
                        <button 
                            v-if="canCancelUpload" 
                            @click="cancelUpload" 
                            class="cancel-btn" 
                            :title="$t('uploading.cancel')"
                        >
                            <x-icon size="14" />
                        </button>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div v-if="recentUploads.length > 0" class="recent-uploads">
                    <div class="recent-title">{{ $t('uploading.recent_uploads') }}</div>
                    <div class="recent-list">
                        <div 
                            v-for="file in recentUploads" 
                            :key="file.id" 
                            class="recent-item"
                        >
                            <div class="recent-file-info">
                                <file-icon size="12" />
                                <span class="recent-file-name">{{ file.name }}</span>
                            </div>
                            <div class="recent-file-size">{{ formatFileSize(file.size) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import ProgressBar from './UI/Others/ProgressBar'
import { 
    RefreshCwIcon, 
    XIcon, 
    ChevronUpIcon, 
    ChevronDownIcon, 
    UploadIcon, 
    FileIcon 
} from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../bus'

export default {
    name: 'UploadProgressBar',
    components: {
        RefreshCwIcon,
        ProgressBar,
        XIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        UploadIcon,
        FileIcon,
    },
    data() {
        return {
            isMinimized: false,
        }
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
            'uploadSpeed',
            'currentUploadingFile',
            'recentUploads',
            'canCancelUpload',
            'estimatedTimeRemaining',
        ]),
        currentFileName() {
            return this.currentUploadingFile?.file?.name || 
                   this.fileQueue[0]?.file?.name || 
                   this.$t('uploading.unknown_file')
        },
        uploadSpeedFormatted() {
            if (!this.uploadSpeed || this.uploadSpeed <= 0) return null
            const speedMB = this.uploadSpeed / (1024 * 1024)
            return speedMB >= 1 
                ? `${speedMB.toFixed(1)} MB/s` 
                : `${Math.max(1, (this.uploadSpeed / 1024)).toFixed(0)} KB/s`
        },
        timeRemainingFormatted() {
            if (!this.estimatedTimeRemaining || this.estimatedTimeRemaining <= 0) return null
            const seconds = Math.floor(this.estimatedTimeRemaining)
            if (seconds < 60) return `${seconds}s`
            const minutes = Math.floor(seconds / 60)
            const remainingSeconds = seconds % 60
            return remainingSeconds > 0 ? `${minutes}m ${remainingSeconds}s` : `${minutes}m`
        },
    },
    methods: {
        toggleMinimize() {
            this.isMinimized = !this.isMinimized
        },
        cancelUpload() {
            events.$emit('cancel-upload')
            this.$store.commit('CLEAR_UPLOAD_PROGRESS')
            setTimeout(() => {
                events.$emit('toaster', { 
                    type: 'danger',
                    message: this.$t('uploaded_canceled'),
                })
            }, 1000)
        },
        formatFileSize(bytes) {
            if (!bytes) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
        },
    },
}
</script>

<style scoped lang="scss">
@import '../../sass/vuefilemanager/variables';
@import '../../sass/vuefilemanager/mixins';

.upload-panel-enter-active,
.upload-panel-leave-active {
    transition: all 0.3s ease;
}

.upload-panel-enter,
.upload-panel-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.upload-progress-bar {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 320px;
    max-width: calc(100vw - 40px);
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow: hidden;

    .upload-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        background: rgba(0, 0, 0, 0.02);

        .upload-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            color: $text;

            .sync-icon {
                animation: spin 1s linear infinite;
                color: $theme;
            }
        }

        .minimize-btn {
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: $text_muted;
            border-radius: 4px;
            transition: all 0.2s ease;

            &:hover {
                background: rgba(0, 0, 0, 0.05);
                color: $theme;
            }
        }
    }

    .upload-content {
        padding: 16px;

        .current-upload {
            margin-bottom: 16px;

            .file-info {
                margin-bottom: 8px;

                .file-name {
                    font-weight: 500;
                    font-size: 13px;
                    color: $text;
                    margin-bottom: 4px;
                    word-break: break-all;
                    line-height: 1.3;
                }

                .upload-stats {
                    display: flex;
                    gap: 12px;
                    font-size: 11px;
                    color: $text_muted;

                    .progress-text {
                        font-weight: 600;
                        color: $theme;
                    }
                }
            }

            .progress-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;

                .cancel-btn {
                    background: none;
                    border: none;
                    padding: 4px;
                    cursor: pointer;
                    color: $text_muted;
                    border-radius: 4px;
                    transition: all 0.2s ease;

                    &:hover {
                        background: rgba(244, 67, 54, 0.1);
                        color: #f44336;
                    }
                }
            }
        }

        .recent-uploads {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 12px;

            .recent-title {
                font-size: 12px;
                font-weight: 600;
                color: $text_muted;
                margin-bottom: 8px;
            }

            .recent-list {
                max-height: 120px;
                overflow-y: auto;

                .recent-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 6px 0;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.03);

                    &:last-child {
                        border-bottom: none;
                    }

                    .recent-file-info {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        flex: 1;
                        min-width: 0;

                        .recent-file-name {
                            font-size: 11px;
                            color: $text;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                    }

                    .recent-file-size {
                        font-size: 10px;
                        color: $text_muted;
                        flex-shrink: 0;
                    }
                }
            }
        }
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

// Dark mode styles
.dark {
    .upload-progress-bar {
        background: $dark_mode_background;
        border-color: rgba(255, 255, 255, 0.1);

        .upload-header {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);

            .upload-title {
                color: $dark_mode_text_primary;
            }

            .minimize-btn {
                color: $dark_mode_text_secondary;

                &:hover {
                    background: rgba(255, 255, 255, 0.05);
                    color: $theme;
                }
            }
        }

        .upload-content {
            .current-upload {
                .file-info {
                    .file-name {
                        color: $dark_mode_text_primary;
                    }

                    .upload-stats {
                        color: $dark_mode_text_secondary;

                        .progress-text {
                            color: $theme;
                        }
                    }
                }

                .progress-wrapper {
                    .cancel-btn {
                        color: $dark_mode_text_secondary;

                        &:hover {
                            background: rgba(244, 67, 54, 0.2);
                            color: #f44336;
                        }
                    }
                }
            }

            .recent-uploads {
                border-color: rgba(255, 255, 255, 0.05);

                .recent-title {
                    color: $dark_mode_text_secondary;
                }

                .recent-list {
                    .recent-item {
                        border-color: rgba(255, 255, 255, 0.03);

                        .recent-file-info {
                            .recent-file-name {
                                color: $dark_mode_text_primary;
                            }
                        }

                        .recent-file-size {
                            color: $dark_mode_text_secondary;
                        }
                    }
                }
            }
        }
    }
}

// Responsive design
@media (max-width: 480px) {
    .upload-progress-bar {
        width: calc(100vw - 40px);
        bottom: 10px;
        right: 20px;

        .upload-content {
            padding: 12px;

            .current-upload {
                margin-bottom: 12px;

                .file-info {
                    .file-name {
                        font-size: 12px;
                    }

                    .upload-stats {
                        font-size: 10px;
                        gap: 8px;
                    }
                }
            }

            .recent-uploads {
                .recent-title {
                    font-size: 11px;
                }

                .recent-list {
                    max-height: 80px;

                    .recent-item {
                        .recent-file-info {
                            .recent-file-name {
                                font-size: 10px;
                            }
                        }

                        .recent-file-size {
                            font-size: 9px;
                        }
                    }
                }
            }
        }
    }
}
</style>