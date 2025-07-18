<template>
    <transition name="info-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress">
            <!-- Global Progress Header -->
            <div class="progress-header">
                <div class="progress-title">
                    <span v-if="isProcessingFile" class="flex items-center justify-center">
                        <refresh-cw-icon size="12" class="sync-alt text-theme" />
                        {{ $t('uploading.processing_file') }}
                    </span>
                    <span v-else-if="fileQueue.length > 0">
                        {{
                            $t('uploading.progress', {
                                current: filesInQueueUploaded,
                                total: filesInQueueTotal,
                                progress: uploadingProgress,
                            })
                        }}
                    </span>
                </div>
                <div class="progress-actions">
                    <button 
                        v-if="hasActiveUploads" 
                        @click="togglePauseAll" 
                        class="action-btn"
                        :title="allPaused ? $t('resume_all_uploads') : $t('pause_all_uploads')"
                    >
                        <pause-icon v-if="!allPaused" size="14" />
                        <play-icon v-else size="14" />
                    </button>
                    <button @click="cancelUpload" :title="$t('uploading.cancel')" class="action-btn cancel-btn">
                        <x-icon size="16" />
                    </button>
                </div>
            </div>

            <!-- Global Progress Bar -->
            <div class="global-progress">
                <ProgressBar 
                    :progress="globalProgress" 
                    :animated="hasActiveUploads"
                    :state="globalProgressState"
                    :show-details="true"
                    :speed="globalUploadSpeed"
                    :time-remaining="globalTimeRemaining"
                    :progress-text="`${filesInQueueUploaded} / ${filesInQueueTotal} files`"
                />
            </div>

            <!-- Individual File Progress (collapsible) -->
            <div v-if="showIndividualProgress && fileQueue.length > 1" class="individual-files">
                <div class="files-header" @click="toggleFilesView">
                    <span class="files-title">{{ $t('individual_files') }}</span>
                    <chevron-down-icon 
                        size="16" 
                        :class="{ 'rotated': filesViewExpanded }"
                        class="chevron-icon"
                    />
                </div>
                
                <transition name="files-expand">
                    <div v-if="filesViewExpanded" class="files-list">
                        <div 
                            v-for="(file, index) in fileQueue" 
                            :key="file.file.name + index"
                            class="file-item"
                            :class="{ 'is-current': index === 0, 'is-waiting': index > 0 }"
                        >
                            <div class="file-info">
                                <div class="file-name">{{ file.file.name }}</div>
                                <div class="file-size">{{ formatFileSize(file.file.size) }}</div>
                            </div>
                            <div class="file-controls">
                                <button 
                                    v-if="index === 0 && hasActiveUploads"
                                    @click="togglePauseFile(file)" 
                                    class="file-action-btn"
                                    :title="file.paused ? $t('resume_upload') : $t('pause_upload')"
                                >
                                    <pause-icon v-if="!file.paused" size="12" />
                                    <play-icon v-else size="12" />
                                </button>
                                <button 
                                    @click="removeFile(file, index)" 
                                    class="file-action-btn remove-btn"
                                    :title="$t('remove_from_queue')"
                                >
                                    <x-icon size="12" />
                                </button>
                            </div>
                            <div class="file-progress">
                                <ProgressBar 
                                    :progress="getFileProgress(index)"
                                    :state="getFileState(index)"
                                    :animated="index === 0 && hasActiveUploads"
                                />
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </transition>
</template>

