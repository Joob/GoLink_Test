import { debounce, isArray, orderBy } from 'lodash'
import i18n from '../i18n'
import router from '../router'
import store from '../store/index'
import { events } from '../bus'
import axios from 'axios'

const FunctionHelpers = {
    install(Vue) {
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

        Vue.prototype.$updateImage = function (route, name, image) {
            // Create form
            let formData = new FormData()

            // Add image to form
            formData.append('name', name)
            formData.append(name, image)
            formData.append('_method', 'PATCH')

            axios
                .post(store.getters.api + route, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .catch((error) => {
                    events.$emit('alert:open', {
                        title: this.$t('popup_error.title'),
                        message: this.$t('popup_error.message'),
                    })
                })
        }

        Vue.prototype.$updateAvatar = function (image) {
            // Create form
            let formData = new FormData()

            // Add image to form
            formData.append('avatar', image)

            axios
                .post(`${store.getters.api}/user/avatar`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .catch(() => {
                    events.$emit('alert:open', {
                        title: this.$t('popup_error.title'),
                        message: this.$t('popup_error.message'),
                    })
                })
        }

        Vue.prototype.$scrollTop = function () {
            const container = document.getElementsByTagName('html')[0]

            if (container) {
                container.scrollTop = 0
            }
        }

        Vue.prototype.$translateSelectOptions = function (options) {
            return options.map((role) => {
                let key, values

                if (isArray(role.label)) {
                    ;[key, values] = role.label
                }

                return {
                    label: isArray(role.label) ? i18n.t(key, values) : i18n.t(role.label),
                    value: role.value,
                    icon: role.icon ? role.icon : '',
                }
            })
        }

        Vue.prototype.$mapStorageUsage = function (storage) {
            let distribution = [
                {
                    progress: storage.data.meta.images.percentage,
                    color: 'success',
                    value: storage.data.meta.images.used,
                    title: i18n.t('images'),
                },
                {
                    progress: storage.data.meta.videos.percentage,
                    color: 'danger',
                    value: storage.data.meta.videos.used,
                    title: i18n.t('videos'),
                },
                {
                    progress: storage.data.meta.audios.percentage,
                    color: 'warning',
                    value: storage.data.meta.audios.used,
                    title: i18n.t('audios'),
                },
                {
                    progress: storage.data.meta.documents.percentage,
                    color: 'info',
                    value: storage.data.meta.documents.used,
                    title: i18n.t('documents'),
                },
                {
                    progress: storage.data.meta.others.percentage,
                    color: 'purple',
                    value: storage.data.meta.others.used,
                    title: i18n.t('others'),
                },
            ]

            // Order distribution by it's size
            distribution = orderBy(distribution, ['progress'], ['desc'])

            // Push at the end empty space data
            if (config.subscriptionType === 'fixed' || (config.subscriptionType === 'none' && config.storageLimit)) {
                distribution.push({
                    progress: 100 - storage.data.attributes.percentage,
                    color: 'secondary',
                    value: storage.data.meta.others.used,
                    title: i18n.t('empty'),
                })
            }

            return distribution
        }

        Vue.prototype.$getImage = function (source) {
            return source ? store.getters.config.host + '/' + source : ''
        }

        Vue.prototype.$getCreditCardBrand = function (brand) {
            return `/assets/icons/${brand}.svg`
        }

        Vue.prototype.$getInvoiceLink = function (id) {
            return '/invoices/' + id
        }

        Vue.prototype.$uploadFiles = async function (files) {
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

            // Push items to file queue with unique IDs
            [...files].map((item, index) => {
                const fileId = Date.now() + index; // Ensure unique ID
                store.dispatch('pushFileToTheUploadQueue', {
                    id: fileId,
                    parent_id: store.getters.currentFolder ? store.getters.currentFolder.data.id : '',
                    file: item,
                    path: '/' + item.webkitRelativePath,
                })
            })
        }

        Vue.prototype.$uploadDraggedFolderOrFile = async function (files, parent_id) {
            files.map((file, index) => {
                // Get file path
                let filePath = file.filepath ? '/' + file.filepath : undefined
                const fileId = Date.now() + index; // Ensure unique ID

                // Determine if we are uploading folder or file
                if (filePath.split('/').length > 2) {
                    store.commit('UPDATE_UPLOADING_FOLDER_STATE', true)
                }
                
                // Push file to the upload queue
                store.dispatch('pushFileToTheUploadQueue', {
                    id: fileId,
                    parent_id: parent_id || '',
                    path: filePath,
                    file: file,
                })
            })
        }

        Vue.prototype.$pauseUpload = function (fileId) {
            const uploadingFile = store.getters.uploadingFiles[fileId];
            if (uploadingFile) {
                store.commit('SET_UPLOAD_FILE_STATUS', { fileId, status: 'paused' });
                events.$emit('upload-paused', fileId);
            }
        }

        Vue.prototype.$resumeUpload = function (fileId) {
            const uploadingFile = store.getters.uploadingFiles[fileId];
            if (uploadingFile) {
                store.commit('SET_UPLOAD_FILE_STATUS', { fileId, status: 'uploading' });
                // Find the file in queue and resume upload
                const fileInQueue = store.getters.fileQueue.find(f => f.id === fileId);
                if (fileInQueue) {
                    Vue.prototype.$handleUploading(fileInQueue);
                }
                events.$emit('upload-resumed', fileId);
            }
        }

        Vue.prototype.$cancelUpload = function (fileId) {
            const uploadingFile = store.getters.uploadingFiles[fileId];
            if (uploadingFile) {
                store.commit('CLEAR_UPLOAD_FILE_METADATA', fileId);
                // Remove from queue
                store.commit('REMOVE_FILE_FROM_QUEUE', fileId);
                events.$emit('upload-cancelled', fileId);
            }
        }

        Vue.prototype.$getOptimalChunkSize = function (fileSize, customChunkSize = null) {
            // Allow manual override of chunk size via config or parameter
            if (customChunkSize && customChunkSize > 0) {
                return customChunkSize;
            }

            // Check if there's a global chunk size override in config
            const configChunkSize = store.getters.config?.chunkSize;
            const useDefaultChunkingStrategy = store.getters.config?.useOptimalChunking !== false;

            // If optimal chunking is disabled, use the config chunk size
            if (!useDefaultChunkingStrategy && configChunkSize) {
                return configChunkSize;
            }

            // Define size thresholds in bytes
            const KB = 1024;
            const MB = KB * 1024;
            const GB = MB * 1024;

            // Dynamic chunk sizing based on file size for optimal performance
            if (fileSize < 10 * MB) {
                // Small files: 512KB chunks
                return 512 * KB;
            } else if (fileSize < 100 * MB) {
                // Medium files: 2MB chunks
                return 2 * MB;
            } else if (fileSize < 1 * GB) {
                // Large files: 8MB chunks
                return 8 * MB;
            } else if (fileSize < 10 * GB) {
                // Very large files: 32MB chunks
                return 32 * MB;
            } else if (fileSize < 100 * GB) {
                // Extremely large files: 64MB chunks
                return 64 * MB;
            } else {
                // TB range files: 256MB chunks for maximum efficiency
                return 256 * MB;
            }
        }

        Vue.prototype.$handleUploading = async function (item) {
            // Reset progress to 0 when starting a new file upload
            store.commit('UPLOADING_FILE_PROGRESS', 0);
            
            // Check if upload should be paused or cancelled
            const uploadingFile = store.getters.uploadingFiles[item.id];
            if (uploadingFile && uploadingFile.status === 'paused') {
                console.log('Upload paused for file:', item.file.name);
                return;
            }

            // Get optimal chunk size based on file size
            let size = this.$getOptimalChunkSize(item.file.size),
                chunksCeil = Math.ceil(item.file.size / size),
                chunks = [],
                chunkMetadata = []

            // Safety check for very large number of chunks
            if (chunksCeil > 50000) {
                console.warn('File has too many chunks, increasing chunk size for safety');
                size = Math.ceil(item.file.size / 50000); // Limit to maximum 50k chunks
                chunksCeil = Math.ceil(item.file.size / size);
            }

            // Create chunks with metadata for better tracking
            for (let i = 0; i < chunksCeil; i++) {
                const start = i * size;
                const end = Math.min(start + size, item.file.size);
                const chunkBlob = item.file.slice(start, end, item.file.type);
                
                chunks.push(chunkBlob);
                chunkMetadata.push({
                    index: i,
                    start: start,
                    end: end,
                    size: chunkBlob.size,
                    uploaded: false,
                    attempts: 0
                });
            }

            // Set Data
            let formData = new FormData(),
                uploadedSize = 0,
                isNotGeneralError = true,
                striped_spaces = item.file.name.replace(/\s/g, '-'),
                striped_to_safe_characters = striped_spaces.match(/^[A-Za-z0-9._~()'!*:@,;+?-\W]*$/g),
                source_name =
                    Array(16)
                        .fill(0)
                        .map((x) => Math.random().toString(36).charAt(2))
                        .join('') +
                    '-' +
                    striped_to_safe_characters +
                    '.part'

            // Store initial file metadata for progress tracking
            store.commit('SET_UPLOAD_FILE_METADATA', {
                fileId: item.id || Date.now(),
                fileName: item.file.name,
                fileSize: item.file.size,
                totalChunks: chunksCeil,
                uploadedChunks: 0,
                chunkMetadata: chunkMetadata
            });

            let chunkIndex = 0;
            do {
                // Check again if upload should be paused
                const currentUploadingFile = store.getters.uploadingFiles[item.id];
                if (currentUploadingFile && currentUploadingFile.status === 'paused') {
                    console.log('Upload paused during chunk upload for file:', item.file.name);
                    return;
                }

                let isLastChunk = chunks.length === 1 ? 1 : 0,
                    chunk = chunks.shift(),
                    currentChunkMeta = chunkMetadata[chunkIndex],
                    attempts = 0,
                    maxAttempts = 3,
                    retryDelay = 1000 // Start with 1 second delay

                // Set form data
                formData.set('name', item.file.name)
                formData.set('chunk', chunk, source_name)
                formData.set('extension', item.file.name.split('.').pop())
                formData.set('is_last_chunk', isLastChunk)
                formData.set('chunk_index', chunkIndex)
                formData.set('total_chunks', chunksCeil)

                if (item.path && item.path !== '/')
                    formData.set('path', item.path)

                if (item.parent_id)
                    formData.set('parent_id', item.parent_id)

                // Upload chunks with exponential backoff retry
                do {
                    try {
                        await store.dispatch('uploadFiles', {
                            form: formData,
                            fileSize: item.file.size,
                            totalUploadedSize: uploadedSize,
                            chunkIndex: chunkIndex,
                            totalChunks: chunksCeil,
                            chunkSize: chunk.size
                        });

                        // Update progress tracking
                        uploadedSize += chunk.size;
                        currentChunkMeta.uploaded = true;
                        
                        // Update chunk progress in store
                        store.commit('UPDATE_CHUNK_PROGRESS', {
                            fileId: item.id || Date.now(),
                            chunkIndex: chunkIndex,
                            uploaded: true
                        });

                        attempts = 0; // Reset attempts on success
                        break;

                    } catch (error) {
                        attempts++;
                        currentChunkMeta.attempts = attempts;

                        console.warn(`Chunk ${chunkIndex} upload attempt ${attempts} failed:`, error);

                        // Handle different error types
                        if (error.response) {
                            const statusCode = error.response.status;
                            
                            // Permanent errors - don't retry
                            if ([422, 413, 415, 423].includes(statusCode)) {
                                console.error(`Permanent error ${statusCode} for chunk ${chunkIndex}`);
                                isNotGeneralError = false;
                                break;
                            }
                            
                            // Server errors - retry with backoff
                            if ([500, 502, 503, 504].includes(statusCode) && attempts < maxAttempts) {
                                // Exponential backoff: 1s, 2s, 4s
                                const delay = retryDelay * Math.pow(2, attempts - 1);
                                console.log(`Retrying chunk ${chunkIndex} in ${delay}ms...`);
                                await new Promise(resolve => setTimeout(resolve, delay));
                                continue;
                            }
                        }

                        // Network errors - retry with backoff
                        if (!error.response && attempts < maxAttempts) {
                            const delay = retryDelay * Math.pow(2, attempts - 1);
                            console.log(`Network error, retrying chunk ${chunkIndex} in ${delay}ms...`);
                            await new Promise(resolve => setTimeout(resolve, delay));
                            continue;
                        }

                        // Max attempts reached or permanent error
                        if (attempts >= maxAttempts) {
                            console.error(`Chunk ${chunkIndex} failed after ${maxAttempts} attempts`);
                            isNotGeneralError = false;
                            store.commit('PROCESSING_FILE', false);
                            store.commit('CLEAR_UPLOAD_PROGRESS');
                        }
                        
                        break;
                    }
                } while (attempts > 0 && attempts < maxAttempts && isNotGeneralError);

                chunkIndex++;
            } while (isNotGeneralError && chunks.length !== 0);

            // Clean up file metadata after upload completes or fails
            if (chunks.length === 0 || !isNotGeneralError) {
                store.commit('CLEAR_UPLOAD_FILE_METADATA', item.id || Date.now());
            }
        }

        Vue.prototype.$downloadFile = function (url, filename) {
            // Show alert message when download is disabled
            if (store.getters.user && !store.getters.user.data.meta.restrictions.canDownload) {
              Vue.prototype.$temporarilyDisabledDownload()
              return
            }
          
            let anchor = document.createElement('a')
          
            // Add the .zip extension to the filename
            filename = filename.endsWith('.zip') ? filename : filename + '.zip'
          
            anchor.href = url
            anchor.download = filename
          
            document.body.appendChild(anchor)
          
            anchor.click()
        }

        Vue.prototype.$getCurrentLocationName = function () {
            if (store.getters.currentFolder) {
                return store.getters.currentFolder.data.attributes.name
            } else {
                return {
                    RequestUpload: this.$t('home'),
                    RecentUploads: this.$t('menu.latest'),
                    MySharedItems: this.$t('publicly_shared'),
                    Trash: this.$t('trash'),
                    Public: this.$t('menu.files'),
                    Files: this.$t('sidebar.home'),
                    TeamFolders: this.$t('team_folders'),
                    SharedWithMe: this.$t('shared_with_me'),
                }[this.$route.name]
            }
        }

        Vue.prototype.$getCurrentSectionName = function () {
            return {
                RecentUploads: this.$t('menu.latest'),
                MySharedItems: this.$t('publicly_shared'),
                Trash: this.$t('trash'),
                Public: this.$t('menu.files'),
                Files: this.$t('sidebar.home'),
                TeamFolders: this.$t('team_folders'),
                SharedWithMe: this.$t('shared_with_me'),
            }[this.$route.name]
        }

        Vue.prototype.$getCurrentSectionIcon = function () {
            return {
                RecentUploads: 'upload-cloud',
                MySharedItems: 'share',
                Trash: 'trash2',
                Public: 'hard-drive',
                Files: 'hard-drive',
                TeamFolders: 'users',
                SharedWithMe: 'user-check',
            }[this.$router.currentRoute.name]
        }

        Vue.prototype.$getDataByLocation = async function (page) {
            let routes = {
                RequestUpload: ['getUploadRequestFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                Public: ['getSharedFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                Files: ['getFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                RecentUploads: ['getRecentUploads', page],
                MySharedItems: ['getMySharedItems', page],
                Trash: ['getTrash', {page: page, id: router.currentRoute.params.id || undefined}],
                TeamFolders: ['getTeamFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                SharedWithMe: ['getSharedWithMeFolder', {page: page, id: router.currentRoute.params.id || undefined}],
            }

            return await store.dispatch(...routes[router.currentRoute.name])
        }
        /*Vue.prototype.$getDataByLocation = async function (page) {
            let routes = {
                RequestUpload: ['getUploadRequestFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                Public: ['getSharedFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                Files: ['getFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                RecentUploads: ['getRecentUploads', page],
                MySharedItems: ['getMySharedItems', page],
                Trash: ['getTrash', {page: page, id: router.currentRoute.params.id || undefined}],
                TeamFolders: ['getTeamFolder', {page: page, id: router.currentRoute.params.id || undefined}],
                SharedWithMe: ['getSharedWithMeFolder', {page: page, id: router.currentRoute.params.id || undefined}],
            }
            
            const [actionName, params] = routes[router.currentRoute.name];
            await store.dispatch(actionName, params);
            
            // Optionally, you can perform any additional tasks here after the data has been reloaded.
            //console.log('Data Reloaded!');
        }*/

        Vue.prototype.$getPaymentLogo = function (driver) {
            return (
                {
                    paypal: store.getters.isDarkMode
                        ? '/assets/payments/paypal-dark.svg'
                        : '/assets/payments/paypal.svg',
                    paystack: store.getters.isDarkMode
                        ? '/assets/payments/paystack-dark.svg'
                        : '/assets/payments/paystack.svg',
                    stripe: '/assets/payments/stripe.svg',
                    system: store.getters.isDarkMode
                        ? this.$getImage(store.getters.config.app_logo_horizontal_dark)
                        : this.$getImage(store.getters.config.app_logo_horizontal),
                }[driver] || this.$getImage(store.getters.config.app_logo_horizontal)
            )
        }

        Vue.prototype.$getSocialLogo = function (driver) {
            return {
                google: '/assets/socials/google.svg',
                facebook: '/assets/socials/facebook.svg',
                github: store.getters.isDarkMode ? '/assets/socials/github-dark.svg' : '/assets/socials/github.svg',
            }[driver]
        }

        Vue.prototype.$getSubscriptionStatusColor = function (status) {
            return {
                active: 'green',
                cancelled: 'yellow',
                completed: 'purple',
            }[status]
        }

        Vue.prototype.$getTransactionStatusColor = function (status) {
            return {
                completed: 'green',
                cancelled: 'yellow',
                error: 'red',
            }[status]
        }

        Vue.prototype.$getTransactionTypeColor = function (type) {
            return {
                credit: 'green',
                charge: 'purple',
                withdrawal: 'red',
            }[type]
        }

        Vue.prototype.$getTransactionStatusColor = function (type) {
            return {
                completed: 'green',
                error: 'red',
            }[type]
        }

        Vue.prototype.$getPlanStatusColor = function (type) {
            return {
                active: 'green',
                archived: 'red',
            }[type]
        }

        Vue.prototype.$getUserRoleColor = function (role) {
            return {
                admin: 'purple',
                user: 'green',
            }[role]
        }

        Vue.prototype.$getTransactionTypeTextColor = function (type) {
            return {
                withdrawal: 'text-red',
                credit: 'text-green',
                charge: '',
            }[type]
        }

        Vue.prototype.$getTransactionMark = function (type) {
            return {
                withdrawal: '-',
                credit: '+',
                charge: '',
            }[type]
        }

        Vue.prototype.$goToFileView = function (id) {
			// If user is located in trash, then automatically after click on the navigator go to the Files view
            if (this.$router.currentRoute.name === 'Trash') {
                this.$router.push({ name: 'Files', params: { id: id } })
                return
            }
			
            let locations = {
                RequestUpload: {name: 'RequestUpload', params: { token: this.$route.params.token, id: id }},
                Public: {name: 'Public', params: { token: this.$route.params.token, id: id }},
                TeamFolders: { name: 'TeamFolders', params: { id: id } },
                SharedWithMe: { name: 'SharedWithMe', params: { id: id } },
                MySharedItems: { name: 'Files', params: { id: id } },
                Trash: { name: 'Trash', params: { id: id } },
                Files: { name: 'Files', params: { id: id } },
            }

            this.$router.push(locations[this.$router.currentRoute.name])
        }

        Vue.prototype.$isThisRoute = function (route, locations) {
            return locations.includes(route.name)
        }

        // TODO: not working correctly in share page
        Vue.prototype.$checkPermission = function (type) {
            let currentPermission = store.getters.permission

            // Check if type is object
            if (typeof type === 'Object' || type instanceof Object) {
                return type.includes(currentPermission)
            } else {
                return currentPermission === type
            }
        }

        Vue.prototype.$checkFileMimetype = function (files) {
            let validated = true
            let mimetypesBlacklist = store.getters.config.mimetypesBlacklist

            for (let i = 0; i < files.length; i++) {
                let fileType = files[i].type.split('/')

                if (!fileType[0]) {
                    fileType[1] = _.last(files[i].name.split('.'))
                }

                if (mimetypesBlacklist.includes(fileType[1])) {
                    validated = false

                    events.$emit('alert:open', {
                        emoji: '😬😬😬',
                        title: i18n.t('popup_mimetypes_blacklist.title'),
                        message: i18n.t('popup_mimetypes_blacklist.message', {
                            mimetype: fileType[1],
                        }),
                    })
                }
            }
            return validated
        }

        Vue.prototype.$checkUploadLimit = function (files) {
            let uploadLimit = store.getters.config.uploadLimit
            let validate = true

            for (let i = 0; i < files.length; i++) {
                if (uploadLimit != 0 && files[i].size > uploadLimit) {
                    validate = false
                    events.$emit('alert:open', {
                        emoji: '😬😬😬',
                        title: i18n.t('popup_upload_limit.title'),
                        message: i18n.t('popup_upload_limit.message', {
                            uploadLimit: store.getters.config.uploadLimitFormatted,
                        }),
                    })
                    break
                }
            }
            return validate
        }

// GoLink CHANGE HERE
        /*Vue.prototype.$checkQuotaLimit = async function (item) {
            let validate = true
            let route = '/api/quota' + (
                router.currentRoute.params.token ? '/' + router.currentRoute.params.token : ''
            )

            let quota = await axios.post(route, {
                params: {"file_size": item.file.size}
            }).then(response => { return response.data });

            if (item.file.size > quota.available) {
                validate = false

                events.$emit('alert:open', {
                    emoji: '😟😟😟',
                    title: i18n.t('popup_exceed_quota_limit.title'),
                    message: i18n.t('popup_exceed_quota_limit.message', {remain: quota}),
                })

                store.commit('CLEAR_UPLOAD_PROGRESS')
                return false
            }

            return validate
        }*/
// GoLink END

        // Detect windows
        Vue.prototype.$isWindows = function () {
            return navigator.userAgent.indexOf('Windows') != -1
        }

        // Check if device is Apple
        Vue.prototype.$isApple = function () {
            const toMatch = [/iPhone/i, /iPad/i, /iPod/i, /iOS/i, /macOS/i, /Macintosh/i]

            return toMatch.some((toMatchItem) => {
                return navigator.userAgent.match(toMatchItem)
            })
        }

        Vue.prototype.$isMobile = function () {
            const toMatch = [/Android/i, /webOS/i, /iPhone/i, /iPad/i, /iPod/i, /BlackBerry/i, /Windows Phone/i]

            return toMatch.some((toMatchItem) => {
                return navigator.userAgent.match(toMatchItem)
            })
        }

        Vue.prototype.$mapIntoMemberResource = function (entry) {
            return {
                data: {
                    attributes: {
                        avatar: entry.avatar,
                        name: entry.name,
                        email: entry.email,
                        color: entry.color,
                    },
                },
            }
        }

        Vue.prototype.$closePopup = function () {
            events.$emit('popup:close')
        }

        Vue.prototype.$openInDetailPanel = function (entry) {
            // Dispatch load file info detail
            store.commit('CLIPBOARD_CLEAR')
            store.commit('ADD_ITEM_TO_CLIPBOARD', entry)

            // Show panel if is not open
            store.dispatch('fileInfoToggle', true)
        }

        Vue.prototype.$openSpotlight = function (filter = undefined) {
            events.$emit('spotlight:show', filter)
        }

        Vue.prototype.$enableMultiSelectMode = function () {
            store.commit('TOGGLE_MULTISELECT_MODE')
        }

        Vue.prototype.$showMobileMenu = function (name) {
            events.$emit('mobile-menu:show', name)
        }

        Vue.prototype.$openSubscribeOptions = function () {
            events.$emit('popup:open', { name: 'select-plan-subscription' })
        }

        Vue.prototype.$changeSubscriptionOptions = function () {
            events.$emit('popup:open', { name: 'change-plan-subscription' })
        }

        Vue.prototype.$openRemoteUploadPopup = function () {
            events.$emit('popup:open', {name: 'remote-upload'})
        }
    },
}

export default FunctionHelpers