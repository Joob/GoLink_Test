<template>
    <transition name="upload-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress-panel">
            <div class="upload-panel-header">
                <div class="upload-title">
                    <upload-cloud-icon size="16" class="upload-icon" />
                    <span>{{ getTranslation('uploading.title', {}, 'Uploading Files') }}</span>
                </div>
                <button @click="togglePanel" class="toggle-button" :title="isExpanded ? getTranslation('uploading.collapse', {}, 'Collapse') : getTranslation('uploading.expand', {}, 'Expand')">
                    <chevron-up-icon v-if="isExpanded" size="16" />
                    <chevron-down-icon v-else size="16" />
                </button>
            </div>

            <div v-if="isExpanded" class="upload-panel-content">
                <!-- Overall Progress -->
                <div class="overall-progress">
                    <div class="progress-info">
                        <span class="progress-text">
                            {{ getTranslation('uploading.progress', {
                                current: filesInQueueUploaded,
                                total: filesInQueueTotal
                            }, `File ${filesInQueueUploaded} of ${filesInQueueTotal}`) }}
                        </span>
                        <span class="progress-percentage">{{ uploadingProgress }}%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div 
                                class="progress-fill" 
                                :style="{ width: uploadingProgress + '%' }"
                            ></div>
                        </div>
                        <button @click="cancelUpload" class="cancel-button" :title="getTranslation('uploading.cancel', {}, 'Cancel Upload')">
                            <x-icon size="14" />
                        </button>
                    </div>
                </div>

                <!-- Current File Progress -->
                <div v-if="currentFile" class="current-file">
                    <div class="file-info">
                        <file-icon size="14" class="file-icon" />
                        <span class="file-name" :title="currentFile.file.name">
                            {{ truncateFileName(currentFile.file.name) }}
                        </span>
                        <span class="file-size">{{ formatFileSize(currentFile.file.size) }}</span>
                    </div>
                    <div v-if="isProcessingFile" class="processing-indicator">
                        <refresh-cw-icon size="12" class="processing-icon" />
                        <span>{{ getTranslation('uploading.processing_file', {}, 'Processing file...') }}</span>
                    </div>
                </div>

                <!-- Queue List -->
                <div v-if="fileQueue.length > 1" class="queue-list">
                    <div class="queue-header">
                        <span>{{ getTranslation('uploading.queue', {}, 'Queue') }} ({{ fileQueue.length - 1 }} {{ getTranslation('uploading.remaining', {}, 'remaining') }})</span>
                    </div>
                    <div class="queue-items">
                        <div 
                            v-for="(item, index) in queuePreview" 
                            :key="index + 1"
                            class="queue-item"
                        >
                            <file-icon size="12" class="queue-file-icon" />
                            <span class="queue-file-name" :title="item.file.name">
                                {{ truncateFileName(item.file.name) }}
                            </span>
                            <span class="queue-file-size">{{ formatFileSize(item.file.size) }}</span>
                        </div>
                        <div v-if="fileQueue.length > 4" class="more-files">
                            +{{ fileQueue.length - 4 }} {{ getTranslation('uploading.more_files', {}, 'more files') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { UploadCloudIcon, XIcon, FileIcon, RefreshCwIcon, ChevronUpIcon, ChevronDownIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'EnhancedUploadProgress',
    components: {
        UploadCloudIcon,
        XIcon,
        FileIcon,
        RefreshCwIcon,
        ChevronUpIcon,
        ChevronDownIcon,
    },
    data() {
        return {
            isExpanded: true,
        }
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
        ]),
        currentFile() {
            return this.fileQueue.length > 0 ? this.fileQueue[0] : null
        },
        queuePreview() {
            return this.fileQueue.slice(1, 4) // Show next 3 files in queue
        }
    },
    methods: {
        togglePanel() {
            this.isExpanded = !this.isExpanded
        },
        cancelUpload() {
            this.$store.dispatch('cancelAllUploads')
        },
        truncateFileName(fileName) {
            if (fileName.length > 25) {
                const extension = fileName.split('.').pop()
                const name = fileName.slice(0, fileName.lastIndexOf('.'))
                return name.slice(0, 20) + '...' + extension
            }
            return fileName
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
        },
        // Translation helper with fallback
        getTranslation(key, params = {}, fallback = '') {
            try {
                if (this.$t) {
                    return this.$t(key, params);
                }
                return fallback || key.split('.').pop();
            } catch (error) {
                console.warn(`Translation missing for key: ${key}`);
                return fallback || key.split('.').pop();
            }
        }
    },
}
</script>

