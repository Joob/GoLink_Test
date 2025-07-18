<template>
    <div class="file-upload-item" :class="itemClass">
        <div class="file-info">
            <div class="file-icon">
                <component :is="fileIcon" size="24" />
            </div>
            <div class="file-details">
                <div class="file-name" :title="file.file.name">{{ file.file.name }}</div>
                <div class="file-meta">
                    <span class="file-size">{{ formatFileSize(file.file.size) }}</span>
                    <span class="file-status">{{ statusText }}</span>
                    <span v-if="estimatedTime" class="estimated-time">{{ estimatedTime }}</span>
                </div>
            </div>
        </div>

        <div class="file-progress-section">
            <ProgressBar 
                :progress="progress"
                :state="progressState"
                :animated="isActive && !file.paused"
                :show-details="isActive"
                :speed="uploadSpeed"
                :time-remaining="timeRemaining"
            />
        </div>

        <div class="file-actions">
            <div class="action-buttons">
                <!-- Pause/Resume for active uploads -->
                <button 
                    v-if="isActive && !file.error"
                    @click="togglePause"
                    class="action-btn"
                    :title="file.paused ? $t('resume_upload') : $t('pause_upload')"
                >
                    <play-icon v-if="file.paused" size="14" />
                    <pause-icon v-else size="14" />
                </button>

                <!-- Retry for failed uploads -->
                <button 
                    v-if="file.error"
                    @click="$emit('retry')"
                    class="action-btn retry-btn"
                    :title="$t('retry_upload')"
                >
                    <rotate-cw-icon size="14" />
                </button>

                <!-- Queue management -->
                <button 
                    v-if="canMoveUp && !isCompleted"
                    @click="$emit('move-up')"
                    class="action-btn move-btn"
                    :title="$t('move_up')"
                >
                    <chevron-up-icon size="14" />
                </button>

                <button 
                    v-if="canMoveDown && !isCompleted"
                    @click="$emit('move-down')"
                    class="action-btn move-btn"
                    :title="$t('move_down')"
                >
                    <chevron-down-icon size="14" />
                </button>

                <!-- Cancel/Remove -->
                <button 
                    @click="$emit('cancel')"
                    class="action-btn cancel-btn"
                    :title="isCompleted ? $t('remove_from_list') : $t('cancel_upload')"
                >
                    <x-icon size="14" />
                </button>
            </div>

            <div class="status-indicator">
                <div class="status-dot" :class="statusClass"></div>
            </div>
        </div>
    </div>
</template>

<script>
import ProgressBar from '../UI/Others/ProgressBar.vue'
import { 
    PlayIcon, 
    PauseIcon, 
    XIcon, 
    RotateCwIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    FileIcon,
    FileTextIcon,
    ImageIcon,
    VideoIcon,
    MusicIcon,
    ArchiveIcon,
} from 'vue-feather-icons'

