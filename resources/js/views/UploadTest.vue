<template>
    <div class="upload-test-container p-6 max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                Upload System Test
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Test the new upload components by selecting files to upload.
            </p>
        </div>

        <!-- File Input Area -->
        <div class="mb-8">
            <div 
                @drop="handleFileDrop"
                @dragover.prevent
                @dragenter.prevent
                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors duration-200"
            >
                <input 
                    ref="fileInput"
                    type="file" 
                    multiple 
                    @change="handleFileSelect"
                    class="hidden"
                >
                <div class="space-y-4">
                    <UploadIcon size="48" class="mx-auto text-gray-400" />
                    <div>
                        <button 
                            @click="$refs.fileInput.click()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-theme hover:bg-theme/90 transition-colors duration-200"
                        >
                            Select Files
                        </button>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            or drag and drop files here
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar Demo -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Progress Bar Demo</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Static Progress: {{ demoProgress }}%
                    </label>
                    <ProgressBar 
                        :progress="demoProgress" 
                        show-percentage
                        animated
                        striped
                    />
                </div>
                <div class="flex space-x-2">
                    <button 
                        @click="demoProgress = Math.max(0, demoProgress - 10)"
                        class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                    >
                        -10%
                    </button>
                    <button 
                        @click="demoProgress = Math.min(100, demoProgress + 10)"
                        class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                    >
                        +10%
                    </button>
                    <button 
                        @click="animateProgress"
                        class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        Animate
                    </button>
                </div>
            </div>
        </div>

        <!-- File Upload Items Demo -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">File Upload Items Demo</h2>
            <div class="space-y-3">
                <FileUploadItem
                    v-for="(file, index) in demoFiles"
                    :key="index"
                    :file="file"
                    :progress="file.progress"
                    :status="file.status"
                    :upload-speed="file.uploadSpeed"
                    :time-remaining="file.timeRemaining"
                    @cancel="removeDemoFile(index)"
                />
                
                <button 
                    @click="addDemoFile"
                    class="w-full px-4 py-2 border border-dashed border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                >
                    Add Demo File
                </button>
            </div>
        </div>

        <!-- Upload Statistics -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upload Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Active Uploads</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeUploads.length }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Upload History</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ uploadHistory.length }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Queue Total</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ filesInQueueTotal }}</div>
                </div>
            </div>
        </div>

        <!-- Upload Progress Component Demo -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upload Progress Component</h2>
            <div class="max-w-2xl">
                <UploadProgress 
                    :files="allUploads"
                    @cancel-upload="handleCancelUpload"
                    @cancel-all-uploads="handleCancelAllUploads"
                    @clear-completed="handleClearCompleted"
                />
            </div>
        </div>
    </div>
</template>

<script>
import ProgressBar from '../components/Upload/ProgressBar.vue'
import FileUploadItem from '../components/Upload/FileUploadItem.vue'
import UploadProgress from '../components/Upload/UploadProgress.vue'
import { UploadIcon } from 'vue-feather-icons'
import { mapGetters, mapActions } from 'vuex'

export default {
    name: 'UploadTest',
    components: {
        ProgressBar,
        FileUploadItem,
        UploadProgress,
        UploadIcon
    },
    data() {
        return {
            demoProgress: 45,
            demoFiles: [
                {
                    name: 'document.pdf',
                    size: 2048576,
                    type: 'application/pdf',
                    progress: 75,
                    status: 'uploading',
                    uploadSpeed: '1.2 MB/s',
                    timeRemaining: '15s'
                },
                {
                    name: 'image.jpg',
                    size: 1024000,
                    type: 'image/jpeg',
                    progress: 100,
                    status: 'completed',
                    uploadSpeed: null,
                    timeRemaining: null
                },
                {
                    name: 'video.mp4',
                    size: 10485760,
                    type: 'video/mp4',
                    progress: 0,
                    status: 'error',
                    uploadSpeed: null,
                    timeRemaining: null
                }
            ]
        }
    },
    computed: {
        ...mapGetters([
            'activeUploads',
            'uploadHistory', 
            'allUploads',
            'filesInQueueTotal'
        ])
    },
    methods: {
        ...mapActions([
            'cancelUpload',
            'cancelAllUploads', 
            'clearCompletedUploads'
        ]),
        
        handleFileSelect(event) {
            const files = Array.from(event.target.files)
            this.processFiles(files)
        },
        
        handleFileDrop(event) {
            event.preventDefault()
            const files = Array.from(event.dataTransfer.files)
            this.processFiles(files)
        },
        
        processFiles(files) {
            files.forEach(file => {
                this.$store.dispatch('pushFileToTheUploadQueue', {
                    file: file,
                    parent_id: this.$store.getters.currentFolder?.data?.id || null
                })
            })
        },
        
        animateProgress() {
            let progress = 0
            const interval = setInterval(() => {
                progress += 2
                this.demoProgress = progress
                
                if (progress >= 100) {
                    clearInterval(interval)
                    setTimeout(() => {
                        this.demoProgress = 0
                    }, 1000)
                }
            }, 50)
        },
        
        addDemoFile() {
            const statuses = ['pending', 'uploading', 'completed', 'error']
            const types = [
                { name: 'document.pdf', type: 'application/pdf', size: 2048576 },
                { name: 'image.png', type: 'image/png', size: 1024000 },
                { name: 'video.mp4', type: 'video/mp4', size: 10485760 },
                { name: 'audio.mp3', type: 'audio/mpeg', size: 5242880 },
                { name: 'archive.zip', type: 'application/zip', size: 3145728 }
            ]
            
            const randomType = types[Math.floor(Math.random() * types.length)]
            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)]
            
            this.demoFiles.push({
                ...randomType,
                progress: randomStatus === 'completed' ? 100 : Math.floor(Math.random() * 100),
                status: randomStatus,
                uploadSpeed: randomStatus === 'uploading' ? `${(Math.random() * 2 + 0.5).toFixed(1)} MB/s` : null,
                timeRemaining: randomStatus === 'uploading' ? `${Math.floor(Math.random() * 60 + 10)}s` : null
            })
        },
        
        removeDemoFile(index) {
            this.demoFiles.splice(index, 1)
        },
        
        handleCancelUpload(file) {
            this.cancelUpload(file.id)
        },
        
        handleCancelAllUploads() {
            this.cancelAllUploads()
        },
        
        handleClearCompleted() {
            this.clearCompletedUploads()
        }
    }
}
</script>

<style lang="scss" scoped>
.upload-test-container {
    min-height: 100vh;
    background: #f9fafb;
    
    .dark & {
        background: #111827;
    }
}
</style>