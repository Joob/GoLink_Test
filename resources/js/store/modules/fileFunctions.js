import i18n from '../../i18n'
import router from '../../router'
import { events } from '../../bus'
import axios from 'axios'
import Vue from 'vue'

const defaultState = {
    processingPopup: undefined,
    isUploadingFolder: false,
    isProcessingFile: false,
    filesInQueueUploaded: 0,
    filesInQueueTotal: 0,
    uploadingProgress: 0,
    fileQueue: [],
    // Enhanced upload state
    activeUploads: new Map(), // Track active upload requests
    maxSimultaneousUploads: 3,
    uploadStats: {
        speed: 0,
        timeRemaining: 0,
        startTime: null,
    },
    retryAttempts: new Map(), // Track retry attempts per file
    pausedUploads: new Set(), // Track paused uploads
}

const actions = {
    downloadZip: async ({ commit, getters }, item = undefined) => {
        try {
          // Get the IDs and types of the items to download
          const files = []
          if (item) {
            files.push(item.data.id + '|' + 'folder')
          } else {
            getters.clipboard.forEach((file) => {
              const type = file.data.type === 'folder' ? 'folder' : 'file'
              files.push(file.data.id + '|' + type)
            })
          }
      
          // Show the processing popup with the initial message
          commit('PROCESSING_POPUP', {
            title: i18n.t('toaster.please_wait'),
            message: i18n.t('calculate_download_percentage')
          })
      
          // Determine the route to download the zip file
          const route = getters.sharedDetail
            ? `/api/sharing/zip/${router.currentRoute.params.token}?items=${files.join(',')}`
            : `/api/zip?items=${files.join(',')}`
      
          // Download the zip file and update the progress message
          const response = await axios({
            url: route,
            method: 'GET',
            responseType: 'blob',
            onDownloadProgress: (progressEvent) => {
              const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
              commit('PROCESSING_POPUP', {
                title: i18n.t('toaster.please_wait'),
                message: i18n.t('toaster.creating_zip') + ` ( ${percentCompleted}% )`
              })
            }
          })
      
          // Download the zip file
          //Vue.prototype.$downloadFile(response.request.responseURL, 'files.zip')

          // Generate a unique filename for the zip file
            const timestamp = new Date().getTime();
            const filename = `filesGoLink_${timestamp}.zip`;

            // Create a temporary link element to initiate the download
            const downloadLink = document.createElement('a');
            downloadLink.href = window.URL.createObjectURL(new Blob([response.data]));
            downloadLink.setAttribute('download', filename);
            downloadLink.click();

          // Show toaster notification when download finishes
          events.$emit('toaster', {
            type: 'success',
            message: i18n.t('toaster.download_completed'),
          })
      
        } catch (error) {
          // Show an error message if the download fails
          Vue.prototype.$isSomethingWrong()
      
        } finally {
          // Hide the processing popup
          commit('PROCESSING_POPUP', undefined)
        }
    },   
    moveItem: ({ commit, getters, dispatch }, { to_item, item }) => {
        let items = item ? [item] : getters.clipboard;
        let itemsToMove = items.map((data) => {
          return {
            id: data.data.id,
            type: data.data.type,
          }
        });
      
        // Remove file preview
        if (!item) {
          commit('CLIPBOARD_CLEAR')
        }
      
        // Get route
        let route = {
          RequestUpload: `/api/file-request/${router.currentRoute.params.token}/move`,
          Public: `/api/sharing/move/${router.currentRoute.params.token}`,
        }[router.currentRoute.name] || '/api/move'
      
        let moveToId = to_item.data ? to_item.data.id : to_item.id
      
        axios.post(route, {
            to_id: moveToId || undefined,
            items: itemsToMove,
          })
          .then(() => {
            itemsToMove.forEach((item) => {
              events.$emit('toaster', {
                type: 'success',
                message: i18n.t('toaster.file_moved'),
              })
              commit('REMOVE_ITEM', item.id)
              commit('INCREASE_FOLDER_ITEM', moveToId)
      
              if (item.type === 'folder') {
                dispatch('getAppData')
              }
            })
      
            dispatch('getFolderTree');
          })
          .catch(() => Vue.prototype.$isSomethingWrong());
    },          
    createFolder: ({ commit, getters, dispatch }, folder) => {
        // Get route
        let route = {
            RequestUpload: `/api/file-request/${router.currentRoute.params.token}/create-folder`,
            Public: `/api/sharing/create-folder/${router.currentRoute.params.token}`,
        }[router.currentRoute.name] || '/api/create-folder'

        axios
            .post(route, {
                name: folder.name,
                emoji: folder.emoji,
                parent_id: getters.currentFolder?.data.id,
            })
            .then((response) => {
                events.$emit('toaster', {
                    type: 'success',
                    message: i18n.t('toaster.folder_created'),
                })
                commit('ADD_NEW_ITEM', response.data)

                events.$emit('scrollTop')

                // Set focus on new folder name
                setTimeout(() => {
                    if (!Vue.prototype.$isMobile()) {
                        events.$emit('newFolder:focus', response.data.data.id)
                    }
                }, 10)

                // Refresh folder tree navigation
                dispatch('getFolderTree')
            })
            .catch((error) => {
                events.$emit('alert:open', {
                    title: error.response.data.message || i18n.t('popup_error.title'),
                    message: i18n.t('popup_error.message'),
                })
            })

    },
    renameItem: ({ commit, getters, dispatch }, data) => {
        // Updated name in favourites panel
        if (data.type === 'folder' && getters.user)
            commit('UPDATE_NAME_IN_FAVOURITES', data)

        // Get route
        let route = {
            RequestUpload: `/api/file-request/${router.currentRoute.params.token}/rename/${data.id}`,
            Public: `/api/sharing/rename/${data.id}/${router.currentRoute.params.token}`,
        }[router.currentRoute.name] || `/api/rename/${data.id}`

        axios
            .post(route, {
                ...data,
                _method: 'patch',
            })
            .then((response) => {
                commit('CHANGE_ITEM_NAME', response.data)

                // Refresh folder tree navigation
                dispatch('getAppData')

                // Refresh folder tree navigation
                dispatch('getFolderTree')

                // Listen to click and key events to hide toaster
                const hideToaster = () => {
                    events.$off('hide-toaster', hideToaster)
                    events.$off('keydown', hideToaster)
                    events.$emit('hide-toaster')
                }
                events.$on('hide-toaster', hideToaster)
                events.$on('keydown', (event) => {
                    if (event.key === 'Enter') {
                        hideToaster()
                    }
                })

                // Show toaster with delay to ensure it is hidden when clicked or key is pressed
                setTimeout(() => {
                    events.$emit('toaster', {
                        type: 'success',
                        message: i18n.t('toaster.folder_file_renamed'),
                    })
                }, 100)
            })
            .catch(() => Vue.prototype.$isSomethingWrong())

    },
    uploadFiles: ({ commit, getters, dispatch}, { form, fileSize, totalUploadedSize, fileIndex = 0 }) => {
        return new Promise((resolve, reject) => {
            // Get route
            let route = {
                RequestUpload: `/api/file-request/${router.currentRoute.params.token}/upload/chunks`,
                Public: `/api/sharing/upload/chunks/${router.currentRoute.params.token}`,
            }[router.currentRoute.name] || '/api/upload/chunks'

            // Create cancel token for axios cancellation
            const CancelToken = axios.CancelToken,
                source = CancelToken.source()
            
            // Store active upload for cancellation capability
            commit('SET_ACTIVE_UPLOAD', { fileIndex, source })
            
            let completedUploads = 0;
            const startTime = Date.now()

            axios
                .post(route, form, {
                    cancelToken: source.token,
                    headers: {
                        'Content-Type': 'application/octet-stream',
                    },
                    onUploadProgress: (event) => {

                        const completedSize = totalUploadedSize + event.loaded;
                        const fileSizeMB = fileSize / (1024 * 1024); // Convert fileSize to megabytes
                        const completedSizeMB = completedSize / (1024 * 1024); // Convert completedSize to megabytes
                        const progress = Math.floor((completedSize / fileSize) * 100);
                        const progressText = `${completedSizeMB.toFixed(2)}mb / ${fileSizeMB.toFixed(2)}mb`;

                        commit('UPLOADING_FILE_PROGRESS', progress >= 100 ? 100 : progress);

                        // Calculate upload speed and time remaining
                        const elapsed = (Date.now() - startTime) / 1000
                        const speed = event.loaded / elapsed
                        const remaining = (fileSize - (totalUploadedSize + event.loaded)) / speed

                        commit('UPDATE_UPLOAD_STATS', { speed, timeRemaining: remaining })

                        // Set processing file
                        if (progress >= 100) {
                            commit('PROCESSING_FILE', true);
                        }
                    },
                })
                .then(async (response) => {
                    resolve(response)

                    completedUploads++;

                    // Remove from active uploads
                    commit('REMOVE_ACTIVE_UPLOAD', fileIndex)

                    // Check if all files are uploaded
                    if (completedUploads === getters.fileQueue.length) {            
                        events.$emit('toaster', {
                            type: 'success',
                            emoji: '⏳',
                            message: i18n.t('uploaded_success'),
                        });
                    }
                    
                    // Proceed if was returned database record
                    if (response.data.data.id) {

                        commit('PROCESSING_FILE', false)

                        commit('INCREASE_FOLDER_ITEM', response.data.data.attributes.parent_id)

                        // Mark file as completed
                        commit('MARK_FILE_COMPLETED', fileIndex)

                        // Remove first file from file queue
                        commit('SHIFT_FROM_FILE_QUEUE')

                        // Refresh request detail to update currentFolder in Vuex
                        if (router.currentRoute.name === 'RequestUpload' && !getters.currentFolder) {
                            await dispatch('getUploadRequestDetail')
                        }

                        // Check if user is in uploading folder, if yes, than show new file
                        if (
                            (!getters.currentFolder && !response.data.data.attributes.parent_id) ||
                            (getters.currentFolder &&
                                response.data.data.attributes.parent_id === getters.currentFolder.data.id)
                        ) {
                            // Add uploaded item into view
                            commit('ADD_NEW_ITEM', response.data)
                        }

                        // Reset file progress
                        commit('UPLOADING_FILE_PROGRESS', 0)

                        // Increase count in files in queue uploaded for 1
                        commit('INCREASE_FILES_IN_QUEUE_UPLOADED')

                        // Start uploading next files if queue is not empty and we can handle more simultaneous uploads
                        dispatch('processUploadQueue')

                        // Reset upload process
                        if (!getters.fileQueue.length) {
                            commit('CLEAR_UPLOAD_PROGRESS')
                        }

                        // Reload File data after folder uploading is finished
                        if (getters.isUploadingFolder) {
                            commit('START_LOADING_VIEW')

                            // Reload files after folder upload is done
                            Vue.prototype.$getDataByLocation(1)

                            // Reload folder tree
                            dispatch('getFolderTree')

                            commit('UPDATE_UPLOADING_FOLDER_STATE', false)
                        }

                    }
                })
                .catch((error) => {
                    // Remove from active uploads
                    commit('REMOVE_ACTIVE_UPLOAD', fileIndex)
                    
                    // Handle retry logic
                    const currentRetries = getters.retryAttempts.get(fileIndex) || 0
                    const maxRetries = 3
                    
                    if (currentRetries < maxRetries && !axios.isCancel(error)) {
                        // Exponential backoff retry
                        const backoffDelay = Math.pow(2, currentRetries) * 1000
                        commit('INCREMENT_RETRY_ATTEMPT', fileIndex)
                        
                        setTimeout(() => {
                            dispatch('uploadFiles', { form, fileSize, totalUploadedSize, fileIndex })
                        }, backoffDelay)
                        
                        return
                    }
                    
                    try {
                      let title = '';
                      let message = '';
            
                        // Check the error status code and set the title accordingly
                        if (error.response && error.response.status) {
                            const statusCode = error.response.status;
                
                            switch (statusCode) {
                            case 423:
                                title = i18n.t('popup_exceed_limit.title');
                                message = i18n.t('popup_exceed_limit.message');
                                break;
                            case 422:
                                title = i18n.t('popup_mimetypes_blacklist.title');
                                message = i18n.t('popup_mimetypes_blacklist.message');
                                break;
                            case 413:
                                title = i18n.t('popup_payload_error.title');
                                message = i18n.t('popup_payload_error.message');
                                break;
                            case 401:
                                title = i18n.t('popup_exceed_limit.title');
                                message = i18n.t('popup_exceed_limit.message');
                                break;
                            default:
                                title = i18n.t('popup_default_error.title');
                                message = i18n.t('popup_default_error.message');
                                break;
                            }
                        }
                
                        // Display the error pop-up if there is a title
                        if (title !== '') {
                            events.$emit('alert:open', {
                                type: 'error',
                                emoji: '⚠️',
                                title: `${title}`,
                                message: `${message}`,
                            });
                        }

                        // Mark file as failed
                        commit('MARK_FILE_FAILED', fileIndex)

                        // Skip processing the current file with an error and move to the next file
                        commit('SHIFT_FROM_FILE_QUEUE');

                        // Process next uploads in queue
                        dispatch('processUploadQueue')
            
                    } catch (error) {
                      console.error(error);
                    }
                })
        })
    },
    restoreItem: ({ commit, getters }, item) => {
        let items = item
            ? [item]
            : getters.clipboard

        let restoreToHome = Vue.prototype.$isThisRoute(router.currentRoute, ['Trash'])

        let itemToRestore = items.map((data) => {
            return {
                type: data.data.type,
                id: data.data.id,
            }
        })

        // Remove file preview
        commit('CLIPBOARD_CLEAR')

        axios
            .post(getters.api + '/trash/restore', {
                to_home: restoreToHome,
                items: itemToRestore,
            })
            .then(() => items.forEach((item) => commit('REMOVE_ITEM', item.data.id)))
            .catch(() => Vue.prototype.$isSomethingWrong())

        events.$emit('toaster', {
            type: 'success',
            message: i18n.t('toaster.item_restored'),
        })
    },
    deleteItem: ({ commit, getters, dispatch }, item) => {

        // Initialize variable to keep track of toaster message
        let toasterShown = false;

        let items = item ? [item] : getters.clipboard;
        let numItemsToDelete = items.length; // Count number of items to delete

        let deletedItems = items.map((data) => {
            // Remove file from view
            commit('REMOVE_ITEM', data.data.id)
            commit('REMOVE_ITEM_FROM_CLIPBOARD', data.data.id)
            events.$emit('file:deleted', data.data.id)

            // Remove item from sidebar
            if (! ['Public', 'RequestUpload'].includes(router.currentRoute.name) && data.data.type === 'folder')
                commit('REMOVE_ITEM_FROM_FAVOURITES', data)

            return {
                force_delete: !!data.data.attributes.deleted_at,
                type: data.data.type,
                id: data.data.id,
            }
        })

            // Get route
            let route = {
                RequestUpload: `/api/file-request/${router.currentRoute.params.token}/remove`,
                Public: `/api/sharing/remove/${router.currentRoute.params.token}`,
            }[router.currentRoute.name] || '/api/remove'

                axios
                    .post(route, {
                        items: deletedItems,
                    })
                    .then(() => {

                        deletedItems.forEach((data) => {
                            if (
                              data.type === 'folder' &&
                              getters.currentFolder &&
                              data.id === getters.currentFolder.data.id
                            ) {
                              router.back();
                              events.$emit('folder:deleted', data.id); // Emit event for deleted folder
                            } else {
                              events.$emit('file:deleted', data.id); // Emit event for deleted file
                            }
                    
                            // Decrement numItemsToDelete
                            numItemsToDelete--;
                    
                            // If all items have been deleted, show toaster message only once
                            if (numItemsToDelete === 0 && !toasterShown) {
                              toasterShown = true;
                              events.$emit('toaster', {
                                type: 'danger',
                                message: i18n.t('toaster.files_deleted', {
                                  count: deletedItems.length,
                                }),
                              });
                            }
                        });

                        // Refresh folder tree navigation
                        dispatch('getFolderTree');
                        events.$emit('item:deleted'); // Emit event for all deleted items
        
                    })
                    .catch(() => Vue.prototype.$isSomethingWrong())

    },
    emptyTrash: ({ commit, getters }) => {

        // Clear file browser
        commit('START_LOADING_VIEW')

        axios
            .post(getters.api + '/trash/dump', {
                _method: 'delete',
            })
            .then(() => {
                events.$emit('toaster', {
                    type: 'danger',
                    message: i18n.t('toaster.permanently_removed_files'),
                })
                commit('STOP_LOADING_VIEW')
                events.$emit('scrollTop')

                commit('CLIPBOARD_CLEAR')
            })
            .catch(() => Vue.prototype.$isSomethingWrong())
            .finally(() => {
				commit('PROCESSING_POPUP', undefined)
			})

    },
    emptyTrashQuietly: ({ commit, getters }) => {
        axios
            .post(getters.api + '/trash/dump', {
                _method: 'delete',
            })
            .then(() => {
                if (router.currentRoute.name === 'Trash') {
                    commit('STOP_LOADING_VIEW')
                }
            })
            .catch(() => Vue.prototype.$isSomethingWrong())

        events.$emit('toaster', {
            type: 'success',
            message: i18n.t('toaster.successfully_cleared'),
        })
    },
    pushFileToTheUploadQueue: ({commit, getters, dispatch}, item) => {
        // Prevent to upload file with 0kb file size
        if (item.file.size === 0) {
            events.$emit('toaster', {
                type: 'danger',
                message: `${i18n.t('file_0kb.first')} ${item.file.name} ${i18n.t('file_0kb.second')}`,
            });
            return
        }

        if (item.file.size !== 0 && item.file.name !== '.DS_Store') {
            // Enhance file object with upload state
            const enhancedItem = {
                ...item,
                id: `${item.file.name}_${Date.now()}`,
                paused: false,
                completed: false,
                error: null,
                progress: 0,
                speed: 0,
                timeRemaining: null,
            }

            // commit file to the upload queue
            commit('ADD_FILES_TO_QUEUE', enhancedItem)

            // Start uploading if uploading process isn't running
            if (getters.filesInQueueTotal === 0) {
                dispatch('processUploadQueue')
            }

            // Increase total files in upload bar
            commit('INCREASE_FILES_IN_QUEUES_TOTAL')
        }
    },
    processUploadQueue: ({ commit, getters, dispatch }) => {
        const { fileQueue, activeUploads, maxSimultaneousUploads, pausedUploads } = getters
        
        // Find files that can be uploaded
        const availableSlots = maxSimultaneousUploads - activeUploads.size
        const pendingFiles = fileQueue.filter((file, index) => 
            !file.completed && 
            !file.error && 
            !pausedUploads.has(file.id) &&
            !activeUploads.has(index)
        )

        // Start uploads for available slots
        for (let i = 0; i < Math.min(availableSlots, pendingFiles.length); i++) {
            const file = pendingFiles[i]
            const fileIndex = fileQueue.indexOf(file)
            
            if (fileIndex !== -1) {
                Vue.prototype.$handleUploading(file, fileIndex)
            }
        }
    },
    pauseFileUpload: ({ commit }, fileId) => {
        commit('PAUSE_UPLOAD', fileId)
        events.$emit('toaster', {
            type: 'info',
            message: i18n.t('upload_paused'),
        })
    },
    resumeFileUpload: ({ commit, dispatch }, fileId) => {
        commit('RESUME_UPLOAD', fileId)
        dispatch('processUploadQueue')
        events.$emit('toaster', {
            type: 'info', 
            message: i18n.t('upload_resumed'),
        })
    },
    pauseAllUploads: ({ commit, getters }) => {
        getters.fileQueue.forEach(file => {
            if (!file.completed && !file.error) {
                commit('PAUSE_UPLOAD', file.id)
            }
        })
        events.$emit('toaster', {
            type: 'info',
            message: i18n.t('all_uploads_paused'),
        })
    },
    resumeAllUploads: ({ commit, dispatch, getters }) => {
        getters.fileQueue.forEach(file => {
            if (file.paused) {
                commit('RESUME_UPLOAD', file.id)
            }
        })
        dispatch('processUploadQueue')
        events.$emit('toaster', {
            type: 'info',
            message: i18n.t('all_uploads_resumed'),
        })
    },
    cancelAllUploads: ({ commit, getters }) => {
        // Cancel all active uploads
        getters.activeUploads.forEach((source) => {
            source.cancel('Upload cancelled by user')
        })
        
        commit('CLEAR_UPLOAD_PROGRESS')
        events.$emit('toaster', {
            type: 'danger',
            message: i18n.t('all_uploads_cancelled'),
        })
    },
    retryFailedUpload: ({ commit, dispatch, getters }, fileIndex) => {
        const file = getters.fileQueue[fileIndex]
        if (file && file.error) {
            commit('RESET_FILE_STATE', fileIndex)
            commit('RESET_RETRY_ATTEMPT', fileIndex)
            dispatch('processUploadQueue')
        }
    }
}

