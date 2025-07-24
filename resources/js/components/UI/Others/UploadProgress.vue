<template>
    <transition name="info-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress">
            <div class="progress-title">
                <!--Is processing-->
                <span v-if="isProcessingFile" class="flex items-center justify-center">
                    <refresh-cw-icon size="12" class="sync-alt text-theme" />
                    {{ $t('uploading.processing_file') }}
                </span>

                <!--File upload progress with current file name and remaining count-->
                <div v-if="!isProcessingFile && fileQueue.length > 0" class="upload-info">
                    <div class="file-list-info">
                        <div class="current-file">
                            <strong>{{ currentFileName }}</strong>
                        </div>
                        <div class="remaining-files">
                            {{ remainingFilesText }}
                        </div>
                    </div>
                    <div class="progress-info">
                        {{ uploadingProgress }}%
                    </div>
                </div>
            </div>
            <div class="progress-wrapper">
                <ProgressBar :progress="uploadingProgress" />
                <span @click="cancelUpload" :title="$t('uploading.cancel')" class="cancel-icon">
                    <x-icon size="16" @click="cancelUpload" class="hover-text-theme"></x-icon>
                </span>
            </div>
        </div>
    </transition>
</template>

<script>
import SimpleProgressBar from './SimpleProgressBar'
import { RefreshCwIcon, XIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'UploadProgress',
    components: {
        RefreshCwIcon,
        ProgressBar: SimpleProgressBar,
        XIcon,
    },
    computed: {
        ...mapGetters([
            'filesInQueueUploaded',
            'filesInQueueTotal',
            'uploadingProgress',
            'isProcessingFile',
            'fileQueue',
            'uploadingFiles',
        ]),
        currentUploadInfo() {
            const currentFile = this.fileQueue[0];
            const uploadingFile = currentFile ? this.uploadingFiles[currentFile.id] : null;
            
            if (uploadingFile) {
                return {
                    fileName: uploadingFile.fileName,
                    fileSize: uploadingFile.fileSize,
                    hasChunks: uploadingFile.totalChunks > 1
                };
            }
            
            return null;
        },
        currentFileName() {
            if (this.fileQueue.length > 0) {
                return this.fileQueue[0].file.name;
            }
            return '';
        },
        remainingFilesText() {
            const total = this.filesInQueueTotal;
            const uploaded = this.filesInQueueUploaded;
            const remaining = total - uploaded;
            
            if (total === 1) {
                return '1 ficheiro';
            } else if (remaining === 1) {
                return `${remaining} ficheiro restante de ${total}`;
            } else {
                return `${remaining} ficheiros restantes de ${total}`;
            }
        }
    },
    methods: {
        cancelUpload() {
            events.$emit('cancel-upload')
            this.$store.commit('CLEAR_UPLOAD_PROGRESS')
            setTimeout(() => {
                events.$emit('toaster', { 
                    type: 'danger',
                    message: this.$t('uploaded_canceled'),
                })
            }, 1000) // 1 seconds delay
        },
    },
}
</script>

<style scoped lang="scss">
@import '../../../../sass/vuefilemanager/variables';
@import '../../../../sass/vuefilemanager/mixins';

.sync-alt {
    animation: spin 1s linear infinite;
    margin-right: 5px;

    polyline,
    path {
        color: inherit;
    }
}

@keyframes spin {
    0% {
        transform: rotate(0);
    }
    100% {
        transform: rotate(360deg);
    }
}

.info-panel-enter-active,
.info-panel-leave-active {
    transition: all 0.3s ease;
}

.info-panel-enter,
.info-panel-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}

.upload-progress {
    width: 100%;
    position: relative;
    z-index: 1;

    .progress-wrapper {
        display: flex;

        .cancel-icon {
            cursor: pointer;
            padding: 0 7px 0 13px;

            &:hover {
                line {
                    color: inherit;
                }
            }
        }
    }

    .progress-title {
        font-weight: 700;
        text-align: center;

        span {
            @include font-size(14);
        }
        
        .upload-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: left;
            padding: 0 15px;
            
            .file-list-info {
                flex: 1;
                
                .current-file {
                    @include font-size(14);
                    font-weight: 600;
                    color: $text;
                    text-overflow: ellipsis;
                    overflow: hidden;
                    white-space: nowrap;
                    max-width: 250px;
                }
                
                .remaining-files {
                    @include font-size(12);
                    font-weight: 400;
                    color: $text-muted;
                    margin-top: 2px;
                }
            }
            
            .progress-info {
                @include font-size(14);
                font-weight: 600;
                color: $theme;
                min-width: 50px;
                text-align: right;
            }
        }
    }
}

.dark {
    .progress-bar {
        background: $dark_mode_foreground;
    }
}
</style>
