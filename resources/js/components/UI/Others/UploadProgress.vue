<template>
    <transition name="info-panel">
        <div v-if="fileQueue.length > 0 || isProcessingFile" class="upload-progress">
            <div class="progress-title">
                <!--Is processing-->
                <span v-if="isProcessingFile" class="flex items-center justify-center">
                    <refresh-cw-icon size="12" class="sync-alt text-theme" />
                    {{ $t('uploading.processing_file') }}
                </span>

                <!--Upload progress-->
                <span v-else-if="fileQueue.length > 0">
                    {{ progressText }}
                </span>
            </div>
            <div class="progress-wrapper">
                <ProgressBar :progress="displayProgress" />
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
            'globalUploadProgress',
            'currentFileProgress',
            'isProcessingFile',
            'fileQueue',
        ]),
        displayProgress() {
            // Use global progress for multiple files, individual progress for single file
            if (this.filesInQueueTotal > 1) {
                return this.globalUploadProgress;
            } else {
                return this.uploadingProgress;
            }
        },
        progressText() {
            // Show more detailed progress text for better user experience
            if (this.isProcessingFile) {
                return this.$t('uploading.processing_file');
            } else if (this.filesInQueueTotal > 1) {
                return this.$t('uploading.progress', {
                    current: this.filesInQueueUploaded,
                    total: this.filesInQueueTotal,
                    progress: this.displayProgress,
                });
            } else {
                return this.$t('uploading.progress', {
                    current: this.filesInQueueUploaded,
                    total: this.filesInQueueTotal,
                    progress: this.displayProgress,
                });
            }
        }
    },
    methods: {
        cancelUpload() {
            // Emit cancel event for any ongoing uploads
            events.$emit('cancel-upload')
            
            // Clear all upload progress state
            this.$store.commit('CLEAR_UPLOAD_PROGRESS')
            
            // Show cancellation message after a brief delay
            setTimeout(() => {
                events.$emit('toaster', { 
                    type: 'danger',
                    message: this.$t('uploaded_canceled'),
                })
            }, 500) // Reduced delay for better UX
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
    }
}

.dark {
    .progress-bar {
        background: $dark_mode_foreground;
    }
}
</style>
