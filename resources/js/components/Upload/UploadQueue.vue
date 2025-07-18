<template>
    <div class="upload-queue">
        <div class="queue-header">
            <h4 class="queue-title">{{ $t('upload_queue') }}</h4>
            <div class="queue-info">
                <span class="simultaneous-info">
                    {{ $t('max_simultaneous_uploads', { max: maxSimultaneous }) }}
                </span>
            </div>
        </div>

        <div class="queue-content">
            <div 
                v-for="(file, index) in files" 
                :key="file.file.name + index"
                class="queue-item"
                :class="getItemClass(file, index)"
            >
                <FileUploadItem
                    :file="file"
                    :index="index"
                    :is-current="index === currentUploadIndex"
                    :is-active="isActiveUpload(index)"
                    :is-completed="isCompleted(file, index)"
                    :progress="getProgress(index)"
                    :can-move-up="index > 0"
                    :can-move-down="index < files.length - 1"
                    @pause="$emit('pause-file', file)"
                    @resume="$emit('resume-file', file)"
                    @cancel="$emit('cancel-file', file)"
                    @retry="$emit('retry-file', file)"
                    @move-up="$emit('move-up', file)"
                    @move-down="$emit('move-down', file)"
                />
            </div>

            <div v-if="files.length === 0" class="empty-queue">
                <upload-cloud-icon size="48" />
                <h3>{{ $t('no_files_in_queue') }}</h3>
                <p>{{ $t('drag_files_or_click_upload') }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import FileUploadItem from './FileUploadItem.vue'
import { UploadCloudIcon } from 'vue-feather-icons'
import { mapGetters } from 'vuex'

export default {
    name: 'UploadQueue',
    components: {
        FileUploadItem,
        UploadCloudIcon,
    },
    props: {
        files: {
            type: Array,
            required: true
        },
        currentUploadIndex: {
            type: Number,
            default: -1
        },
        maxSimultaneous: {
            type: Number,
            default: 3
        }
    },
    computed: {
        ...mapGetters([
            'uploadingProgress',
            'filesInQueueUploaded',
        ]),
    },
    methods: {
        getItemClass(file, index) {
            const classes = []
            
            if (this.isActiveUpload(index)) {
                classes.push('is-active')
            }
            
            if (this.isCompleted(file, index)) {
                classes.push('is-completed')
            }
            
            if (file.paused) {
                classes.push('is-paused')
            }
            
            if (file.error) {
                classes.push('is-error')
            }
            
            if (this.isWaiting(index)) {
                classes.push('is-waiting')
            }
            
            return classes
        },
        isActiveUpload(index) {
            return index < this.maxSimultaneous && 
                   index >= this.filesInQueueUploaded && 
                   !this.files[index]?.completed && 
                   !this.files[index]?.paused &&
                   !this.files[index]?.error
        },
        isCompleted(file, index) {
            return file.completed || index < this.filesInQueueUploaded
        },
        isWaiting(index) {
            return index >= this.maxSimultaneous || 
                   (index > this.filesInQueueUploaded && 
                    !this.isActiveUpload(index))
        },
        getProgress(index) {
            if (this.isCompleted(this.files[index], index)) {
                return 100
            }
            if (index === this.currentUploadIndex) {
                return this.uploadingProgress
            }
            if (this.isActiveUpload(index)) {
                return 0 // Starting upload
            }
            return 0 // Waiting
        },
    }
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.upload-queue {
    .queue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid $light_background;

        .queue-title {
            margin: 0;
            font-weight: 600;
            @include font-size(16);
        }

        .queue-info {
            .simultaneous-info {
                @include font-size(12);
                color: $text;
                opacity: 0.7;
                background: $light_background;
                padding: 4px 8px;
                border-radius: 4px;
            }
        }
    }

    .queue-content {
        max-height: 400px;
        overflow-y: auto;

        .queue-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;

            &:last-child {
                border-bottom: none;
            }

            &.is-active {
                background: rgba(59, 130, 246, 0.05);
                border-left: 3px solid #3b82f6;
            }

            &.is-completed {
                background: rgba(16, 185, 129, 0.05);
                border-left: 3px solid #10b981;
            }

            &.is-paused {
                background: rgba(251, 191, 36, 0.05);
                border-left: 3px solid #fbbf24;
            }

            &.is-error {
                background: rgba(239, 68, 68, 0.05);
                border-left: 3px solid #ef4444;
            }

            &.is-waiting {
                opacity: 0.6;
            }
        }

        .empty-queue {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            color: $text;
            opacity: 0.6;

            h3 {
                margin: 16px 0 8px;
                font-weight: 600;
                @include font-size(18);
            }

            p {
                margin: 0;
                @include font-size(14);
            }
        }
    }
}

.dark {
    .upload-queue {
        color: $dark_mode_text_primary;

        .queue-header {
            border-color: $dark_mode_foreground;

            .simultaneous-info {
                background: $dark_mode_foreground;
                color: $dark_mode_text_primary;
            }
        }

        .queue-content {
            .queue-item {
                border-color: rgba(255, 255, 255, 0.05);
            }

            .empty-queue {
                color: $dark_mode_text_primary;
            }
        }
    }
}
</style>