<template>
    <div class="file-uploader">
        <!-- Upload Area -->
        <div 
            class="upload-area"
            :class="{ 
                'drag-over': isDragOver, 
                'uploading': isUploading,
                'has-files': hasFiles 
            }"
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragenter="handleDragEnter"
            @dragleave="handleDragLeave"
            @click="openFileDialog"
        >
            <!-- Upload Icon and Text -->
            <div v-if="!hasFiles && !isUploading" class="upload-prompt">
                <upload-cloud-icon size="48" class="upload-icon" />
                <h3 class="upload-title">{{ $t('upload.drop_files_here') }}</h3>
                <p class="upload-subtitle">{{ $t('upload.or_click_to_browse') }}</p>
                <p class="upload-info">
                    {{ $t('upload.supports_multiple_files') }}
                    <br>
                    {{ $t('upload.max_file_size', { size: maxFileSizeFormatted }) }}
                </p>
            </div>

            <!-- File List -->
            <div v-if="hasFiles" class="file-list">
                <div 
                    v-for="(file, index) in uploadQueue" 
                    :key="index"
                    class="file-item"
                    :class="{ 
                        'uploading': file.status === 'uploading',
                        'completed': file.status === 'completed',
                        'error': file.status === 'error',
                        'paused': file.status === 'paused'
                    }"
                >
                    <!-- File Info -->
                    <div class="file-info">
                        <div class="file-icon">
                            <component :is="getFileIcon(file.file)" size="24" />
                        </div>
                        <div class="file-details">
                            <div class="file-name">{{ file.file.name }}</div>
                            <div class="file-size">{{ formatFileSize(file.file.size) }}</div>
                            <div v-if="file.status === 'uploading'" class="file-progress-text">
                                {{ formatUploadProgress(file) }}
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="file.status === 'uploading'" class="file-progress">
                        <ProgressBar :progress="file.progress" />
                    </div>

                    <!-- Status Icon -->
                    <div class="file-status">
                        <check-circle-icon 
                            v-if="file.status === 'completed'" 
                            size="20" 
                            class="status-success"
                        />
                        <x-circle-icon 
                            v-if="file.status === 'error'" 
                            size="20" 
                            class="status-error"
                        />
                        <pause-circle-icon 
                            v-if="file.status === 'paused'" 
                            size="20" 
                            class="status-paused"
                        />
                        <loader-icon 
                            v-if="file.status === 'uploading'" 
                            size="20" 
                            class="status-uploading animate-spin"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="file-actions">
                        <button 
                            v-if="file.status === 'uploading'"
                            @click="pauseFile(index)"
                            class="action-button"
                            :title="$t('upload.pause')"
                        >
                            <pause-icon size="16" />
                        </button>
                        <button 
                            v-if="file.status === 'paused'"
                            @click="resumeFile(index)"
                            class="action-button"
                            :title="$t('upload.resume')"
                        >
                            <play-icon size="16" />
                        </button>
                        <button 
                            v-if="file.status === 'error'"
                            @click="retryFile(index)"
                            class="action-button"
                            :title="$t('upload.retry')"
                        >
                            <refresh-cw-icon size="16" />
                        </button>
                        <button 
                            @click="removeFile(index)"
                            class="action-button action-remove"
                            :title="$t('upload.remove')"
                        >
                            <x-icon size="16" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Overall Progress -->
            <div v-if="isUploading && hasFiles" class="overall-progress">
                <div class="overall-stats">
                    <span class="overall-text">
                        {{ $t('uploading.progress', { 
                            current: completedFiles + 1, 
                            total: totalFiles,
                            progress: overallProgress
                        }) }}
                    </span>
                    <span class="overall-percentage">{{ overallProgress }}%</span>
                </div>
                <ProgressBar :progress="overallProgress" />
                <div class="upload-stats">
                    <span>{{ overallUploadSpeed }}</span>
                    <span v-if="estimatedTimeRemaining">{{ estimatedTimeRemaining }}</span>
                </div>
            </div>
        </div>

        <!-- Upload Controls -->
        <div v-if="hasFiles" class="upload-controls">
            <button 
                v-if="!isUploading && hasQueuedFiles"
                @click="startUpload"
                class="control-button primary"
            >
                <upload-icon size="16" />
                {{ $t('upload.start_upload') }}
            </button>
            
            <button 
                v-if="isUploading && !isPaused"
                @click="pauseAll"
                class="control-button"
            >
                <pause-icon size="16" />
                {{ $t('upload.pause_all') }}
            </button>
            
            <button 
                v-if="isPaused"
                @click="resumeAll"
                class="control-button"
            >
                <play-icon size="16" />
                {{ $t('upload.resume_all') }}
            </button>
            
            <button 
                @click="cancelAll"
                class="control-button danger"
            >
                <x-icon size="16" />
                {{ $t('uploaded_canceled') }}
            </button>
            
            <button 
                @click="clearCompleted"
                class="control-button"
                v-if="hasCompletedFiles"
            >
                <trash-2-icon size="16" />
                {{ $t('upload.clear_completed') }}
            </button>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInput"
            type="file"
            multiple
            class="file-input-hidden"
            @change="handleFileSelection"
            :accept="acceptedMimeTypes"
        />
    </div>
