<template>
    <transition name="upload-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress-container">
            <div class="upload-progress-header">
                <div class="upload-status-icon">
                    <refresh-cw-icon v-if="isProcessingFile" size="16" class="sync-alt text-theme" />
                    <div v-else class="upload-icon">📁</div>
                </div>
                <div class="upload-title">
                    <span v-if="isProcessingFile">{{ $t('uploading.processing_file') }}</span>
                    <span v-else>Upload em progresso</span>
                </div>
                <button @click="cancelUpload" class="cancel-button" :title="$t('uploading.cancel')">
                    <x-icon size="16" />
                </button>
            </div>

            <div class="upload-progress-body">
                <div class="file-info">
                    <div class="file-name" :title="currentFileName">
                        {{ currentFileName }}
                    </div>
                    <div class="file-stats">
                        <span class="progress-percentage">{{ uploadingProgress }}%</span>
                        <span class="remaining-files">{{ remainingFilesText }}</span>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <SimpleProgressBar :progress="uploadingProgress" />
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import SimpleProgressBar from './SimpleProgressBar'
import { RefreshCwIcon, XIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'UploadProgress',
    components: {
        RefreshCwIcon,
        SimpleProgressBar,
        XIcon,
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
            'uploadingFiles',
        ]),
        currentUploadInfo() {
            const currentFile = this.fileQueue[0];
            const uploadingFile = currentFile ? this.uploadingFiles[currentFile.id] : null;
            
            if (uploadingFile) {
                return {
                    fileName: uploadingFile.fileName,
                    fileSize: uploadingFile.fileSize,
                    hasChunks: uploadingFile.totalChunks > 1
                };
            }
            
            return null;
        },
        currentFileName() {
            if (this.fileQueue.length > 0) {
                return this.fileQueue[0].file.name;
            }
            return '';
        },
        remainingFilesText() {
            const total = this.filesInQueueTotal;
            const uploaded = this.filesInQueueUploaded;
            const remaining = total - uploaded;
            
            if (total === 1) {
                return '1 ficheiro';
            } else if (remaining === 1) {
                return `${remaining} ficheiro restante de ${total}`;
            } else {
                return `${remaining} ficheiros restantes de ${total}`;
            }
        }
    },
    methods: {
        cancelUpload() {
            // Cancel all active uploads using enhanced system
            this.$store.commit('CLEAR_ALL_CANCEL_TOKENS');
            this.$store.commit('CLEAR_UPLOAD_PROGRESS');
            
            // Also emit cancel-upload event for compatibility
            events.$emit('cancel-upload')
            
            // Stop any ongoing uploads in the file manager (backward compatibility)
            if (this.$root.cancelTokenSource) {
                this.$root.cancelTokenSource.cancel('Upload cancelled by user')
            }
        },
    },
    mounted() {
        // Store cancel token source in root for access across components
        this.$root.cancelTokenSource = null;
    }
}
</script>

<style scoped lang="scss">
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

// Animation for spinning icon
.sync-alt {
    animation: spin 1s linear infinite;

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

// Upload panel animations
.upload-panel-enter-active,
.upload-panel-leave-active {
    transition: all 0.3s ease;
}

.upload-panel-enter,
.upload-panel-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

// Main container - positioned in bottom right corner
.upload-progress-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 320px;
    background: white;
    border-radius: 12px;
    box-shadow: $light_mode_popup_shadow;
    border: 1px solid $light_mode_border;
    z-index: 9999;
    overflow: hidden;
    font-family: inherit;

    // Dark mode support
    .dark & {
        background: $dark_mode_foreground;
        border-color: $dark_mode_border_color;
        box-shadow: $dark_mode_popup_shadow;
    }
}

// Header section
.upload-progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: $theme_light;
    border-bottom: 1px solid $light_mode_border_darken;

    .dark & {
        background: rgba($theme, 0.1);
        border-bottom-color: $dark_mode_border_color;
    }

    .upload-status-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        flex-shrink: 0;

        .upload-icon {
            font-size: 16px;
        }
    }

    .upload-title {
        flex: 1;
        margin-left: 12px;
        font-weight: 600;
        @include font-size(14);
        color: $text;
        min-width: 0; // Allow text truncation
        
        span {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark & {
            color: $dark_mode_text_primary;
        }
    }

    .cancel-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: $text-muted;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;

        &:hover {
            background: rgba($red, 0.1);
            color: $red;
        }

        .dark & {
            color: $dark_mode_text_secondary;

            &:hover {
                background: rgba($red, 0.2);
                color: $red;
            }
        }
    }
}

// Body section
.upload-progress-body {
    padding: 16px;
}

.file-info {
    margin-bottom: 12px;

    .file-name {
        font-weight: 600;
        @include font-size(14);
        color: $text;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        margin-bottom: 8px;

        .dark & {
            color: $dark_mode_text_primary;
        }
    }

    .file-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;

        .progress-percentage {
            font-weight: 700;
            @include font-size(16);
            color: $theme;
            flex-shrink: 0;
        }

        .remaining-files {
            @include font-size(12);
            color: $text-muted;
            font-weight: 500;
            text-align: right;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

            .dark & {
                color: $dark_mode_text_secondary;
            }
        }
    }
}

.progress-bar-container {
    margin-top: 8px;
}

// Responsive adjustments
@media (max-width: 480px) {
    .upload-progress-container {
        width: 280px;
        right: 10px;
        bottom: 10px;
        font-size: 13px;
    }
    
    .upload-progress-header {
        padding: 10px 12px;
        
        .upload-title {
            @include font-size(13);
        }
        
        .cancel-button {
            width: 24px;
            height: 24px;
        }
    }
    
    .upload-progress-body {
        padding: 12px;
    }
    
    .file-info {
        .file-name {
            @include font-size(13);
            margin-bottom: 6px;
        }
        
        .file-stats {
            .progress-percentage {
                @include font-size(15);
            }
            
            .remaining-files {
                @include font-size(11);
            }
        }
    }
}

@media (max-width: 320px) {
    .upload-progress-container {
        width: calc(100vw - 20px);
        right: 10px;
        left: 10px;
        max-width: 250px;
    }
    
    .upload-progress-header {
        padding: 8px 10px;
        
        .upload-title {
            @include font-size(12);
        }
        
        .cancel-button {
            width: 22px;
            height: 22px;
        }
    }
    
    .upload-progress-body {
        padding: 10px;
    }
    
    .file-info {
        margin-bottom: 10px;
        
        .file-name {
            @include font-size(12);
            margin-bottom: 4px;
        }
        
        .file-stats {
            .progress-percentage {
                @include font-size(14);
            }
            
            .remaining-files {
                @include font-size(10);
            }
        }
    }
}

// Additional mobile landscape fixes
@media (max-width: 767px) and (orientation: landscape) {
    .upload-progress-container {
        bottom: 10px;
        max-height: calc(100vh - 20px);
    }
}

// Very small screens (iPhone SE, etc.)
@media (max-width: 280px) {
    .upload-progress-container {
        width: calc(100vw - 16px);
        right: 8px;
        left: 8px;
    }
    
    .file-info .file-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
}
</style>