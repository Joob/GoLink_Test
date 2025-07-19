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
                                {{ $t ? $t('uploading_files') : 'Uploading files' }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ uploadFileCounter }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Progress Indicator -->
                        <div v-if="hasActiveUploads" class="flex items-center space-x-2">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                {{ Math.round(fileBasedProgress) }}%
                            </span>
                            <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-theme rounded-full transition-all duration-300"
                                    :style="{ width: fileBasedProgress + '%' }"
                                ></div>
                            </div>
                        </div>
                        
                        <!-- Status Icon -->
                        <div class="flex-shrink-0">
                            <CheckIcon 
                                v-if="allCompleted" 
                                size="16" 
                                class="text-green-500"
                            />
                            <AlertTriangleIcon 
                                v-else-if="hasErrors" 
                                size="16" 
                                class="text-red-500"
                            />
                            <RefreshCwIcon 
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
                                {{ uploadFileCounter }}
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
                            {{ uploadFileCounter }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ Math.round(fileBasedProgress) }}%
                        </span>
                    </div>
                    <ProgressBar 
                        :progress="fileBasedProgress"
                        color="bg-theme"
                        height="h-1.5"
                        :animated="hasActiveUploads"
                        :striped="hasActiveUploads"
                    />
                </div>
                
                <!-- Upload Status -->
                <div class="px-4 py-6 text-center">
                    <div v-if="hasActiveUploads" class="space-y-2">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-theme/10">
                            <UploadIcon size="24" class="text-theme animate-pulse" />
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ currentUploadMessage }}
                        </p>
                    </div>
                    
                    <div v-else-if="allCompleted" class="space-y-2">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-green-100 dark:bg-green-900/20">
                            <CheckIcon size="24" class="text-green-600 dark:text-green-400" />
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $t ? $t('all_files_uploaded') : 'All files uploaded successfully' }}
                        </p>
                    </div>
                    
                    <div v-else class="space-y-2">
                        <UploadIcon size="32" class="mx-auto text-gray-400" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t ? $t('no_uploads') : 'No uploads' }}
                        </p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div v-if="hasActiveUploads" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center justify-center">
                        <button 
                            @click="cancelAllUploads"
                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium transition-colors duration-200 text-xs"
                        >
                            {{ $t ? $t('cancel_all') : 'Cancel All' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import ProgressBar from './ProgressBar.vue'
import { 
    UploadIcon, 
    XIcon, 
    ChevronUpIcon, 
    ChevronDownIcon,
    CheckIcon,
    AlertTriangleIcon,
    RefreshCwIcon
} from 'vue-feather-icons'
import { mapGetters } from 'vuex'

export default {
    name: 'UploadBox',
    components: {
        ProgressBar,
        UploadIcon,
        XIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        CheckIcon,
        AlertTriangleIcon,
        RefreshCwIcon
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
        
        totalCount() {
            // Use store queue total if available (more accurate for active uploads)
            if (this.filesInQueueTotal > 0) {
                return this.filesInQueueTotal
            }
            
            // Fall back to files array length
            return this.files.length
        },
        
        completedCount() {
            // Use store queue uploaded count if available
            if (this.filesInQueueTotal > 0) {
                return this.filesInQueueUploaded
            }
            
            // Fall back to counting completed files in array
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
            return this.totalCount > 0 && this.completedCount === this.totalCount && !this.hasActiveUploads
        },
        
        fileBasedProgress() {
            if (this.totalCount === 0) return 0
            return (this.completedCount / this.totalCount) * 100
        },
        
        uploadFileCounter() {
            if (this.totalCount === 0) return ''
            return `${this.completedCount} / ${this.totalCount} files`
        },
        
        currentUploadMessage() {
            const remaining = this.totalCount - this.completedCount
            if (remaining === 1) {
                return this.$t ? this.$t('uploading_last_file') : 'Uploading last file...'
            }
            return this.$t 
                ? this.$t('uploading_files_remaining', { count: remaining })
                : `Uploading ${remaining} files...`
        },
        
        showOverallProgress() {
            return this.totalCount > 0
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
                    // Auto close after completion with delay
                    setTimeout(() => {
                        if (this.allCompleted && !this.userManuallyExpanded) {
                            this.userManuallyClosed = true
                        }
                    }, 3000) // Reduced delay from 10 seconds to 3 seconds
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
        
        cancelUpload(file) {
            this.$emit('cancel-upload', file)
        },
        
        cancelAllUploads() {
            this.$emit('cancel-all-uploads')
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