</template>

<script>
import ProgressBar from '../UI/Others/ProgressBar'
import { 
    UploadCloudIcon, 
    UploadIcon,
    CheckCircleIcon, 
    XCircleIcon, 
    PauseCircleIcon, 
    LoaderIcon,
    PauseIcon,
    PlayIcon,
    RefreshCwIcon,
    XIcon,
    Trash2Icon,
    FileIcon,
    ImageIcon,
    VideoIcon,
    MusicIcon
} from 'vue-feather-icons'
import { UploadFunctions } from '../../helpers/uploadFunctions'

export default {
    name: 'FileUploader',
    components: {
        ProgressBar,
        UploadCloudIcon,
        UploadIcon,
        CheckCircleIcon,
        XCircleIcon,
        PauseCircleIcon,
        LoaderIcon,
        PauseIcon,
        PlayIcon,
        RefreshCwIcon,
        XIcon,
        Trash2Icon,
        FileIcon,
        ImageIcon,
        VideoIcon,
        MusicIcon
    },
    props: {
        parentId: {
            type: String,
            default: null
        },
        path: {
            type: String,
            default: '/'
        },
        autoStart: {
            type: Boolean,
            default: true
        },
        maxFileSize: {
            type: Number,
            default: null
        },
        acceptedMimeTypes: {
            type: String,
            default: '*/*'
        }
    },
    data() {
        return {
            isDragOver: false,
            uploadQueue: [],
            isUploading: false,
            isPaused: false,
            dragCounter: 0,
            currentUploadIndex: 0
        }
    },
    computed: {
        hasFiles() {
            return this.uploadQueue.length > 0
        },
        hasQueuedFiles() {
            return this.uploadQueue.some(file => file.status === 'queued')
        },
        hasCompletedFiles() {
            return this.uploadQueue.some(file => file.status === 'completed')
        },
        totalFiles() {
            return this.uploadQueue.length
        },
        completedFiles() {
            return this.uploadQueue.filter(file => file.status === 'completed').length
        },
        overallProgress() {
            if (this.totalFiles === 0) return 0
            
            let totalProgress = 0
            this.uploadQueue.forEach(file => {
                if (file.status === 'completed') {
                    totalProgress += 100
                } else if (file.status === 'uploading') {
                    totalProgress += file.progress || 0
                }
            })
            
            return Math.round(totalProgress / this.totalFiles)
        },
        overallUploadSpeed() {
            const currentFile = this.uploadQueue[this.currentUploadIndex]
            if (currentFile && currentFile.speed) {
                return UploadFunctions.formatFileSize(currentFile.speed) + '/s'
            }
            return '0 B/s'
        },
        estimatedTimeRemaining() {
            const currentFile = this.uploadQueue[this.currentUploadIndex]
            if (currentFile && currentFile.timeRemaining) {
                return currentFile.timeRemaining
            }
            return null
        },
        maxFileSizeFormatted() {
            const maxSize = this.maxFileSize || this.$store.getters.config?.uploadLimit || 0
            return UploadFunctions.formatFileSize(maxSize)
        }
    },
    methods: {
        openFileDialog() {
            this.$refs.fileInput.click()
        },
        
        handleFileSelection(event) {
            this.processFiles(Array.from(event.target.files))
            event.target.value = '' // Reset input
        },
        
        handleDrop(event) {
            event.preventDefault()
            this.isDragOver = false
            this.dragCounter = 0
            
            const files = Array.from(event.dataTransfer.files)
            this.processFiles(files)
        },
        
        handleDragOver(event) {
            event.preventDefault()
        },
        
        handleDragEnter(event) {
            event.preventDefault()
            this.dragCounter++
            this.isDragOver = true
        },
        
        handleDragLeave(event) {
            event.preventDefault()
            this.dragCounter--
            if (this.dragCounter === 0) {
                this.isDragOver = false
            }
        },
        
        processFiles(files) {
            for (const file of files) {
                if (this.$validateFileForUpload(file)) {
                    this.addFileToQueue(file)
                }
            }
            
            if (this.autoStart && this.hasQueuedFiles && !this.isUploading) {
                this.startUpload()
            }
        },
        
        addFileToQueue(file) {
            const fileData = {
                file,
                status: 'queued',
                progress: 0,
                uploadedBytes: 0,
                totalBytes: file.size,
                speed: 0,
                timeRemaining: null,
                sessionId: null,
                retryCount: 0,
                uploadStrategy: UploadFunctions.shouldUseChunkedUpload(file.size) ? 'chunked' : 'direct'
            }
            
            this.uploadQueue.push(fileData)
        },
        
        async startUpload() {
            if (this.isUploading) return
            
            this.isUploading = true
            this.isPaused = false
            this.currentUploadIndex = 0
            
            await this.uploadNextFile()
        },
        
        async uploadNextFile() {
            // Find next queued file
            const nextIndex = this.uploadQueue.findIndex(file => file.status === 'queued')
            
            if (nextIndex === -1) {
                // No more files to upload
                this.isUploading = false
                this.onAllUploadsComplete()
                return
            }
            
            this.currentUploadIndex = nextIndex
            const fileData = this.uploadQueue[nextIndex]
            
            fileData.status = 'uploading'
            fileData.startTime = Date.now()
            
            try {
                if (fileData.uploadStrategy === 'direct') {
                    await this.uploadFileDirectly(fileData, nextIndex)
                } else {
                    await this.uploadFileInChunks(fileData, nextIndex)
                }
            } catch (error) {
                this.handleUploadError(nextIndex, error)
            }
        },
        
        async uploadFileDirectly(fileData, index) {
            const uploadItem = {
                file: fileData.file,
                parent_id: this.parentId,
                path: this.path
            }
            
            const onProgress = (progress, uploadedBytes, totalBytes) => {
                this.updateFileProgress(index, progress, uploadedBytes, totalBytes)
            }
            
            const onComplete = (response) => {
                this.handleUploadSuccess(index, response)
                this.uploadNextFile()
            }
            
            const onError = (error) => {
                this.handleUploadError(index, error)
            }
            
            await UploadFunctions.uploadSmallFile(uploadItem, onProgress, onComplete, onError)
        },
        
        async uploadFileInChunks(fileData, index) {
            const uploadItem = {
                file: fileData.file,
                parent_id: this.parentId,
                path: this.path
            }
            
            const onProgress = (progress, uploadedBytes, totalBytes) => {
                this.updateFileProgress(index, progress, uploadedBytes, totalBytes)
            }
            
            const onComplete = (response) => {
                this.handleUploadSuccess(index, response)
                this.uploadNextFile()
            }
            
            const onError = (error) => {
                this.handleUploadError(index, error)
            }
            
            await UploadFunctions.uploadLargeFile(uploadItem, onProgress, onComplete, onError)
        },
        
        updateFileProgress(index, progress, uploadedBytes, totalBytes) {
            const fileData = this.uploadQueue[index]
            fileData.progress = progress
            fileData.uploadedBytes = uploadedBytes
            fileData.totalBytes = totalBytes
            
            // Calculate speed and time remaining
            const elapsedTime = (Date.now() - fileData.startTime) / 1000
            fileData.speed = elapsedTime > 0 ? uploadedBytes / elapsedTime : 0
            fileData.timeRemaining = UploadFunctions.estimateTimeRemaining(
                uploadedBytes, 
                totalBytes, 
                fileData.startTime
            )
            
            this.$forceUpdate()
        },
        
        handleUploadSuccess(index, response) {
            const fileData = this.uploadQueue[index]
            fileData.status = 'completed'
            fileData.progress = 100
            
            this.$emit('file-uploaded', {
                file: fileData.file,
                response,
                index
            })
            
            this.$forceUpdate()
        },
        
        handleUploadError(index, error) {
            const fileData = this.uploadQueue[index]
            
            // Retry logic for recoverable errors
            if (fileData.retryCount < 3 && this.isRetryableError(error)) {
                fileData.retryCount++
                setTimeout(() => {
                    fileData.status = 'queued'
                    this.uploadNextFile()
                }, Math.pow(2, fileData.retryCount) * 1000)
                return
            }
            
            fileData.status = 'error'
            fileData.error = error
            
            this.$emit('file-error', {
                file: fileData.file,
                error,
                index
            })
            
            this.uploadNextFile()
        },
        
        isRetryableError(error) {
            const retryableStatuses = [500, 502, 503, 504, 408]
            return error.response && retryableStatuses.includes(error.response.status)
        },
        
        pauseFile(index) {
            const fileData = this.uploadQueue[index]
            fileData.status = 'paused'
            this.isPaused = true
        },
        
        resumeFile(index) {
            const fileData = this.uploadQueue[index]
            fileData.status = 'queued'
            
            if (!this.isUploading) {
                this.startUpload()
            }
        },
        
        retryFile(index) {
            const fileData = this.uploadQueue[index]
            fileData.status = 'queued'
            fileData.retryCount = 0
            fileData.progress = 0
            fileData.uploadedBytes = 0
            
            if (!this.isUploading) {
                this.startUpload()
            }
        },
        
        removeFile(index) {
            this.uploadQueue.splice(index, 1)
            
            if (this.currentUploadIndex >= index) {
                this.currentUploadIndex--
            }
        },
        
        pauseAll() {
            this.isPaused = true
            this.isUploading = false
            
            this.uploadQueue.forEach(file => {
                if (file.status === 'uploading') {
                    file.status = 'paused'
                }
            })
        },
        
        resumeAll() {
            this.isPaused = false
            
            this.uploadQueue.forEach(file => {
                if (file.status === 'paused') {
                    file.status = 'queued'
                }
            })
            
            this.startUpload()
        },
        
        cancelAll() {
            this.isUploading = false
            this.isPaused = false
            
            this.uploadQueue.forEach(file => {
                if (file.status === 'uploading' && file.sessionId) {
                    UploadFunctions.cancelUpload(file.sessionId)
                }
            })
            
            this.uploadQueue = []
            this.$emit('all-cancelled')
        },
        
        clearCompleted() {
            this.uploadQueue = this.uploadQueue.filter(file => file.status !== 'completed')
        },
        
        onAllUploadsComplete() {
            this.$emit('all-complete', {
                totalFiles: this.totalFiles,
                completedFiles: this.completedFiles
            })
            
            this.$emit('toaster', {
                type: 'success',
                message: this.$t('uploaded_success')
            })
        },
        
        getFileIcon(file) {
            const type = file.type.split('/')[0]
            
            switch (type) {
                case 'image':
                    return 'ImageIcon'
                case 'video':
                    return 'VideoIcon'
                case 'audio':
                    return 'MusicIcon'
                default:
                    return 'FileIcon'
            }
        },
        
        formatFileSize(bytes) {
            return UploadFunctions.formatFileSize(bytes)
        },
        
        formatUploadProgress(fileData) {
            return this.$formatUploadProgress(
                fileData.uploadedBytes,
                fileData.totalBytes,
                fileData.speed,
                fileData.timeRemaining
            )
        }
    }
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.file-uploader {
    width: 100%;
}

.upload-area {
    border: 2px dashed $border_color;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    
    &.drag-over {
        border-color: $theme;
        background: rgba($theme, 0.05);
    }
    
    &.uploading {
        border-style: solid;
        border-color: $theme;
        background: rgba($theme, 0.02);
    }
    
    &.has-files {
        cursor: default;
        text-align: left;
    }
}

.upload-prompt {
    .upload-icon {
        color: $text_muted;
        margin-bottom: 1rem;
    }
    
    .upload-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: $text;
    }
    
    .upload-subtitle {
        color: $text_muted;
        margin-bottom: 1rem;
    }
    
    .upload-info {
        font-size: 0.875rem;
        color: $text_muted;
        line-height: 1.5;
    }
}

