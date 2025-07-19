<template>
    <div class="upload-progress-container">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme">
                    <UploadIcon size="20" class="text-white" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t ? $t('upload_progress') : 'Upload Progress' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ uploadSummary }}
                    </p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
                <button 
                    v-if="hasActiveUploads"
                    @click="cancelAllUploads"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-600 dark:hover:bg-red-900/20 transition-colors duration-200"
                >
                    <XIcon size="16" class="mr-1" />
                    {{ $t ? $t('cancel_all') : 'Cancel All' }}
                </button>
                
                <button 
                    v-if="hasCompletedUploads"
                    @click="clearCompleted"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <CheckIcon size="16" class="mr-1" />
                    {{ $t ? $t('clear_completed') : 'Clear Completed' }}
                </button>
            </div>
        </div>
        
        <!-- Overall Progress -->
        <div v-if="showOverallProgress" class="p-4 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t ? $t('overall_progress') : 'Overall Progress' }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ Math.round(overallProgress) }}%
                </span>
            </div>
            <ProgressBar 
                :progress="overallProgress"
                color="bg-theme"
                height="h-2"
                :animated="hasActiveUploads"
                :striped="hasActiveUploads"
                show-percentage
            />
            
            <div v-if="estimatedTimeRemaining" class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span>{{ totalUploadedSize }} / {{ totalFileSize }}</span>
                <span>{{ estimatedTimeRemaining }}</span>
            </div>
        </div>
        
        <!-- File List -->
        <div class="upload-file-list max-h-96 overflow-y-auto">
            <div v-if="files.length === 0" class="p-8 text-center">
                <UploadIcon size="48" class="mx-auto text-gray-400 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t ? $t('no_uploads') : 'No uploads in progress' }}
                </p>
            </div>
            
            <div v-else class="p-4 space-y-3">
                <FileUploadItem
                    v-for="(file, index) in files"
                    :key="file.id || index"
                    :file="file"
                    :progress="file.progress || 0"
                    :status="file.status || 'pending'"
                    :upload-speed="file.uploadSpeed"
                    :time-remaining="file.timeRemaining"
                    @cancel="cancelUpload(file)"
                />
            </div>
        </div>
        
        <!-- Footer Stats -->
        <div v-if="files.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium text-green-600 dark:text-green-400">{{ completedCount }}</span> completed
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium text-blue-600 dark:text-blue-400">{{ activeCount }}</span> uploading
                    </span>
                    <span v-if="errorCount > 0" class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium text-red-600 dark:text-red-400">{{ errorCount }}</span> failed
                    </span>
                </div>
                
                <div v-if="averageSpeed" class="text-gray-500 dark:text-gray-400">
                    Avg: {{ averageSpeed }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ProgressBar from './ProgressBar.vue'
import FileUploadItem from './FileUploadItem.vue'
import { UploadIcon, XIcon, CheckIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'

export default {
    name: 'UploadProgress',
    components: {
        ProgressBar,
        FileUploadItem,
        UploadIcon,
        XIcon,
        CheckIcon
    },
    props: {
        files: {
            type: Array,
            default: () => []
        },
        showOverallProgress: {
            type: Boolean,
            default: true
        }
    },
    computed: {
        ...mapGetters([
            'filesInQueueTotal',
            'filesInQueueUploaded',
            'uploadingProgress'
        ]),
        
        completedCount() {
            return this.files.filter(file => file.status === 'completed').length
        },
        
        activeCount() {
            return this.files.filter(file => ['pending', 'uploading'].includes(file.status)).length
        },
        
        errorCount() {
            return this.files.filter(file => file.status === 'error').length
        },
        
        hasActiveUploads() {
            return this.activeCount > 0
        },
        
        hasCompletedUploads() {
            return this.completedCount > 0
        },
        
        overallProgress() {
            if (this.files.length === 0) return 0
            
            const totalProgress = this.files.reduce((sum, file) => {
                if (file.status === 'completed') return sum + 100
                if (file.status === 'uploading') return sum + (file.progress || 0)
                return sum
            }, 0)
            
            return totalProgress / this.files.length
        },
        
        uploadSummary() {
            if (this.files.length === 0) {
                return this.$t ? this.$t('no_files_to_upload') : 'No files to upload'
            }
            
            const total = this.files.length
            const completed = this.completedCount
            const uploading = this.activeCount
            
            if (uploading > 0) {
                return this.$t 
                    ? this.$t('upload_summary_active', { completed, total, uploading })
                    : `${completed} of ${total} files completed, ${uploading} uploading`
            } else if (completed === total) {
                return this.$t 
                    ? this.$t('upload_summary_completed', { total })
                    : `All ${total} files uploaded successfully`
            } else {
                return this.$t 
                    ? this.$t('upload_summary', { completed, total })
                    : `${completed} of ${total} files completed`
            }
        },
        
        totalFileSize() {
            const total = this.files.reduce((sum, file) => sum + (file.size || 0), 0)
            return this.formatBytes(total)
        },
        
        totalUploadedSize() {
            const uploaded = this.files.reduce((sum, file) => {
                if (file.status === 'completed') return sum + (file.size || 0)
                if (file.status === 'uploading') return sum + ((file.size || 0) * (file.progress || 0) / 100)
                return sum
            }, 0)
            return this.formatBytes(uploaded)
        },
        
        estimatedTimeRemaining() {
            if (!this.hasActiveUploads) return null
            
            const activeFiles = this.files.filter(file => file.timeRemaining)
            if (activeFiles.length === 0) return null
            
            // Use the longest estimated time
            const times = activeFiles.map(file => this.parseTimeString(file.timeRemaining)).filter(Boolean)
            if (times.length === 0) return null
            
            const maxTime = Math.max(...times)
            return this.formatTime(maxTime)
        },
        
        averageSpeed() {
            const activeSpeeds = this.files
                .filter(file => file.uploadSpeed && file.status === 'uploading')
                .map(file => this.parseSpeedString(file.uploadSpeed))
                .filter(Boolean)
            
            if (activeSpeeds.length === 0) return null
            
            const avgSpeed = activeSpeeds.reduce((sum, speed) => sum + speed, 0) / activeSpeeds.length
            return this.formatSpeed(avgSpeed)
        }
    },
    methods: {
        cancelUpload(file) {
            this.$emit('cancel-upload', file)
        },
        
        cancelAllUploads() {
            this.$emit('cancel-all-uploads')
        },
        
        clearCompleted() {
            this.$emit('clear-completed')
        },
        
        formatBytes(bytes) {
            if (bytes === 0) return '0 B'
            
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
        },
        
        formatSpeed(bytesPerSecond) {
            return this.formatBytes(bytesPerSecond) + '/s'
        },
        
        formatTime(seconds) {
            if (seconds < 60) return `${Math.round(seconds)}s`
            if (seconds < 3600) return `${Math.round(seconds / 60)}m`
            return `${Math.round(seconds / 3600)}h`
        },
        
        parseSpeedString(speedStr) {
            // Parse strings like "1.5 MB/s" to bytes per second
            const match = speedStr.match(/^(\d+\.?\d*)\s*(B|KB|MB|GB)\/s$/i)
            if (!match) return null
            
            const value = parseFloat(match[1])
            const unit = match[2].toUpperCase()
            
            const multipliers = { B: 1, KB: 1024, MB: 1024 * 1024, GB: 1024 * 1024 * 1024 }
            return value * (multipliers[unit] || 1)
        },
        
        parseTimeString(timeStr) {
            // Parse strings like "2m 30s" to seconds
            const match = timeStr.match(/(\d+)([hms])/g)
            if (!match) return null
            
            let totalSeconds = 0
            match.forEach(part => {
                const value = parseInt(part.slice(0, -1))
                const unit = part.slice(-1)
                
                switch (unit) {
                    case 'h': totalSeconds += value * 3600; break
                    case 'm': totalSeconds += value * 60; break
                    case 's': totalSeconds += value; break
                }
            })
            
            return totalSeconds
        }
    }
}
</script>

<style lang="scss" scoped>
.upload-progress-container {
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    
    .dark & {
        background: #1f2937;
        border-color: #374151;
    }
}

.upload-file-list {
    &::-webkit-scrollbar {
        width: 6px;
    }
    
    &::-webkit-scrollbar-track {
        background: #f1f5f9;
        .dark & {
            background: #374151;
        }
    }
    
    &::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
        
        .dark & {
            background: #6b7280;
        }
        
        &:hover {
            background: #94a3b8;
            
            .dark & {
                background: #9ca3af;
            }
        }
    }
}
</style>