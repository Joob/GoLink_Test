import { debounce, isArray, orderBy } from 'lodash'
import i18n from '../i18n'
import router from '../router'
import store from '../store/index'
import { events } from '../bus'
import axios from 'axios'
import { UploadFunctions } from './uploadFunctions'

const FunctionsHelpers = {
    install(Vue) {
        // Existing helper functions from functionHelpers.js
        Vue.prototype.$updateText = debounce(function (route, name, value, allowEmpty = false) {
            if ((value === '' || value === ' ' || typeof value === 'object') && !allowEmpty) return

            axios
                .post(store.getters.api + route, {
                    name,
                    value,
                    _method: 'patch',
                })
                .catch(() => {
                    events.$emit('alert:open', {
                        title: this.$t('popup_error.title'),
                        message: this.$t('popup_error.message'),
                    })
                })
        }, 150)

        Vue.prototype.$updateInput = debounce(function (route, name, value, allowEmpty = false) {
            if ((value === '' || value === ' ' || typeof value === 'object') && !allowEmpty) return

            axios
                .post(store.getters.api + route, {
                    [name]: value,
                    _method: 'patch',
                })
                .catch(() => {
                    events.$emit('alert:open', {
                        title: this.$t('popup_error.title'),
                        message: this.$t('popup_error.message'),
                    })
                })
        }, 150)

        // Enhanced upload helper functions
        Vue.prototype.$uploadFilesEnhanced = async function (files) {
            // Show alert message when upload is disabled
            if (store.getters.user && !store.getters.user.data.meta.restrictions.canUpload) {
                Vue.prototype.$temporarilyDisabledUpload()
                return
            }

            if (files.length === 0) {
                return
            }

            if (!this.$checkFileMimetype(files) || !this.$checkUploadLimit(files)) {
                return
            }

            // Process each file and determine upload strategy
            for (const file of files) {
                const uploadItem = {
                    parent_id: store.getters.currentFolder ? store.getters.currentFolder.data.id : '',
                    file: file,
                    path: '/' + file.webkitRelativePath,
                }

                // Add to upload queue with enhanced metadata
                store.dispatch('pushFileToTheUploadQueue', {
                    ...uploadItem,
                    uploadStrategy: UploadFunctions.shouldUseChunkedUpload(file.size) ? 'chunked' : 'direct',
                    retryCount: 0,
                    sessionId: null,
                    startTime: null,
                })
            }
        }

        Vue.prototype.$handleEnhancedUploading = async function (uploadItem) {
            // Set start time for progress estimation
            uploadItem.startTime = Date.now()
            
            // Determine upload strategy
            if (uploadItem.uploadStrategy === 'direct') {
                await this.$handleDirectUpload(uploadItem)
            } else {
                await this.$handleChunkedUpload(uploadItem)
            }
        }

        Vue.prototype.$handleDirectUpload = async function (uploadItem) {
            const onProgress = (progress, uploadedBytes, totalBytes) => {
                store.commit('UPLOADING_FILE_PROGRESS', progress)
                
                // Estimate time remaining
                const timeRemaining = UploadFunctions.estimateTimeRemaining(
                    uploadedBytes, 
                    totalBytes, 
                    uploadItem.startTime
                )
                
                store.commit('UPDATE_UPLOAD_STATS', {
                    progress,
                    uploadedBytes,
                    totalBytes,
                    timeRemaining,
                    speed: this.$calculateUploadSpeed(uploadedBytes, uploadItem.startTime)
                })
            }

            const onComplete = (response) => {
                this.$handleUploadComplete(response, uploadItem)
            }

            const onError = (error) => {
                this.$handleUploadError(error, uploadItem)
            }

            try {
                await UploadFunctions.uploadSmallFile(uploadItem, onProgress, onComplete, onError)
            } catch (error) {
                onError(error)
            }
        }

        Vue.prototype.$handleChunkedUpload = async function (uploadItem) {
            const onProgress = (progress, uploadedBytes, totalBytes) => {
                store.commit('UPLOADING_FILE_PROGRESS', progress)
                
                // Calculate detailed progress info
                const timeRemaining = UploadFunctions.estimateTimeRemaining(
                    uploadedBytes, 
                    totalBytes, 
                    uploadItem.startTime
                )
                
                const speed = this.$calculateUploadSpeed(uploadedBytes, uploadItem.startTime)
                
                store.commit('UPDATE_UPLOAD_STATS', {
                    progress,
                    uploadedBytes,
                    totalBytes,
                    uploadedFormatted: UploadFunctions.formatFileSize(uploadedBytes),
                    totalFormatted: UploadFunctions.formatFileSize(totalBytes),
                    timeRemaining,
                    speed: UploadFunctions.formatFileSize(speed) + '/s'
                })

                // Set processing state when approaching completion
                if (progress >= 100) {
                    store.commit('PROCESSING_FILE', true)
                }
            }

            const onComplete = (response) => {
                this.$handleUploadComplete(response, uploadItem)
            }

            const onError = (error) => {
                this.$handleUploadError(error, uploadItem, true)
            }

            try {
                await UploadFunctions.uploadLargeFile(uploadItem, onProgress, onComplete, onError)
            } catch (error) {
                onError(error)
            }
        }

        Vue.prototype.$handleUploadComplete = function (response, uploadItem) {
            store.commit('PROCESSING_FILE', false)
            
            // Proceed if database record was returned
            if (response.data && response.data.id) {
                store.commit('INCREASE_FOLDER_ITEM', response.data.attributes.parent_id)
                store.commit('SHIFT_FROM_FILE_QUEUE')

                // Check if user is in uploading folder, if yes, show new file
                const currentFolder = store.getters.currentFolder
                if (
                    (!currentFolder && !response.data.attributes.parent_id) ||
                    (currentFolder && response.data.attributes.parent_id === currentFolder.data.id)
                ) {
                    store.commit('ADD_NEW_ITEM', { data: response.data })
                }

                // Reset file progress
                store.commit('UPLOADING_FILE_PROGRESS', 0)
                store.commit('INCREASE_FILES_IN_QUEUE_UPLOADED')

                // Continue with next file
                if (store.getters.fileQueue.length) {
                    Vue.prototype.$handleEnhancedUploading(store.getters.fileQueue[0])
                } else {
                    // All files uploaded
                    store.commit('CLEAR_UPLOAD_PROGRESS')
                    
                    events.$emit('toaster', {
                        type: 'success',
                        emoji: '✅',
                        message: i18n.t('uploaded_success'),
                    })
                }
            }
        }

        Vue.prototype.$handleUploadError = function (error, uploadItem, isChunked = false) {
            let title = ''
            let message = ''

            // Handle different error types
            if (error.response && error.response.status) {
                const statusCode = error.response.status

                switch (statusCode) {
                    case 423:
                        title = i18n.t('popup_exceed_limit.title')
                        message = i18n.t('popup_exceed_limit.message')
                        break
                    case 422:
                        title = i18n.t('popup_mimetypes_blacklist.title')
                        message = i18n.t('popup_mimetypes_blacklist.message')
                        break
                    case 413:
                        title = i18n.t('popup_payload_error.title')
                        message = i18n.t('popup_payload_error.message')
                        break
                    case 401:
                        title = i18n.t('popup_exceed_limit.title')
                        message = i18n.t('popup_exceed_limit.message')
                        break
                    default:
                        title = i18n.t('popup_default_error.title')
                        message = i18n.t('popup_default_error.message')
                        break
                }
            }

            // Show error popup if there's a title
            if (title !== '') {
                events.$emit('alert:open', {
                    type: 'error',
                    emoji: '⚠️',
                    title: title,
                    message: message,
                })
            }

            // Handle retry logic for chunked uploads
            if (isChunked && uploadItem.retryCount < 3) {
                uploadItem.retryCount++
                setTimeout(() => {
                    Vue.prototype.$handleEnhancedUploading(uploadItem)
                }, Math.pow(2, uploadItem.retryCount) * 1000) // Exponential backoff
                return
            }

            // Skip to next file
            store.commit('PROCESSING_FILE', false)
            store.commit('SHIFT_FROM_FILE_QUEUE')

            if (store.getters.fileQueue.length) {
                Vue.prototype.$handleEnhancedUploading(store.getters.fileQueue[0])
            } else {
                store.commit('CLEAR_UPLOAD_PROGRESS')
            }
        }

        Vue.prototype.$cancelCurrentUpload = async function () {
            const currentUpload = store.getters.fileQueue[0]
            
            if (currentUpload && currentUpload.sessionId) {
                try {
                    await UploadFunctions.cancelUpload(currentUpload.sessionId)
                } catch (error) {
                    console.warn('Failed to cancel upload:', error)
                }
            }

            // Clear upload queue
            store.commit('CLEAR_UPLOAD_PROGRESS')
            
            events.$emit('toaster', {
                type: 'danger',
                message: i18n.t('uploaded_canceled'),
            })
        }

        Vue.prototype.$calculateUploadSpeed = function (uploadedBytes, startTime) {
            const elapsedTime = (Date.now() - startTime) / 1000 // seconds
            return elapsedTime > 0 ? uploadedBytes / elapsedTime : 0
        }

        Vue.prototype.$pauseUpload = function () {
            store.commit('PAUSE_UPLOAD')
        }

        Vue.prototype.$resumeUpload = function () {
            store.commit('RESUME_UPLOAD')
            
            if (store.getters.fileQueue.length && !store.getters.isUploading) {
                Vue.prototype.$handleEnhancedUploading(store.getters.fileQueue[0])
            }
        }

        // Add upload queue management
        Vue.prototype.$clearUploadQueue = function () {
            store.commit('CLEAR_UPLOAD_PROGRESS')
        }

        Vue.prototype.$removeFromUploadQueue = function (index) {
            store.commit('REMOVE_FROM_FILE_QUEUE', index)
        }

        Vue.prototype.$getUploadQueueStatus = function () {
            return {
                total: store.getters.filesInQueueTotal,
                uploaded: store.getters.filesInQueueUploaded,
                current: store.getters.fileQueue[0],
                queue: store.getters.fileQueue,
                isUploading: store.getters.isUploading,
                isPaused: store.getters.isUploadPaused
            }
        }

        // Enhanced file validation
        Vue.prototype.$validateFileForUpload = function (file) {
            // Check file size
            if (!this.$checkUploadLimit([file])) {
                return false
            }

            // Check mimetype
            if (!this.$checkFileMimetype([file])) {
                return false
            }

            // Check for 0kb files
            if (file.size === 0) {
                events.$emit('toaster', {
                    type: 'danger',
                    message: `${i18n.t('file_0kb.first')} ${file.name} ${i18n.t('file_0kb.second')}`,
                })
                return false
            }

            // Check for invalid file names
            if (file.name === '.DS_Store') {
                return false
            }

            return true
        }

        // Add additional utility functions for upload management
        Vue.prototype.$getOptimalChunkSize = function (fileSize) {
            // Determine optimal chunk size based on file size
            if (fileSize < 50 * 1024 * 1024) return 1 * 1024 * 1024 // 1MB for smaller files
            if (fileSize < 500 * 1024 * 1024) return 5 * 1024 * 1024 // 5MB for medium files
            return 10 * 1024 * 1024 // 10MB for large files
        }

        Vue.prototype.$formatUploadProgress = function (uploadedBytes, totalBytes, speed, timeRemaining) {
            const uploadedFormatted = UploadFunctions.formatFileSize(uploadedBytes)
            const totalFormatted = UploadFunctions.formatFileSize(totalBytes)
            const speedFormatted = speed ? UploadFunctions.formatFileSize(speed) + '/s' : '0 B/s'
            
            let progressText = `${uploadedFormatted} / ${totalFormatted}`
            
            if (speed && speed > 0) {
                progressText += ` • ${speedFormatted}`
            }
            
            if (timeRemaining) {
                progressText += ` • ${timeRemaining} remaining`
            }
            
            return progressText
        }
    }
}

export default FunctionsHelpers