.file-list {
    width: 100%;
    max-height: 400px;
    overflow-y: auto;
    
    .file-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 1px solid $border_color;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
        
        &:last-child {
            margin-bottom: 0;
        }
        
        &.uploading {
            border-color: $theme;
            background: rgba($theme, 0.02);
        }
        
        &.completed {
            border-color: $success;
            background: rgba($success, 0.02);
        }
        
        &.error {
            border-color: $danger;
            background: rgba($danger, 0.02);
        }
        
        &.paused {
            border-color: $warning;
            background: rgba($warning, 0.02);
        }
    }
}

.file-info {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
    
    .file-icon {
        margin-right: 0.75rem;
        color: $text_muted;
    }
    
    .file-details {
        flex: 1;
        min-width: 0;
        
        .file-name {
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 0.25rem;
        }
        
        .file-size {
            font-size: 0.875rem;
            color: $text_muted;
        }
        
        .file-progress-text {
            font-size: 0.75rem;
            color: $text_muted;
            margin-top: 0.25rem;
        }
    }
}

.file-progress {
    flex: 0 0 150px;
    margin: 0 1rem;
}

.file-status {
    margin-right: 0.75rem;
    
    .status-success {
        color: $success;
    }
    
    .status-error {
        color: $danger;
    }
    
    .status-paused {
        color: $warning;
    }
    
    .status-uploading {
        color: $theme;
    }
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    
    .action-button {
        padding: 0.5rem;
        border: none;
        border-radius: 4px;
        background: $light_background;
        color: $text_muted;
        cursor: pointer;
        transition: all 0.2s ease;
        
        &:hover {
            background: $border_color;
            color: $text;
        }
        
        &.action-remove:hover {
            background: $danger;
            color: white;
        }
    }
}