<script>
import ProgressBar from './ProgressBar'
import { RefreshCwIcon, XIcon, PauseIcon, PlayIcon, ChevronDownIcon } from 'vue-feather-icons'
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
        ChevronDownIcon,
    },
    data() {
        return {
            showIndividualProgress: true,
            filesViewExpanded: false,
            startTime: null,
            lastProgressUpdate: Date.now(),
            uploadSpeeds: [], // Array to store recent upload speeds for averaging
        }
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
            'uploadStats',
        ]),
        hasActiveUploads() {
            return this.fileQueue.length > 0 && !this.isProcessingFile
        },
        allPaused() {
            return this.fileQueue.length > 0 && this.fileQueue.every(file => file.paused)
        },
        globalProgress() {
            if (this.filesInQueueTotal === 0) return 0
            const baseProgress = (this.filesInQueueUploaded / this.filesInQueueTotal) * 100
            const currentProgress = this.uploadingProgress / this.filesInQueueTotal
            return Math.min(baseProgress + currentProgress, 100)
        },
        globalProgressState() {
            if (this.allPaused) return 'paused'
            if (this.isProcessingFile) return 'loading'
            return 'loading'
        },
        globalUploadSpeed() {
            if (!this.uploadStats || !this.uploadStats.speed) return null
            return this.formatSpeed(this.uploadStats.speed)
        },
        globalTimeRemaining() {
            if (!this.uploadStats || !this.uploadStats.timeRemaining) return null
            return this.formatTime(this.uploadStats.timeRemaining)
        },
    },
    methods: {
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
        togglePauseAll() {
            if (this.allPaused) {
                events.$emit('resume-all-uploads')
            } else {
                events.$emit('pause-all-uploads')
            }
        },
        togglePauseFile(file) {
            events.$emit('toggle-file-upload', file)
        },
        removeFile(file, index) {
            this.$store.commit('REMOVE_FILE_FROM_QUEUE', index)
            events.$emit('toaster', {
                type: 'info',
                message: this.$t('file_removed_from_queue'),
            })
        },
        toggleFilesView() {
            this.filesViewExpanded = !this.filesViewExpanded
        },
        getFileProgress(index) {
            if (index === 0) return this.uploadingProgress
            if (index < this.filesInQueueUploaded) return 100
            return 0
        },
        getFileState(index) {
            if (index === 0) {
                const file = this.fileQueue[index]
                if (file && file.paused) return 'paused'
                if (file && file.error) return 'error'
                return 'loading'
            }
            if (index < this.filesInQueueUploaded) return 'success'
            return 'loading'
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
        },
        formatSpeed(bytesPerSecond) {
            return this.formatFileSize(bytesPerSecond) + '/s'
        },
        formatTime(seconds) {
            if (seconds < 60) return `${Math.round(seconds)}s`
            if (seconds < 3600) return `${Math.round(seconds / 60)}m`
            return `${Math.round(seconds / 3600)}h`
        },
    },
    mounted() {
        this.startTime = Date.now()
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

.files-expand-enter-active,
.files-expand-leave-active {
    transition: all 0.3s ease;
}

.files-expand-enter,
.files-expand-leave-to {
    opacity: 0;
    max-height: 0;
}

.upload-progress {
    width: 100%;
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;

        .progress-title {
            font-weight: 700;
            @include font-size(14);

            span {
                display: flex;
                align-items: center;
            }
        }

        .progress-actions {
            display: flex;
            gap: 8px;

            .action-btn {
                background: none;
                border: none;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s ease;

                &:hover {
                    background: rgba(0, 0, 0, 0.1);
                }

                &.cancel-btn:hover {
                    background: rgba(239, 68, 68, 0.1);
                    color: #ef4444;
                }
            }
        }
    }

    .global-progress {
        margin-bottom: 16px;
    }

    .individual-files {
        .files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 8px 0;
            border-bottom: 1px solid $light_background;
            margin-bottom: 8px;

            .files-title {
                font-weight: 600;
                @include font-size(13);
            }

            .chevron-icon {
                transition: transform 0.3s ease;

                &.rotated {
                    transform: rotate(180deg);
                }
            }
        }

        .files-list {
            max-height: 300px;
            overflow-y: auto;

            .file-item {
                display: flex;
                flex-direction: column;
                padding: 8px 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);

                &:last-child {
                    border-bottom: none;
                }

                &.is-current {
                    background: rgba(59, 130, 246, 0.05);
                    border-radius: 4px;
                    padding: 8px;
                }

                &.is-waiting {
                    opacity: 0.6;
                }

                .file-info {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 4px;

                    .file-name {
                        font-weight: 500;
                        @include font-size(12);
                        flex: 1;
                        margin-right: 8px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .file-size {
                        @include font-size(11);
                        color: $text;
                        opacity: 0.7;
                    }
                }

                .file-controls {
                    display: flex;
                    gap: 4px;
                    align-self: flex-end;
                    margin-bottom: 4px;

                    .file-action-btn {
                        background: none;
                        border: none;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 2px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: background-color 0.2s ease;

                        &:hover {
                            background: rgba(0, 0, 0, 0.1);
                        }

                        &.remove-btn:hover {
                            background: rgba(239, 68, 68, 0.1);
                            color: #ef4444;
                        }
                    }
                }

                .file-progress {
                    margin-top: 4px;
                }
            }
        }
    }
}

.dark {
    .upload-progress {
        background: rgba(31, 41, 55, 0.95);
        color: $dark_mode_text_primary;

        .progress-header,
        .individual-files .files-header {
            border-color: $dark_mode_foreground;
        }

        .action-btn:hover,
        .file-action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .individual-files .files-list .file-item {
            border-color: rgba(255, 255, 255, 0.05);

            &.is-current {
                background: rgba(59, 130, 246, 0.1);
            }
        }
    }
}
</style>
