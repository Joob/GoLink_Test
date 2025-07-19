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
    uploadStats: null,
    isPaused: false,
    isUploading: false,
    currentUploadSession: null,
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
    uploadFiles: ({ commit, getters, dispatch}, { form, fileSize, totalUploadedSize, uploadStrategy = 'chunked' }) => {
        return new Promise((resolve, reject) => {
            // Set uploading state
            commit('SET_UPLOADING_STATE', true)
            
            // Get route based on upload strategy
            let route
            if (uploadStrategy === 'direct') {
                route = {
                    RequestUpload: `/api/file-request/${router.currentRoute.params.token}/upload`,
                    Public: `/api/sharing/upload/${router.currentRoute.params.token}`,
                }[router.currentRoute.name] || '/api/upload'
            } else {
                route = {
                    RequestUpload: `/api/file-request/${router.currentRoute.params.token}/upload/chunks`,
                    Public: `/api/sharing/upload/chunks/${router.currentRoute.params.token}`,
                }[router.currentRoute.name] || '/api/upload/chunks'
            }

            // Create cancel token for axios cancellation
            const CancelToken = axios.CancelToken,
                source = CancelToken.source()
            
            let completedUploads = 0;

            // Store cancel source for potential cancellation
            commit('SET_UPLOAD_SESSION', source)

            axios
                .post(route, form, {
                    cancelToken: source.token,
                    headers: {
                        'Content-Type': uploadStrategy === 'direct' ? 'multipart/form-data' : 'application/octet-stream',
                    },
                    onUploadProgress: (event) => {
                        const completedSize = totalUploadedSize + event.loaded;
                        const fileSizeMB = fileSize / (1024 * 1024);
                        const completedSizeMB = completedSize / (1024 * 1024);
                        const progress = Math.floor((completedSize / fileSize) * 100);
                        const progressText = `${completedSizeMB.toFixed(2)}mb / ${fileSizeMB.toFixed(2)}mb`;

                        commit('UPLOADING_FILE_PROGRESS', progress >= 100 ? 100 : progress);

                        // Update enhanced upload stats
                        commit('UPDATE_UPLOAD_STATS', {
                            progress,
                            uploadedBytes: completedSize,
                            totalBytes: fileSize,
                            uploadedFormatted: `${completedSizeMB.toFixed(2)}MB`,
                            totalFormatted: `${fileSizeMB.toFixed(2)}MB`,
                            speed: null,
                            timeRemaining: null
                        });

                        // Set processing file when approaching completion
                        if (progress >= 100) {
                            commit('PROCESSING_FILE', true);
                        }
                    },
                })
                .then(async (response) => {
                    resolve(response)

                    completedUploads++;

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
                            commit('ADD_NEW_ITEM', response.data)
                        }

                        // Reset file progress
                        commit('UPLOADING_FILE_PROGRESS', 0)
                        commit('INCREASE_FILES_IN_QUEUE_UPLOADED')

                        // Start uploading next file if file queue is not empty
                        if (getters.fileQueue.length) {
                            Vue.prototype.$handleUploading(getters.fileQueue[0])
                        }

                        // Reset upload process
                        if (!getters.fileQueue.length) {
                            commit('CLEAR_UPLOAD_PROGRESS')
                        }

                        // Reload File data after folder uploading is finished
                        if (getters.isUploadingFolder) {
                            commit('START_LOADING_VIEW')
                            Vue.prototype.$getDataByLocation(1)
                            dispatch('getFolderTree')
                            commit('UPDATE_UPLOADING_FOLDER_STATE', false)
                        }
                    }
                })
                .catch((error) => {
                    commit('SET_UPLOADING_STATE', false)
                    
                    if (axios.isCancel(error)) {
                        // Upload was cancelled
                        commit('CLEAR_UPLOAD_PROGRESS')
                        reject(new Error('Upload cancelled'))
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

                        // Skip processing the current file with an error and move to the next file
                        commit('SHIFT_FROM_FILE_QUEUE');

                        // Start uploading the next file if the file queue is not empty
                        if (getters.fileQueue.length) {
                            Vue.prototype.$handleUploading(getters.fileQueue[0]);
                        } else {
                            commit('CLEAR_UPLOAD_PROGRESS')
                        }
            
                    } catch (error) {
                      console.error(error);
                    }
                    
                    reject(error)
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
    pushFileToTheUploadQueue: ({commit, getters}, item) => {
        // Prevent to upload file with 0kb file size
        if (item.file.size === 0) {
            events.$emit('toaster', {
                type: 'danger',
                message: `${i18n.t('file_0kb.first')} ${item.file.name} ${i18n.t('file_0kb.second')}`,
            });
            return;
        }

        if (item.file.size !== 0 && item.file.name !== '.DS_Store') {
            // Enhance item with upload strategy if not provided
            if (!item.uploadStrategy) {
                const SMALL_FILE_THRESHOLD = 5 * 1024 * 1024 // 5MB
                item.uploadStrategy = item.file.size > SMALL_FILE_THRESHOLD ? 'chunked' : 'direct'
            }

            // Add enhanced metadata
            item.retryCount = item.retryCount || 0
            item.sessionId = item.sessionId || null
            item.startTime = item.startTime || null
            item.status = 'queued'

            // commit file to the upload queue
            commit('ADD_FILES_TO_QUEUE', item)

            // Start uploading if uploading process isn't running
            if (getters.filesInQueueTotal === 0) {
                Vue.prototype.$handleEnhancedUploading(getters.fileQueue[0])
            }

            // Increase total files in upload bar
            commit('INCREASE_FILES_IN_QUEUES_TOTAL')
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
    REMOVE_FROM_FILE_QUEUE(state, index) {
        state.fileQueue.splice(index, 1)
    },
    PROCESSING_FILE(state, status) {
        state.isProcessingFile = status
    },
    UPLOADING_FILE_PROGRESS(state, percentage) {
        state.uploadingProgress = percentage
    },
    UPDATE_UPLOAD_STATS(state, stats) {
        state.uploadStats = stats
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
        state.uploadStats = null
        state.isPaused = false
        state.isUploading = false
        state.currentUploadSession = null
    },
    PAUSE_UPLOAD(state) {
        state.isPaused = true
        state.isUploading = false
    },
    RESUME_UPLOAD(state) {
        state.isPaused = false
        state.isUploading = true
    },
    SET_UPLOADING_STATE(state, status) {
        state.isUploading = status
    },
    SET_UPLOAD_SESSION(state, sessionId) {
        state.currentUploadSession = sessionId
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
    uploadStats: (state) => state.uploadStats,
    isPaused: (state) => state.isPaused,
    isUploading: (state) => state.isUploading,
    currentUploadSession: (state) => state.currentUploadSession,
}

export default {
    state: defaultState,
    mutations,
    actions,
    getters,
}