.overall-progress {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid $border_color;
    
    .overall-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        
        .overall-text {
            font-weight: 500;
        }
        
        .overall-percentage {
            font-weight: 600;
            color: $theme;
        }
    }
    
    .upload-stats {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
        color: $text_muted;
        margin-top: 0.5rem;
    }
}

.upload-controls {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
    flex-wrap: wrap;
    
    .control-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: 1px solid $border_color;
        border-radius: 6px;
        background: white;
        color: $text;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease;
        
        &:hover {
            border-color: $theme;
            color: $theme;
        }
        
        &.primary {
            background: $theme;
            color: white;
            border-color: $theme;
            
            &:hover {
                background: darken($theme, 10%);
            }
        }
        
        &.danger {
            border-color: $danger;
            color: $danger;
            
            &:hover {
                background: $danger;
                color: white;
            }
        }
    }
}

.file-input-hidden {
    display: none;
}

// Dark mode support
.dark {
    .upload-area {
        border-color: $dark_mode_border;
        background: $dark_mode_background;
        
        &.drag-over {
            background: rgba($theme, 0.1);
        }
        
        &.uploading {
            background: rgba($theme, 0.05);
        }
    }
    
    .file-item {
        border-color: $dark_mode_border;
        background: $dark_mode_foreground;
        
        &.uploading {
            background: rgba($theme, 0.1);
        }
        
        &.completed {
            background: rgba($success, 0.1);
        }
        
        &.error {
            background: rgba($danger, 0.1);
        }
        
        &.paused {
            background: rgba($warning, 0.1);
        }
    }
    
    .action-button {
        background: $dark_mode_background;
        color: $dark_mode_text_muted;
        
        &:hover {
            background: $dark_mode_border;
            color: $dark_mode_text;
        }
    }
    
    .control-button {
        border-color: $dark_mode_border;
        background: $dark_mode_foreground;
        color: $dark_mode_text;
    }
    
    .overall-progress {
        border-color: $dark_mode_border;
    }
}

@media (max-width: 768px) {
    .upload-area {
        padding: 1rem;
        min-height: 150px;
    }
    
    .file-item {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .file-info {
        margin-bottom: 0.5rem;
    }
    
    .file-progress {
        flex: 1;
        margin: 0;
    }
    
    .upload-controls {
        .control-button {
            flex: 1;
            justify-content: center;
        }
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>