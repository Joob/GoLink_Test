<template>
    <transition name="info-panel">
        <div v-if="fileQueue.length > 0" class="upload-progress">
            <div class="progress-title">
                <!--Is processing-->
                <span v-if="isProcessingFile" class="flex items-center justify-center">
                    <refresh-cw-icon size="12" class="sync-alt text-theme" />
                    {{ $t('uploading.processing_file') }}
                </span>

                <!--Multi file upload-->
                <span v-if="!isProcessingFile && fileQueue.length > 0">
                    {{
                        $t('uploading.progress', {
                            current: filesInQueueUploaded,
                            total: filesInQueueTotal,
                            progress: uploadingProgress,
                        })
                    }}
                </span>
                
                <!--Current file info for large files with chunks-->
                <div v-if="currentUploadInfo && currentUploadInfo.hasChunks" class="current-file-info">
                    <div class="file-name">{{ currentUploadInfo.fileName }}</div>
                    <div class="chunk-info">
                        Chunks: {{ currentUploadInfo.chunks }}
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
import ProgressBar from './ProgressBar'
import { RefreshCwIcon, XIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'UploadProgress',
    components: {
        RefreshCwIcon,
        ProgressBar,
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
                    chunks: `${uploadingFile.uploadedChunks}/${uploadingFile.totalChunks}`,
                    hasChunks: uploadingFile.totalChunks > 1
                };
            }
            
            return null;
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
        
        .current-file-info {
            margin-top: 5px;
            
            .file-name {
                @include font-size(12);
                font-weight: 500;
                color: $text;
                text-overflow: ellipsis;
                overflow: hidden;
                white-space: nowrap;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .chunk-info {
                @include font-size(11);
                font-weight: 400;
                color: $text-muted;
                margin-top: 2px;
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