export default {
    name: 'FileUploadItem',
    components: {
        ProgressBar,
        PlayIcon,
        PauseIcon,
        XIcon,
        RotateCwIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        FileIcon,
        FileTextIcon,
        ImageIcon,
        VideoIcon,
        MusicIcon,
        ArchiveIcon,
    },
    props: {
        file: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            required: true
        },
        isCurrent: {
            type: Boolean,
            default: false
        },
        isActive: {
            type: Boolean,
            default: false
        },
        isCompleted: {
            type: Boolean,
            default: false
        },
        progress: {
            type: Number,
            default: 0
        },
        canMoveUp: {
            type: Boolean,
            default: false
        },
        canMoveDown: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            uploadSpeed: null,
            timeRemaining: null,
            startTime: null,
            lastProgressUpdate: null,
        }
    },
    computed: {
        itemClass() {
            return {
                'is-current': this.isCurrent,
                'is-active': this.isActive,
                'is-completed': this.isCompleted,
                'is-paused': this.file.paused,
                'is-error': this.file.error,
                'is-waiting': !this.isActive && !this.isCompleted && !this.file.error
            }
        },
        fileIcon() {
            const mimeType = this.file.file.type
            const extension = this.file.file.name.split('.').pop()?.toLowerCase()

            if (mimeType.startsWith('image/')) return 'ImageIcon'
            if (mimeType.startsWith('video/')) return 'VideoIcon'
            if (mimeType.startsWith('audio/')) return 'MusicIcon'
            if (mimeType.startsWith('text/') || ['txt', 'md', 'json', 'xml'].includes(extension)) return 'FileTextIcon'
            if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) return 'ArchiveIcon'
            
            return 'FileIcon'
        },
        statusText() {
            if (this.file.error) return this.$t ? this.$t('upload_failed') : 'Failed'
            if (this.isCompleted) return this.$t ? this.$t('completed') : 'Completed'
            if (this.file.paused) return this.$t ? this.$t('paused') : 'Paused'
            if (this.isActive) return this.$t ? this.$t('uploading') : 'Uploading'
            return this.$t ? this.$t('waiting') : 'Waiting'
        },
        statusClass() {
            if (this.file.error) return 'error'
            if (this.isCompleted) return 'success'
            if (this.file.paused) return 'paused'
            if (this.isActive) return 'active'
            return 'waiting'
        },
        progressState() {
            if (this.file.error) return 'error'
            if (this.isCompleted) return 'success'
            if (this.file.paused) return 'paused'
            return 'loading'
        },
        estimatedTime() {
            if (!this.timeRemaining || this.timeRemaining === Infinity) return null
            return this.formatTime(this.timeRemaining)
        }
    },
    methods: {
        togglePause() {
            if (this.file.paused) {
                this.$emit('resume')
            } else {
                this.$emit('pause')
            }
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
        },
        formatTime(seconds) {
            if (seconds < 60) return `${Math.round(seconds)}s`
            if (seconds < 3600) return `${Math.round(seconds / 60)}m`
            return `${Math.round(seconds / 3600)}h`
        },
        updateUploadStats() {
            if (!this.isActive || this.file.paused) return

            const now = Date.now()
            
            if (!this.startTime) {
                this.startTime = now
                this.lastProgressUpdate = now
                return
            }

            if (this.lastProgressUpdate && (now - this.lastProgressUpdate) > 1000) {
                const timeDiff = (now - this.lastProgressUpdate) / 1000
                const progressDiff = this.progress - (this.lastProgress || 0)
                
                if (progressDiff > 0) {
                    const bytesUploaded = (this.file.file.size * progressDiff) / 100
                    this.uploadSpeed = bytesUploaded / timeDiff
                    
                    const remainingBytes = this.file.file.size * (100 - this.progress) / 100
                    this.timeRemaining = remainingBytes / this.uploadSpeed
                }
                
                this.lastProgress = this.progress
                this.lastProgressUpdate = now
            }
        }
    },
    watch: {
        progress: {
            handler: 'updateUploadStats',
            immediate: true
        },
        isActive: {
            handler(newVal) {
                if (newVal) {
                    this.startTime = Date.now()
                    this.lastProgressUpdate = Date.now()
                } else {
                    this.uploadSpeed = null
                    this.timeRemaining = null
                }
            }
        }
    }
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.file-upload-item {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    transition: all 0.2s ease;
    position: relative;

    &:hover {
        background: rgba(0, 0, 0, 0.02);
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;

        .file-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: $light_background;
            color: $text;
        }

        .file-details {
            flex: 1;
            min-width: 0;

            .file-name {
                font-weight: 600;
                @include font-size(14);
                color: $text;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                margin-bottom: 4px;
            }

            .file-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                @include font-size(12);
                color: $text;
                opacity: 0.7;

                .file-size {
                    font-weight: 500;
                }

                .file-status {
                    padding: 2px 6px;
                    border-radius: 4px;
                    background: $light_background;
                    font-weight: 500;
                }

                .estimated-time {
                    color: #3b82f6;
                    font-weight: 500;
                }
            }
        }
    }

    .file-progress-section {
        flex: 0 0 200px;
        margin: 0 16px;

        @media (max-width: 768px) {
            flex: 0 0 120px;
            margin: 0 8px;
        }
    }

    .file-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;

        .action-buttons {
            display: flex;
            gap: 4px;

            .action-btn {
                background: none;
                border: none;
                cursor: pointer;
                padding: 6px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s ease;
                color: $text;

                &:hover {
                    background: rgba(0, 0, 0, 0.1);
                }

                &.retry-btn {
                    color: #3b82f6;

                    &:hover {
                        background: rgba(59, 130, 246, 0.1);
                    }
                }

                &.move-btn {
                    color: #6b7280;

                    &:hover {
                        background: rgba(107, 114, 128, 0.1);
                    }
                }

                &.cancel-btn {
                    color: #ef4444;

                    &:hover {
                        background: rgba(239, 68, 68, 0.1);
                    }
                }
            }
        }

        .status-indicator {
            .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                
                &.waiting {
                    background: #9ca3af;
                }
                
                &.active {
                    background: #3b82f6;
                    animation: pulse 2s infinite;
                }
                
                &.paused {
                    background: #fbbf24;
                }
                
                &.success {
                    background: #10b981;
                }
                
                &.error {
                    background: #ef4444;
                }
            }
        }
    }

    // State-specific styling
    &.is-current {
        background: rgba(59, 130, 246, 0.05);
    }

    &.is-completed {
        .file-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .file-meta .file-status {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
    }

    &.is-error {
        .file-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .file-meta .file-status {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    }

    &.is-paused {
        .file-icon {
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
        }

        .file-meta .file-status {
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
        }
    }

    &.is-waiting {
        opacity: 0.6;
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@media (max-width: 768px) {
    .file-upload-item {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;

        .file-info {
            order: 1;
        }

        .file-progress-section {
            order: 2;
            flex: 1;
            margin: 0;
        }

        .file-actions {
            order: 3;
            justify-content: space-between;
        }
    }
}

.dark {
    .file-upload-item {
        color: $dark_mode_text_primary;

        &:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .file-icon {
            background: $dark_mode_foreground;
            color: $dark_mode_text_primary;
        }

        .file-name {
            color: $dark_mode_text_primary;
        }

        .file-meta {
            color: $dark_mode_text_primary;

            .file-status {
                background: $dark_mode_foreground;
            }
        }

        .action-btn {
            color: $dark_mode_text_primary;

            &:hover {
                background: rgba(255, 255, 255, 0.1);
            }
        }
    }
}
</style>