<style scoped lang="scss">
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

.upload-panel-enter-active,
.upload-panel-leave-active {
    transition: all 0.3s ease;
}

.upload-panel-enter,
.upload-panel-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.upload-progress-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.08);
    z-index: 1000;
    overflow: hidden;

    .upload-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        background: #f8fafc;

        .upload-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            @include font-size(14);

            .upload-icon {
                color: #3b82f6;
            }
        }

        .toggle-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            color: #6b7280;
            transition: all 0.2s ease;

            &:hover {
                background: rgba(0, 0, 0, 0.05);
                color: #374151;
            }
        }
    }

    .upload-panel-content {
        padding: 16px 20px;

        .overall-progress {
            margin-bottom: 16px;

            .progress-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;

                .progress-text {
                    @include font-size(13);
                    color: #6b7280;
                }

                .progress-percentage {
                    @include font-size(13);
                    font-weight: 600;
                    color: #059669;
                }
            }

            .progress-bar-container {
                display: flex;
                align-items: center;
                gap: 12px;

                .progress-bar {
                    flex: 1;
                    height: 6px;
                    background: #e5e7eb;
                    border-radius: 3px;
                    overflow: hidden;

                    .progress-fill {
                        height: 100%;
                        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
                        border-radius: 3px;
                        transition: width 0.3s ease;
                    }
                }

                .cancel-button {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 4px;
                    border-radius: 4px;
                    color: #ef4444;
                    transition: all 0.2s ease;

                    &:hover {
                        background: rgba(239, 68, 68, 0.1);
                    }
                }
            }
        }

        .current-file {
            padding: 12px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
            margin-bottom: 16px;

            .file-info {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 8px;

                .file-icon {
                    color: #3b82f6;
                    flex-shrink: 0;
                }

                .file-name {
                    flex: 1;
                    @include font-size(13);
                    font-weight: 500;
                    color: #1e40af;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .file-size {
                    @include font-size(12);
                    color: #6b7280;
                    flex-shrink: 0;
                }
            }

            .processing-indicator {
                display: flex;
                align-items: center;
                gap: 6px;
                @include font-size(12);
                color: #7c3aed;

                .processing-icon {
                    animation: spin 1s linear infinite;
                }
            }
        }

        .queue-list {
            .queue-header {
                @include font-size(12);
                font-weight: 600;
                color: #6b7280;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .queue-items {
                max-height: 120px;
                overflow-y: auto;

                .queue-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 0;
                    border-bottom: 1px solid #f3f4f6;

                    &:last-child {
                        border-bottom: none;
                    }

                    .queue-file-icon {
                        color: #9ca3af;
                        flex-shrink: 0;
                    }

                    .queue-file-name {
                        flex: 1;
                        @include font-size(12);
                        color: #4b5563;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .queue-file-size {
                        @include font-size(11);
                        color: #9ca3af;
                        flex-shrink: 0;
                    }
                }

                .more-files {
                    @include font-size(11);
                    color: #9ca3af;
                    font-style: italic;
                    text-align: center;
                    padding: 6px 0;
                }
            }
        }
    }
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

// Dark mode
.dark {
    .upload-progress-panel {
        background: $dark_mode_foreground;
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);

        .upload-panel-header {
            background: rgba(255, 255, 255, 0.05);
            border-bottom-color: rgba(255, 255, 255, 0.1);

            .toggle-button {
                color: #9ca3af;

                &:hover {
                    background: rgba(255, 255, 255, 0.1);
                    color: #d1d5db;
                }
            }
        }

        .upload-panel-content {
            .progress-bar-container {
                .progress-bar {
                    background: rgba(255, 255, 255, 0.1);
                }
            }

            .current-file {
                background: rgba(59, 130, 246, 0.1);
                border-left-color: #3b82f6;
            }

            .queue-items {
                .queue-item {
                    border-bottom-color: rgba(255, 255, 255, 0.1);
                }
            }
        }
    }
}

// Responsive design
@media (max-width: 768px) {
    .upload-progress-panel {
        width: calc(100vw - 40px);
        right: 20px;
        left: 20px;
    }
}
</style>