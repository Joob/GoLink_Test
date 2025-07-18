<template>
    <div class="upload-stats">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon">
                    <file-text-icon size="20" />
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ completedFiles }} / {{ totalFiles }}</div>
                    <div class="stat-label">{{ $t('files') }}</div>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon">
                    <upload-icon size="20" />
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ activeUploads }}</div>
                    <div class="stat-label">{{ $t('active_uploads') }}</div>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon">
                    <trending-up-icon size="20" />
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ formattedSpeed }}</div>
                    <div class="stat-label">{{ $t('upload_speed') }}</div>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon">
                    <clock-icon size="20" />
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ formattedTimeRemaining }}</div>
                    <div class="stat-label">{{ $t('time_remaining') }}</div>
                </div>
            </div>

            <div class="stat-item stat-wide">
                <div class="stat-icon">
                    <hard-drive-icon size="20" />
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ formattedUploadedSize }} / {{ formattedTotalSize }}</div>
                    <div class="stat-label">{{ $t('data_transferred') }}</div>
                    <div class="stat-progress">
                        <div class="progress-bar-mini">
                            <div 
                                class="progress-fill-mini" 
                                :style="{ width: dataProgress + '%' }"
                            ></div>
                        </div>
                        <span class="progress-percentage">{{ Math.round(dataProgress) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Indicator -->
        <div class="performance-indicator">
            <div class="performance-label">{{ $t('connection_quality') }}:</div>
            <div class="performance-status" :class="connectionQualityClass">
                <div class="signal-bars">
                    <div class="bar" :class="{ active: connectionQuality >= 1 }"></div>
                    <div class="bar" :class="{ active: connectionQuality >= 2 }"></div>
                    <div class="bar" :class="{ active: connectionQuality >= 3 }"></div>
                    <div class="bar" :class="{ active: connectionQuality >= 4 }"></div>
                </div>
                <span class="performance-text">{{ connectionQualityText }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import { 
    FileTextIcon, 
    UploadIcon, 
    TrendingUpIcon, 
    ClockIcon, 
    HardDriveIcon 
} from 'vue-feather-icons'

export default {
    name: 'UploadStats',
    components: {
        FileTextIcon,
        UploadIcon,
        TrendingUpIcon,
        ClockIcon,
        HardDriveIcon,
    },
    props: {
        totalFiles: {
            type: Number,
            default: 0
        },
        completedFiles: {
            type: Number,
            default: 0
        },
        activeUploads: {
            type: Number,
            default: 0
        },
        uploadSpeed: {
            type: Number,
            default: 0
        },
        timeRemaining: {
            type: Number,
            default: 0
        },
        totalSize: {
            type: Number,
            default: 0
        },
        uploadedSize: {
            type: Number,
            default: 0
        }
    },
    computed: {
        formattedSpeed() {
            if (this.uploadSpeed === 0) return '--'
            return this.formatBytes(this.uploadSpeed) + '/s'
        },
        formattedTimeRemaining() {
            if (this.timeRemaining === 0) return '--'
            return this.formatTime(this.timeRemaining)
        },
        formattedTotalSize() {
            return this.formatBytes(this.totalSize)
        },
        formattedUploadedSize() {
            return this.formatBytes(this.uploadedSize)
        },
        dataProgress() {
            if (this.totalSize === 0) return 0
            return (this.uploadedSize / this.totalSize) * 100
        },
        connectionQuality() {
            // Determine connection quality based on upload speed
            // 1 = Poor (<1MB/s), 2 = Fair (1-5MB/s), 3 = Good (5-20MB/s), 4 = Excellent (>20MB/s)
            const speedMBps = this.uploadSpeed / (1024 * 1024)
            if (speedMBps < 1) return 1
            if (speedMBps < 5) return 2
            if (speedMBps < 20) return 3
            return 4
        },
        connectionQualityClass() {
            const qualityClasses = {
                1: 'poor',
                2: 'fair', 
                3: 'good',
                4: 'excellent'
            }
            return qualityClasses[this.connectionQuality] || 'poor'
        },
        connectionQualityText() {
            const qualityTexts = {
                1: this.$t ? this.$t('poor') : 'Poor',
                2: this.$t ? this.$t('fair') : 'Fair',
                3: this.$t ? this.$t('good') : 'Good',
                4: this.$t ? this.$t('excellent') : 'Excellent'
            }
            return qualityTexts[this.connectionQuality] || 'Poor'
        }
    },
    methods: {
        formatBytes(bytes) {
            if (bytes === 0) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
        },
        formatTime(seconds) {
            if (seconds < 60) return `${Math.round(seconds)}s`
            if (seconds < 3600) {
                const minutes = Math.floor(seconds / 60)
                const remainingSeconds = Math.round(seconds % 60)
                return `${minutes}m ${remainingSeconds}s`
            }
            const hours = Math.floor(seconds / 3600)
            const minutes = Math.round((seconds % 3600) / 60)
            return `${hours}h ${minutes}m`
        }
    }
}
</script>

<style scoped lang="scss">
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.upload-stats {
    padding: 20px;
    background: rgba(249, 250, 251, 0.5);
    border-bottom: 1px solid $light_background;

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
        margin-bottom: 16px;

        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;

            &:hover {
                transform: translateY(-1px);
            }

            &.stat-wide {
                grid-column: span 2;
                
                @media (max-width: 768px) {
                    grid-column: span 1;
                }
            }

            .stat-icon {
                flex-shrink: 0;
                width: 40px;
                height: 40px;
                background: rgba(59, 130, 246, 0.1);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #3b82f6;
            }

            .stat-content {
                flex: 1;
                min-width: 0;

                .stat-value {
                    font-weight: 700;
                    @include font-size(16);
                    color: $text;
                    margin-bottom: 2px;
                }

                .stat-label {
                    @include font-size(12);
                    color: $text;
                    opacity: 0.7;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .stat-progress {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 8px;

                    .progress-bar-mini {
                        flex: 1;
                        height: 4px;
                        background: $light_background;
                        border-radius: 2px;
                        overflow: hidden;

                        .progress-fill-mini {
                            height: 100%;
                            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                            border-radius: 2px;
                            transition: width 0.3s ease;
                        }
                    }

                    .progress-percentage {
                        @include font-size(11);
                        font-weight: 600;
                        color: $text;
                        opacity: 0.8;
                        min-width: 35px;
                        text-align: right;
                    }
                }
            }
        }
    }

    .performance-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 12px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

        .performance-label {
            @include font-size(13);
            font-weight: 600;
            color: $text;
        }

        .performance-status {
            display: flex;
            align-items: center;
            gap: 8px;

            .signal-bars {
                display: flex;
                align-items: end;
                gap: 2px;

                .bar {
                    width: 3px;
                    background: #e5e7eb;
                    border-radius: 1px;
                    transition: background-color 0.3s ease;

                    &:nth-child(1) { height: 8px; }
                    &:nth-child(2) { height: 12px; }
                    &:nth-child(3) { height: 16px; }
                    &:nth-child(4) { height: 20px; }

                    &.active {
                        background: currentColor;
                    }
                }
            }

            .performance-text {
                @include font-size(12);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            &.poor {
                color: #ef4444;
            }

            &.fair {
                color: #f59e0b;
            }

            &.good {
                color: #10b981;
            }

            &.excellent {
                color: #059669;
            }
        }
    }
}

@media (max-width: 768px) {
    .upload-stats {
        .stats-grid {
            grid-template-columns: 1fr;
            
            .stat-item.stat-wide {
                grid-column: span 1;
            }
        }
    }
}

.dark {
    .upload-stats {
        background: rgba(31, 41, 55, 0.5);
        color: $dark_mode_text_primary;

        .stat-item,
        .performance-indicator {
            background: $dark_mode_foreground;
            color: $dark_mode_text_primary;

            .stat-value {
                color: $dark_mode_text_primary;
            }

            .stat-label {
                color: $dark_mode_text_primary;
            }

            .progress-bar-mini {
                background: $dark_mode_background;
            }
        }

        .performance-label {
            color: $dark_mode_text_primary;
        }
    }
}
</style>