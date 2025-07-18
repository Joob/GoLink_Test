<template>
    <div class="upload-manager">
        <div class="upload-header">
            <h3 class="upload-title">{{ $t('upload_manager') }}</h3>
            <div class="upload-controls">
                <button 
                    @click="clearCompleted" 
                    v-if="hasCompletedUploads"
                    class="control-btn secondary"
                >
                    {{ $t('clear_completed') }}
                </button>
                <button 
                    @click="pauseAll" 
                    v-if="hasActiveUploads && !allPaused"
                    class="control-btn"
                >
                    {{ $t('pause_all') }}
                </button>
                <button 
                    @click="resumeAll" 
                    v-if="hasActiveUploads && allPaused"
                    class="control-btn"
                >
                    {{ $t('resume_all') }}
                </button>
                <button 
                    @click="cancelAll" 
                    v-if="hasActiveUploads"
                    class="control-btn danger"
                >
                    {{ $t('cancel_all') }}
                </button>
            </div>
        </div>

        <!-- Upload Statistics -->
        <UploadStats 
            v-if="showStats"
            :total-files="filesInQueueTotal"
            :completed-files="filesInQueueUploaded"
            :active-uploads="activeUploadsCount"
            :upload-speed="globalUploadSpeed"
            :time-remaining="globalTimeRemaining"
            :total-size="totalUploadSize"
            :uploaded-size="uploadedSize"
        />

        <!-- Upload Queue -->
        <UploadQueue 
            :files="fileQueue"
            :current-upload-index="currentUploadIndex"
            :max-simultaneous="maxSimultaneousUploads"
            @pause-file="pauseFile"
            @resume-file="resumeFile"
            @cancel-file="cancelFile"
            @retry-file="retryFile"
            @move-up="moveFileUp"
            @move-down="moveFileDown"
        />

        <!-- Drag & Drop Overlay -->
        <div 
            v-if="isDragOver" 
            class="drag-overlay"
            @dragover.prevent
            @drop.prevent="handleDrop"
            @dragleave="handleDragLeave"
        >
            <div class="drag-content">
                <folder-upload-icon size="48" />
                <h3>{{ $t('drop_files_to_upload') }}</h3>
                <p>{{ $t('multiple_files_supported') }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { events } from '../../bus'
import UploadStats from './UploadStats.vue'
import UploadQueue from './UploadQueue.vue'
import { FolderUploadIcon } from 'vue-feather-icons'

export default {
    name: 'UploadManager',
    components: {
        UploadStats,
        UploadQueue,
        FolderUploadIcon,
    },
    props: {
        showStats: {
            type: Boolean,
            default: true
        },
        maxSimultaneousUploads: {
            type: Number,
            default: 3
        }
    },
    data() {
        return {
            isDragOver: false,
            dragCounter: 0, // Track drag enter/leave events
        }
    },
    computed: {
        ...mapGetters([
            'fileQueue',
            'filesInQueueTotal',
            'filesInQueueUploaded',
            'uploadingProgress',
            'isProcessingFile',
            'uploadStats',
        ]),
        hasActiveUploads() {
            return this.fileQueue.length > 0
        },
        hasCompletedUploads() {
            return this.filesInQueueUploaded > 0
        },
        allPaused() {
            return this.fileQueue.length > 0 && this.fileQueue.every(file => file.paused)
        },
        activeUploadsCount() {
            return this.fileQueue.filter(file => !file.paused && !file.completed && !file.error).length
        },
        currentUploadIndex() {
            return this.fileQueue.findIndex(file => !file.completed && !file.error)
        },
        globalUploadSpeed() {
            return this.uploadStats?.speed || 0
        },
        globalTimeRemaining() {
            return this.uploadStats?.timeRemaining || 0
        },
        totalUploadSize() {
            return this.fileQueue.reduce((total, file) => total + file.file.size, 0)
        },
        uploadedSize() {
            let uploaded = 0
            this.fileQueue.forEach((file, index) => {
                if (file.completed) {
                    uploaded += file.file.size
                } else if (index === this.currentUploadIndex) {
                    uploaded += (file.file.size * this.uploadingProgress) / 100
                }
            })
            return uploaded
        },
    },
    methods: {
        pauseAll() {
            events.$emit('pause-all-uploads')
        },
        resumeAll() {
            events.$emit('resume-all-uploads')
        },
        cancelAll() {
            if (confirm(this.$t('confirm_cancel_all_uploads'))) {
                events.$emit('cancel-all-uploads')
                this.$store.commit('CLEAR_UPLOAD_PROGRESS')
            }
        },
        clearCompleted() {
            this.$store.commit('CLEAR_COMPLETED_UPLOADS')
        },
        pauseFile(file) {
            events.$emit('pause-file-upload', file)
        },
        resumeFile(file) {
            events.$emit('resume-file-upload', file)
        },
        cancelFile(file) {
            const index = this.fileQueue.indexOf(file)
            this.$store.commit('REMOVE_FILE_FROM_QUEUE', index)
        },
        retryFile(file) {
            events.$emit('retry-file-upload', file)
        },
        moveFileUp(file) {
            const index = this.fileQueue.indexOf(file)
            if (index > 0) {
                this.$store.commit('MOVE_FILE_IN_QUEUE', { from: index, to: index - 1 })
            }
        },
        moveFileDown(file) {
            const index = this.fileQueue.indexOf(file)
            if (index < this.fileQueue.length - 1) {
                this.$store.commit('MOVE_FILE_IN_QUEUE', { from: index, to: index + 1 })
            }
        },
        handleDragEnter(e) {
            e.preventDefault()
            this.dragCounter++
            this.isDragOver = true
        },
        handleDragLeave(e) {
            e.preventDefault()
            this.dragCounter--
            if (this.dragCounter === 0) {
                this.isDragOver = false
            }
        },
        handleDrop(e) {
            e.preventDefault()
            this.isDragOver = false
            this.dragCounter = 0
            
            const files = Array.from(e.dataTransfer.files)
            if (files.length > 0) {
                this.$uploadFiles(files)
            }
        },
        setupDragAndDrop() {
            document.addEventListener('dragenter', this.handleDragEnter)
            document.addEventListener('dragleave', this.handleDragLeave)
            document.addEventListener('dragover', (e) => e.preventDefault())
            document.addEventListener('drop', this.handleDrop)
        },
        removeDragAndDrop() {
            document.removeEventListener('dragenter', this.handleDragEnter)
            document.removeEventListener('dragleave', this.handleDragLeave)
            document.removeEventListener('dragover', (e) => e.preventDefault())
            document.removeEventListener('drop', this.handleDrop)
        },
    },
    mounted() {
        this.setupDragAndDrop()
    },
    beforeDestroy() {
        this.removeDragAndDrop()
    },
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.upload-manager {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;

    .upload-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid $light_background;

        .upload-title {
            margin: 0;
            font-weight: 700;
            @include font-size(18);
        }

        .upload-controls {
            display: flex;
            gap: 8px;

            .control-btn {
                @include font-size(13);
                font-weight: 600;
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;

                &:hover {
                    transform: translateY(-1px);
                }

                &.secondary {
                    background: $light_background;
                    color: $text;

                    &:hover {
                        background: darken($light_background, 5%);
                    }
                }

                &.danger {
                    background: #ef4444;
                    color: white;

                    &:hover {
                        background: darken(#ef4444, 5%);
                    }
                }

                &:not(.secondary):not(.danger) {
                    background: $theme;
                    color: white;

                    &:hover {
                        background: darken($theme, 5%);
                    }
                }
            }
        }
    }

    .drag-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(59, 130, 246, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;

        .drag-content {
            text-align: center;
            color: white;

            h3 {
                margin: 16px 0 8px;
                font-size: 24px;
                font-weight: 700;
            }

            p {
                margin: 0;
                opacity: 0.9;
                font-size: 16px;
            }
        }
    }
}

.dark {
    .upload-manager {
        background: $dark_mode_background;
        color: $dark_mode_text_primary;

        .upload-header {
            border-color: $dark_mode_foreground;
        }

        .control-btn.secondary {
            background: $dark_mode_foreground;
            color: $dark_mode_text_primary;

            &:hover {
                background: lighten($dark_mode_foreground, 5%);
            }
        }
    }
}
</style>