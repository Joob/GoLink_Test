<template>
    <div class="file-upload-item" :class="itemClass">
        <!-- File Info Section -->
        <div class="flex items-center space-x-3 flex-1 min-w-0">
            <!-- File Icon -->
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="iconBackgroundClass">
                    <component 
                        :is="fileIcon" 
                        size="20" 
                        class="text-white"
                    />
                </div>
            </div>
            
            <!-- File Details -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ file.name }}
                    </span>
                    <span v-if="status" class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass">
                        {{ statusText }}
                    </span>
                </div>
                
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formattedSize }}
                    </span>
                    <span v-if="uploadSpeed && status === 'uploading'" class="text-xs text-gray-500 dark:text-gray-400">
                        • {{ uploadSpeed }}
                    </span>
                    <span v-if="timeRemaining && status === 'uploading'" class="text-xs text-gray-500 dark:text-gray-400">
                        • {{ timeRemaining }}
                    </span>
                </div>
                
                <!-- Progress Bar -->
                <div v-if="showProgress" class="mt-2">
                    <ProgressBar 
                        :progress="progress"
                        :color="progressBarColor"
                        height="h-1.5"
                        :animated="status === 'uploading'"
                        :striped="status === 'uploading'"
                    />
                </div>
            </div>
        </div>
        
        <!-- Action Button -->
        <div v-if="showCancelButton" class="flex-shrink-0 ml-3">
            <button 
                @click="$emit('cancel')"
                class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                :title="$t('cancel_upload')"
            >
                <XIcon size="16" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
            </button>
        </div>
    </div>
</template>

<script>
import ProgressBar from './ProgressBar.vue'
import { 
    FileIcon, 
    ImageIcon, 
    VideoIcon, 
    MusicIcon, 
    FileTextIcon,
    ArchiveIcon,
    XIcon,
    CheckIcon
} from 'vue-feather-icons'

export default {
    name: 'FileUploadItem',
    components: {
        ProgressBar,
        FileIcon,
        ImageIcon,
        VideoIcon,
        MusicIcon,
        FileTextIcon,
        ArchiveIcon,
        XIcon,
        CheckIcon
    },
    props: {
        file: {
            type: Object,
            required: true
        },
        progress: {
            type: Number,
            default: 0
        },
        status: {
            type: String,
            default: 'pending',
            validator: (value) => ['pending', 'uploading', 'completed', 'error', 'cancelled'].includes(value)
        },
        uploadSpeed: {
            type: String,
            default: null
        },
        timeRemaining: {
            type: String,
            default: null
        },
        compact: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        formattedSize() {
            if (!this.file.size) return '0 B'
            
            const bytes = this.file.size
            const sizes = ['B', 'KB', 'MB', 'GB']
            
            if (bytes === 0) return '0 B'
            
            const i = Math.floor(Math.log(bytes) / Math.log(1024))
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
        },
        
        fileIcon() {
            if (!this.file.type) return 'FileIcon'
            
            const type = this.file.type.toLowerCase()
            
            if (type.startsWith('image/')) return 'ImageIcon'
            if (type.startsWith('video/')) return 'VideoIcon'
            if (type.startsWith('audio/')) return 'MusicIcon'
            if (type.includes('text') || type.includes('document')) return 'FileTextIcon'
            if (type.includes('zip') || type.includes('archive')) return 'ArchiveIcon'
            
            return 'FileIcon'
        },
        
        iconBackgroundClass() {
            const baseClass = 'bg-gradient-to-br'
            
            if (!this.file.type) return `${baseClass} from-gray-400 to-gray-600`
            
            const type = this.file.type.toLowerCase()
            
            if (type.startsWith('image/')) return `${baseClass} from-green-400 to-green-600`
            if (type.startsWith('video/')) return `${baseClass} from-red-400 to-red-600`
            if (type.startsWith('audio/')) return `${baseClass} from-purple-400 to-purple-600`
            if (type.includes('text') || type.includes('document')) return `${baseClass} from-blue-400 to-blue-600`
            if (type.includes('zip') || type.includes('archive')) return `${baseClass} from-yellow-400 to-yellow-600`
            
            return `${baseClass} from-gray-400 to-gray-600`
        },
        
        itemClass() {
            return [
                'flex items-center p-3 rounded-lg transition-all duration-200',
                this.compact ? 'py-2' : 'py-3',
                this.status === 'error' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' :
                this.status === 'completed' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' :
                'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]
        },
        
        statusClass() {
            switch (this.status) {
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                case 'uploading':
                    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                case 'completed':
                    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                case 'error':
                    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                case 'cancelled':
                    return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                default:
                    return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
            }
        },
        
        statusText() {
            switch (this.status) {
                case 'pending':
                    return this.$t ? this.$t('pending') : 'Pending'
                case 'uploading':
                    return this.$t ? this.$t('uploading') : 'Uploading'
                case 'completed':
                    return this.$t ? this.$t('completed') : 'Completed'
                case 'error':
                    return this.$t ? this.$t('error') : 'Error'
                case 'cancelled':
                    return this.$t ? this.$t('cancelled') : 'Cancelled'
                default:
                    return ''
            }
        },
        
        progressBarColor() {
            switch (this.status) {
                case 'uploading':
                    return 'bg-blue-500'
                case 'completed':
                    return 'bg-green-500'
                case 'error':
                    return 'bg-red-500'
                default:
                    return 'bg-theme'
            }
        },
        
        showProgress() {
            return ['uploading', 'completed', 'error'].includes(this.status)
        },
        
        showCancelButton() {
            return ['pending', 'uploading'].includes(this.status)
        }
    }
}
</script>

<style lang="scss" scoped>
.file-upload-item {
    transition: all 0.2s ease-in-out;
    
    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
}
</style>