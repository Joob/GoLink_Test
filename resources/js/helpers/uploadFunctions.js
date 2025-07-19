import store from '../store/index'
import router from '../router'
import { events } from '../bus'
import i18n from '../i18n'
import axios from 'axios'

// File size constants
const SMALL_FILE_THRESHOLD = 5 * 1024 * 1024 // 5MB
const CHUNK_SIZE = 1024 * 1024 // 1MB chunks

export const UploadFunctions = {
    /**
     * Determine if file should be uploaded directly or in chunks
     */
    shouldUseChunkedUpload(fileSize) {
        return fileSize > SMALL_FILE_THRESHOLD
    },

    /**
     * Upload small file directly
     */
    async uploadSmallFile(uploadItem, onProgress, onComplete, onError) {
        const formData = new FormData()
        formData.append('file', uploadItem.file)
        formData.append('name', uploadItem.file.name)
        formData.append('extension', uploadItem.file.name.split('.').pop())
        
        if (uploadItem.parent_id) {
            formData.append('parent_id', uploadItem.parent_id)
        }
        
        if (uploadItem.path && uploadItem.path !== '/') {
            formData.append('path', uploadItem.path)
        }

        // Get upload route
        const route = this.getUploadRoute()
        
        try {
            const response = await axios.post(route, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    onProgress(progress, progressEvent.loaded, progressEvent.total)
                },
            })
            
            onComplete(response.data)
        } catch (error) {
            onError(error)
        }
    },

    /**
     * Upload large file in chunks with retry capability
     */
    async uploadLargeFile(uploadItem, onProgress, onComplete, onError) {
        const file = uploadItem.file
        const chunkSize = CHUNK_SIZE
        const totalChunks = Math.ceil(file.size / chunkSize)
        let uploadedBytes = 0
        let uploadedChunks = 0
        
        // Generate unique source name for chunk assembly
        const sourceName = this.generateChunkSourceName(file.name)
        
        // Initialize upload session
        const sessionId = await this.initializeChunkedUpload(uploadItem)
        
        try {
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * chunkSize
                const end = Math.min(start + chunkSize, file.size)
                const chunk = file.slice(start, end)
                const isLastChunk = chunkIndex === totalChunks - 1
                
                // Upload chunk with retry logic
                await this.uploadChunkWithRetry({
                    chunk,
                    chunkIndex,
                    isLastChunk,
                    sourceName,
                    sessionId,
                    uploadItem,
                    onChunkProgress: (chunkProgress) => {
                        const chunkBytes = (chunkProgress / 100) * chunk.size
                        const totalProgress = Math.round(((uploadedBytes + chunkBytes) / file.size) * 100)
                        onProgress(totalProgress, uploadedBytes + chunkBytes, file.size)
                    }
                })
                
                uploadedBytes += chunk.size
                uploadedChunks++
                
                // Update overall progress
                const progress = Math.round((uploadedBytes / file.size) * 100)
                onProgress(progress, uploadedBytes, file.size)
            }
            
            // Finalize upload
            const finalResponse = await this.finalizeChunkedUpload(sessionId, uploadItem)
            onComplete(finalResponse)
            
        } catch (error) {
            // Cleanup failed upload
            await this.cleanupFailedUpload(sessionId)
            onError(error)
        }
    },

    /**
     * Upload single chunk with retry logic
     */
    async uploadChunkWithRetry(options, maxRetries = 3) {
        const { chunk, chunkIndex, isLastChunk, sourceName, sessionId, uploadItem, onChunkProgress } = options
        let retryCount = 0
        
        while (retryCount < maxRetries) {
            try {
                await this.uploadSingleChunk({
                    chunk,
                    chunkIndex,
                    isLastChunk,
                    sourceName,
                    sessionId,
                    uploadItem,
                    onChunkProgress
                })
                return // Success
            } catch (error) {
                retryCount++
                if (retryCount >= maxRetries) {
                    throw error
                }
                
                // Exponential backoff
                await this.delay(Math.pow(2, retryCount) * 1000)
            }
        }
    },

    /**
     * Upload single chunk
     */
    async uploadSingleChunk(options) {
        const { chunk, chunkIndex, isLastChunk, sourceName, sessionId, uploadItem, onChunkProgress } = options
        
        const formData = new FormData()
        formData.append('chunk', chunk, sourceName)
        formData.append('name', uploadItem.file.name)
        formData.append('extension', uploadItem.file.name.split('.').pop())
        formData.append('is_last_chunk', isLastChunk ? '1' : '0')
        formData.append('chunk_index', chunkIndex.toString())
        formData.append('session_id', sessionId)
        
        if (uploadItem.parent_id) {
            formData.append('parent_id', uploadItem.parent_id)
        }
        
        if (uploadItem.path && uploadItem.path !== '/') {
            formData.append('path', uploadItem.path)
        }

        const route = this.getChunkedUploadRoute()
        
        return axios.post(route, formData, {
            headers: {
                'Content-Type': 'application/octet-stream',
            },
            onUploadProgress: (progressEvent) => {
                const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                onChunkProgress(progress)
            },
        })
    },

    /**
     * Initialize chunked upload session
     */
    async initializeChunkedUpload(uploadItem) {
        const route = this.getChunkedUploadInitRoute()
        
        const response = await axios.post(route, {
            filename: uploadItem.file.name,
            filesize: uploadItem.file.size,
            mimetype: uploadItem.file.type,
            parent_id: uploadItem.parent_id,
            path: uploadItem.path
        })
        
        return response.data.session_id
    },

    /**
     * Finalize chunked upload
     */
    async finalizeChunkedUpload(sessionId, uploadItem) {
        const route = this.getChunkedUploadFinalizeRoute()
        
        const response = await axios.post(route, {
            session_id: sessionId,
            filename: uploadItem.file.name,
            parent_id: uploadItem.parent_id,
            path: uploadItem.path
        })
        
        return response.data
    },

    /**
     * Cleanup failed upload
     */
    async cleanupFailedUpload(sessionId) {
        try {
            const route = this.getChunkedUploadCleanupRoute()
            await axios.delete(route, { data: { session_id: sessionId } })
        } catch (error) {
            // Silently handle cleanup errors
            console.warn('Failed to cleanup upload session:', error)
        }
    },

    /**
     * Cancel ongoing upload
     */
    async cancelUpload(sessionId) {
        try {
            const route = this.getChunkedUploadCancelRoute()
            await axios.post(route, { session_id: sessionId })
            
            // Cleanup session
            await this.cleanupFailedUpload(sessionId)
        } catch (error) {
            console.warn('Failed to cancel upload:', error)
        }
    },

    /**
     * Generate unique chunk source name
     */
    generateChunkSourceName(filename) {
        const strippedSpaces = filename.replace(/\s/g, '-')
        const safeCharacters = strippedSpaces.match(/^[A-Za-z0-9._~()'!*:@,;+?-\W]*$/g)
        const randomString = Array(16)
            .fill(0)
            .map((x) => Math.random().toString(36).charAt(2))
            .join('')
        
        return randomString + '-' + safeCharacters + '.part'
    },

    /**
     * Get appropriate upload routes based on current context
     */
    getUploadRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload`,
            Public: `/api/sharing/upload/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload'
    },

    getChunkedUploadRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload/chunks`,
            Public: `/api/sharing/upload/chunks/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload/chunks'
    },

    getChunkedUploadInitRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload/init`,
            Public: `/api/sharing/upload/init/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload/init'
    },

    getChunkedUploadFinalizeRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload/finalize`,
            Public: `/api/sharing/upload/finalize/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload/finalize'
    },

    getChunkedUploadCleanupRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload/cleanup`,
            Public: `/api/sharing/upload/cleanup/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload/cleanup'
    },

    getChunkedUploadCancelRoute() {
        const currentRoute = router.currentRoute;
        const routeMap = {
            RequestUpload: `/api/file-request/${currentRoute.params.token}/upload/cancel`,
            Public: `/api/sharing/upload/cancel/${currentRoute.params.token}`,
        }
        
        return routeMap[currentRoute.name] || '/api/upload/cancel'
    },

    /**
     * Utility function for delays
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms))
    },

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes'
        
        const k = 1024
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },

    /**
     * Estimate upload time remaining
     */
    estimateTimeRemaining(uploadedBytes, totalBytes, startTime) {
        const elapsedTime = Date.now() - startTime
        const uploadSpeed = uploadedBytes / elapsedTime * 1000 // bytes per second
        const remainingBytes = totalBytes - uploadedBytes
        
        if (uploadSpeed === 0) return null
        
        const remainingSeconds = remainingBytes / uploadSpeed
        return this.formatDuration(remainingSeconds)
    },

    /**
     * Format duration in human readable format
     */
    formatDuration(seconds) {
        if (seconds < 60) return `${Math.round(seconds)}s`
        if (seconds < 3600) return `${Math.round(seconds / 60)}m`
        return `${Math.round(seconds / 3600)}h`
    }
}

export default UploadFunctions