const mutations = {
    UPDATE_UPLOADING_FOLDER_STATE(state, status) {
        state.isUploadingFolder = status
    },
    PROCESSING_POPUP(state, status) {
        state.processingPopup = status
    },
    ADD_FILES_TO_QUEUE(state, file) {
        state.fileQueue.push(file)
    },
    SHIFT_FROM_FILE_QUEUE(state) {
        state.fileQueue.shift()
    },
    REMOVE_FILE_FROM_QUEUE(state, index) {
        if (index >= 0 && index < state.fileQueue.length) {
            const file = state.fileQueue[index]
            // Cancel active upload if exists
            if (state.activeUploads.has(index)) {
                const source = state.activeUploads.get(index)
                source.cancel('Upload cancelled by user')
                state.activeUploads.delete(index)
            }
            
            state.fileQueue.splice(index, 1)
            state.retryAttempts.delete(index)
            state.pausedUploads.delete(file.id)
        }
    },
    MOVE_FILE_IN_QUEUE(state, { from, to }) {
        if (from >= 0 && from < state.fileQueue.length && to >= 0 && to < state.fileQueue.length) {
            const file = state.fileQueue.splice(from, 1)[0]
            state.fileQueue.splice(to, 0, file)
        }
    },
    PROCESSING_FILE(state, status) {
        state.isProcessingFile = status
    },
    UPLOADING_FILE_PROGRESS(state, percentage) {
        state.uploadingProgress = percentage
    },
    INCREASE_FILES_IN_QUEUES_TOTAL(state) {
        state.filesInQueueTotal += 1
    },
    INCREASE_FILES_IN_QUEUE_UPLOADED(state) {
        state.filesInQueueUploaded++
    },
    CLEAR_UPLOAD_PROGRESS(state) {
        state.filesInQueueUploaded = 0
        state.filesInQueueTotal = 0
        state.fileQueue = []
        state.activeUploads.clear()
        state.retryAttempts.clear()
        state.pausedUploads.clear()
        state.uploadStats = {
            speed: 0,
            timeRemaining: 0,
            startTime: null,
        }
    },
    CLEAR_COMPLETED_UPLOADS(state) {
        state.fileQueue = state.fileQueue.filter(file => !file.completed)
        state.filesInQueueUploaded = 0
    },
    // Enhanced upload management mutations
    SET_ACTIVE_UPLOAD(state, { fileIndex, source }) {
        state.activeUploads.set(fileIndex, source)
    },
    REMOVE_ACTIVE_UPLOAD(state, fileIndex) {
        state.activeUploads.delete(fileIndex)
    },
    MARK_FILE_COMPLETED(state, fileIndex) {
        if (state.fileQueue[fileIndex]) {
            state.fileQueue[fileIndex].completed = true
            state.fileQueue[fileIndex].progress = 100
        }
    },
    MARK_FILE_FAILED(state, fileIndex) {
        if (state.fileQueue[fileIndex]) {
            state.fileQueue[fileIndex].error = true
            state.fileQueue[fileIndex].progress = 0
        }
    },
    RESET_FILE_STATE(state, fileIndex) {
        if (state.fileQueue[fileIndex]) {
            state.fileQueue[fileIndex].error = null
            state.fileQueue[fileIndex].completed = false
            state.fileQueue[fileIndex].progress = 0
            state.fileQueue[fileIndex].paused = false
        }
    },
    PAUSE_UPLOAD(state, fileId) {
        const file = state.fileQueue.find(f => f.id === fileId)
        if (file) {
            file.paused = true
            state.pausedUploads.add(fileId)
        }
    },
    RESUME_UPLOAD(state, fileId) {
        const file = state.fileQueue.find(f => f.id === fileId)
        if (file) {
            file.paused = false
            state.pausedUploads.delete(fileId)
        }
    },
    UPDATE_UPLOAD_STATS(state, { speed, timeRemaining }) {
        state.uploadStats.speed = speed
        state.uploadStats.timeRemaining = timeRemaining
        if (!state.uploadStats.startTime) {
            state.uploadStats.startTime = Date.now()
        }
    },
    INCREMENT_RETRY_ATTEMPT(state, fileIndex) {
        const current = state.retryAttempts.get(fileIndex) || 0
        state.retryAttempts.set(fileIndex, current + 1)
    },
    RESET_RETRY_ATTEMPT(state, fileIndex) {
        state.retryAttempts.delete(fileIndex)
    },
}

const getters = {
    filesInQueueUploaded: (state) => state.filesInQueueUploaded,
    filesInQueueTotal: (state) => state.filesInQueueTotal,
    uploadingProgress: (state) => state.uploadingProgress,
    isUploadingFolder: (state) => state.isUploadingFolder,
    isProcessingFile: (state) => state.isProcessingFile,
    processingPopup: (state) => state.processingPopup,
    fileQueue: (state) => state.fileQueue,
    // Enhanced getters
    activeUploads: (state) => state.activeUploads,
    maxSimultaneousUploads: (state) => state.maxSimultaneousUploads,
    uploadStats: (state) => state.uploadStats,
    retryAttempts: (state) => state.retryAttempts,
    pausedUploads: (state) => state.pausedUploads,
    hasActiveUploads: (state) => state.activeUploads.size > 0,
    hasCompletedUploads: (state) => state.fileQueue.some(file => file.completed),
    allUploadsPaused: (state) => state.fileQueue.length > 0 && state.fileQueue.every(file => file.paused || file.completed),
}

export default {
    state: defaultState,
    mutations,
    actions,
    getters,
}
