<template>
    <!-- Upload Box - Fixed position at bottom right -->
    <transition name="upload-box">
        <div 
            v-if="showUploadBox"
            class="upload-box-container fixed bottom-6 right-6 z-50"
            :class="{ 'upload-box-expanded': isExpanded }"
        >
            <!-- Collapsed Header -->
            <div 
                v-if="!isExpanded"
                @click="expandBox"
                class="upload-box-header bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:shadow-xl transition-all duration-200 min-w-80"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme">
                            <UploadIcon 
                                size="20" 
                                class="text-white"
                                :class="{ 'animate-pulse': hasActiveUploads }"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ headerTitle }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ headerSubtitle }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Progress Indicator -->
                        <div v-if="hasActiveUploads" class="flex items-center space-x-2">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                {{ Math.round(overallProgress) }}%
                            </span>
                            <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-theme rounded-full transition-all duration-300"
                                    :style="{ width: overallProgress + '%' }"
                                ></div>
                            </div>
                        </div>
                        
                        <!-- Status Icon -->
                        <div class="flex-shrink-0">
                            <CheckCircleIcon 
                                v-if="allCompleted" 
                                size="16" 
                                class="text-green-500"
                            />
                            <AlertCircleIcon 
                                v-else-if="hasErrors" 
                                size="16" 
                                class="text-red-500"
                            />
                            <RotateCwIcon 
                                v-else-if="hasActiveUploads" 
                                size="16" 
                                class="text-blue-500 animate-spin"
                            />
                        </div>
                        
                        <!-- Expand Arrow -->
                        <ChevronUpIcon 
                            size="16" 
                            class="text-gray-400 transform transition-transform duration-200"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Expanded Content -->
            <div 
                v-else
                class="upload-box-expanded-content bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 w-96"
            >
                <!-- Expanded Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme">
                            <UploadIcon 
                                size="20" 
                                class="text-white"
                                :class="{ 'animate-pulse': hasActiveUploads }"
                            />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $t ? $t('uploads') : 'Uploads' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ uploadSummary }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Minimize Button -->
                        <button 
                            @click="collapseBox"
                            class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                            :title="$t ? $t('minimize') : 'Minimize'"
                        >
                            <ChevronDownIcon size="16" class="text-gray-400" />
                        </button>
                        
                        <!-- Close Button -->
                        <button 
                            @click="closeBox"
                            class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                            :title="$t ? $t('close') : 'Close'"
                        >
                            <XIcon size="16" class="text-gray-400" />
                        </button>
                    </div>
                </div>
                
                <!-- Overall Progress -->
                <div v-if="showOverallProgress" class="p-4 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ $t ? $t('overall_progress') : 'Overall Progress' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ Math.round(overallProgress) }}%
                        </span>
                    </div>
                    <ProgressBar 
                        :progress="overallProgress"
                        color="bg-theme"
                        height="h-1.5"
                        :animated="hasActiveUploads"
                        :striped="hasActiveUploads"
                    />
                    
                    <div v-if="currentFileName || estimatedTimeRemaining" class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span v-if="currentFileName" class="truncate flex-1 mr-2">
                            {{ currentFileName }}
                        </span>
                        <span v-if="estimatedTimeRemaining" class="flex-shrink-0">
                            {{ estimatedTimeRemaining }}
                        </span>
                    </div>
                </div>
                
                <!-- Quick File List -->
                <div class="upload-file-list max-h-64 overflow-y-auto">
                    <div v-if="displayFiles.length === 0" class="p-6 text-center">
                        <UploadIcon size="32" class="mx-auto text-gray-400 mb-2" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t ? $t('no_uploads') : 'No uploads' }}
                        </p>
                    </div>
                    
                    <div v-else class="p-3 space-y-2">
                        <FileUploadItem
                            v-for="file in displayFiles"
                            :key="file.id || file.name"
                            :file="file"
                            :progress="file.progress || 0"
                            :status="file.status || 'pending'"
                            :upload-speed="file.uploadSpeed"
                            :time-remaining="file.timeRemaining"
                            :compact="true"
                            @cancel="cancelUpload(file)"
                        />
                        
                        <!-- Show More Button -->
                        <div v-if="hasMoreFiles" class="pt-2">
                            <button 
                                @click="showFullProgress"
                                class="w-full text-xs text-theme hover:text-theme/80 font-medium py-2 transition-colors duration-200"
                            >
                                {{ $t ? $t('show_more_files', { count: additionalFilesCount }) : `Show ${additionalFilesCount} more files` }}
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div v-if="hasActiveUploads || hasCompletedUploads" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex space-x-3">
                            <button 
                                v-if="hasActiveUploads"
                                @click="cancelAllUploads"
                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium transition-colors duration-200"
                            >
                                {{ $t ? $t('cancel_all') : 'Cancel All' }}
                            </button>
                            
                            <button 
                                v-if="hasCompletedUploads"
                                @click="clearCompleted"
                                class="text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium transition-colors duration-200"
                            >
                                {{ $t ? $t('clear_completed') : 'Clear Completed' }}
                            </button>
                        </div>
                        
                        <button 
                            @click="showFullProgress"
                            class="text-theme hover:text-theme/80 font-medium transition-colors duration-200"
                        >
                            {{ $t ? $t('view_details') : 'View Details' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import ProgressBar from './ProgressBar.vue'
import FileUploadItem from './FileUploadItem.vue'
import { 
    UploadIcon, 
    XIcon, 
    ChevronUpIcon, 
    ChevronDownIcon,
    CheckCircleIcon,
    AlertCircleIcon,
    RotateCwIcon
} from 'vue-feather-icons'
import { mapGetters } from 'vuex'

export default {
    name: 'UploadBox',
    components: {
        ProgressBar,
        FileUploadItem,
        UploadIcon,
        XIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        CheckCircleIcon,
        AlertCircleIcon,
        RotateCwIcon
    },
    props: {
        files: {
            type: Array,
            default: () => []
        },
        autoExpand: {
            type: Boolean,
            default: true
        },
        autoCollapse: {
            type: Boolean,
            default: true
        },
        maxDisplayFiles: {
            type: Number,
            default: 3
        }
    },
    data() {
        return {
            isExpanded: false,
            userManuallyExpanded: false,
            userManuallyClosed: false
        }
    },
    computed: {
        ...mapGetters([
            'fileQueue',
            'filesInQueueTotal',
            'filesInQueueUploaded',
            'uploadingProgress'
        ]),
        
        showUploadBox() {
            return !this.userManuallyClosed && (this.files.length > 0 || this.fileQueue.length > 0)
        },
        
        displayFiles() {
            // Show most recent files first, prioritize active uploads
            const sortedFiles = [...this.files].sort((a, b) => {
                // Active uploads first
                const aActive = ['pending', 'uploading'].includes(a.status)
                const bActive = ['pending', 'uploading'].includes(b.status)
                
                if (aActive && !bActive) return -1
                if (!aActive && bActive) return 1
                
                // Then by upload order (newest first)
                return (b.startTime || 0) - (a.startTime || 0)
            })
            
            return sortedFiles.slice(0, this.maxDisplayFiles)
        },
        
        hasMoreFiles() {
            return this.files.length > this.maxDisplayFiles
        },
        
        additionalFilesCount() {
            return this.files.length - this.maxDisplayFiles
        },
        
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
            return this.activeCount > 0 || this.fileQueue.length > 0
        },
        
        hasCompletedUploads() {
            return this.completedCount > 0
        },
        
        hasErrors() {
            return this.errorCount > 0
        },
        
        allCompleted() {
            return this.files.length > 0 && this.completedCount === this.files.length && !this.hasActiveUploads
        },
        
        overallProgress() {
            if (this.filesInQueueTotal > 0) {
                return (this.filesInQueueUploaded / this.filesInQueueTotal) * 100
            }
            
            if (this.files.length === 0) return 0
            
            const totalProgress = this.files.reduce((sum, file) => {
                if (file.status === 'completed') return sum + 100
                if (file.status === 'uploading') return sum + (file.progress || 0)
                return sum
            }, 0)
            
            return totalProgress / this.files.length
        },
        
        headerTitle() {
            if (this.hasActiveUploads) {
                return this.$t ? this.$t('uploading_files') : 'Uploading files...'
            } else if (this.allCompleted) {
                return this.$t ? this.$t('upload_completed') : 'Upload completed'
            } else if (this.hasErrors) {
                return this.$t ? this.$t('upload_errors') : 'Upload errors'
            }
            return this.$t ? this.$t('uploads') : 'Uploads'
        },
        
        headerSubtitle() {
            if (this.hasActiveUploads) {
                const remaining = this.activeCount || this.fileQueue.length
                return this.$t 
                    ? this.$t('files_remaining', { count: remaining })
                    : `${remaining} file${remaining !== 1 ? 's' : ''} remaining`
            } else if (this.allCompleted) {
                return this.$t 
                    ? this.$t('files_uploaded_successfully', { count: this.files.length })
                    : `${this.files.length} file${this.files.length !== 1 ? 's' : ''} uploaded`
            }
            
            return this.$t 
                ? this.$t('upload_status', { completed: this.completedCount, total: this.files.length })
                : `${this.completedCount} of ${this.files.length} completed`
        },
        
        uploadSummary() {
            if (this.hasActiveUploads) {
                return this.$t 
                    ? this.$t('upload_in_progress')
                    : 'Upload in progress'
            } else if (this.allCompleted) {
                return this.$t 
                    ? this.$t('all_uploads_completed')
                    : 'All uploads completed'
            }
            return this.$t 
                ? this.$t('upload_status_summary')
                : 'Upload status'
        },
        
        currentFileName() {
            const currentFile = this.files.find(file => file.status === 'uploading')
            return currentFile ? currentFile.name : null
        },
        
        estimatedTimeRemaining() {
            if (!this.hasActiveUploads) return null
            
            const activeFile = this.files.find(file => file.status === 'uploading' && file.timeRemaining)
            return activeFile ? activeFile.timeRemaining : null
        },
        
        showOverallProgress() {
            return this.files.length > 0
        }
    },
    watch: {
        hasActiveUploads: {
            handler(hasActive) {
                if (hasActive && this.autoExpand && !this.userManuallyExpanded) {
                    this.isExpanded = false // Start collapsed but visible
                } else if (!hasActive && this.autoCollapse && !this.userManuallyExpanded) {
                    // Auto collapse after completion with delay
                    setTimeout(() => {
                        if (!this.hasActiveUploads && !this.userManuallyExpanded) {
                            this.isExpanded = false
                        }
                    }, 3000)
                }
            },
            immediate: true
        },
        
        allCompleted: {
            handler(completed) {
                if (completed) {
                    // Auto close after completion with longer delay
                    setTimeout(() => {
                        if (this.allCompleted && !this.userManuallyExpanded) {
                            this.userManuallyClosed = true
                        }
                    }, 10000)
                }
            }
        }
    },
    methods: {
        expandBox() {
            this.isExpanded = true
            this.userManuallyExpanded = true
        },
        
        collapseBox() {
            this.isExpanded = false
            this.userManuallyExpanded = false
        },
        
        closeBox() {
            this.userManuallyClosed = true
            this.isExpanded = false
        },
        
        showFullProgress() {
            this.$emit('show-full-progress')
        },
        
        cancelUpload(file) {
            this.$emit('cancel-upload', file)
        },
        
        cancelAllUploads() {
            this.$emit('cancel-all-uploads')
        },
        
        clearCompleted() {
            this.$emit('clear-completed')
        },
        
        // Reset state when new uploads start
        resetState() {
            this.userManuallyClosed = false
            this.userManuallyExpanded = false
        }
    },
    mounted() {
        // Listen for new uploads to reset state
        this.$root.$on('upload:started', this.resetState)
    },
    
    beforeDestroy() {
        this.$root.$off('upload:started', this.resetState)
    }
}
</script>

<style lang="scss" scoped>
.upload-box-container {
    max-width: 400px;
    max-height: 80vh;
    transition: all 0.3s ease-in-out;
}

.upload-box-expanded {
    max-height: 80vh;
}

.upload-box-header {
    transition: all 0.2s ease-in-out;
    
    &:hover {
        transform: translateY(-2px);
    }
}

.upload-box-expanded-content {
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.upload-file-list {
    flex: 1;
    overflow-y: auto;
    
    &::-webkit-scrollbar {
        width: 4px;
    }
    
    &::-webkit-scrollbar-track {
        background: #f1f5f9;
        .dark & {
            background: #374151;
        }
    }
    
    &::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
        
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

// Animations
.upload-box-enter-active, .upload-box-leave-active {
    transition: all 0.3s ease-in-out;
}

.upload-box-enter-from, .upload-box-leave-to {
    opacity: 0;
    transform: translateY(100px) scale(0.8);
}

.upload-box-enter-to, .upload-box-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}
</style>