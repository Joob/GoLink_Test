<template>
    <div class="upload-demo">
        <div class="demo-header">
            <h1>Enhanced Upload System Demo</h1>
            <p>This page demonstrates the new enhanced upload capabilities with simultaneous uploads, pause/resume, and retry functionality.</p>
        </div>

        <div class="demo-grid">
            <!-- Enhanced Progress Components -->
            <div class="demo-section">
                <h2>Enhanced Progress Components</h2>
                <p>The existing components have been enhanced with better visuals and functionality</p>
                
                <div class="demo-card">
                    <h3>Enhanced UploadProgress</h3>
                    <UploadProgress />
                </div>

                <div class="demo-card">
                    <h3>Enhanced ProgressBar Examples</h3>
                    <div class="progress-examples">
                        <div class="progress-demo">
                            <span>Loading (50%)</span>
                            <ProgressBar 
                                :progress="50" 
                                state="loading" 
                                :animated="true"
                                :show-details="true"
                                speed="2.3 MB/s"
                                time-remaining="45s"
                                progress-text="1.2 GB / 2.4 GB"
                            />
                        </div>
                        
                        <div class="progress-demo">
                            <span>Paused (75%)</span>
                            <ProgressBar 
                                :progress="75" 
                                state="paused" 
                                :animated="false"
                                :show-details="true"
                                progress-text="Upload paused"
                            />
                        </div>
                        
                        <div class="progress-demo">
                            <span>Success (100%)</span>
                            <ProgressBar 
                                :progress="100" 
                                state="success" 
                                :animated="false"
                                :show-details="true"
                                progress-text="Upload completed"
                            />
                        </div>
                        
                        <div class="progress-demo">
                            <span>Error</span>
                            <ProgressBar 
                                :progress="30" 
                                state="error" 
                                :animated="false"
                                :show-details="true"
                                progress-text="Upload failed"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Upload Manager -->
            <div class="demo-section">
                <h2>New Upload Manager</h2>
                <p>Complete upload management with queue, statistics, and individual file controls</p>
                
                <div class="demo-card">
                    <UploadManager 
                        :show-stats="true"
                        :max-simultaneous-uploads="3"
                    />
                </div>
            </div>

            <!-- Upload Instructions -->
            <div class="demo-section">
                <h2>How to Test</h2>
                <div class="demo-card">
                    <div class="instructions">
                        <div class="instruction-item">
                            <h4>1. Upload Files</h4>
                            <p>Use the upload button in the toolbar or drag & drop files onto the page</p>
                        </div>
                        <div class="instruction-item">
                            <h4>2. Multiple Files</h4>
                            <p>Upload multiple files to see simultaneous upload processing (max 3 at once)</p>
                        </div>
                        <div class="instruction-item">
                            <h4>3. Pause/Resume</h4>
                            <p>Use the pause/resume buttons to control individual uploads or all uploads</p>
                        </div>
                        <div class="instruction-item">
                            <h4>4. Error Handling</h4>
                            <p>Failed uploads will automatically retry with exponential backoff</p>
                        </div>
                        <div class="instruction-item">
                            <h4>5. Queue Management</h4>
                            <p>Reorder uploads in the queue, cancel individual files, or manage the entire queue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison -->
            <div class="demo-section">
                <h2>Feature Comparison</h2>
                <div class="demo-card">
                    <div class="comparison-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Before</th>
                                    <th>After</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Simultaneous Uploads</td>
                                    <td>❌ Sequential only</td>
                                    <td>✅ Up to 3 simultaneous</td>
                                </tr>
                                <tr>
                                    <td>Pause/Resume</td>
                                    <td>❌ Not available</td>
                                    <td>✅ Individual & global control</td>
                                </tr>
                                <tr>
                                    <td>Progress Details</td>
                                    <td>❌ Basic percentage</td>
                                    <td>✅ Speed, time, visual states</td>
                                </tr>
                                <tr>
                                    <td>Retry Logic</td>
                                    <td>❌ Simple retry</td>
                                    <td>✅ Exponential backoff</td>
                                </tr>
                                <tr>
                                    <td>Queue Management</td>
                                    <td>❌ Basic FIFO</td>
                                    <td>✅ Reorder, cancel, prioritize</td>
                                </tr>
                                <tr>
                                    <td>Upload Statistics</td>
                                    <td>❌ Not available</td>
                                    <td>✅ Detailed stats & monitoring</td>
                                </tr>
                                <tr>
                                    <td>Visual Feedback</td>
                                    <td>❌ Basic animations</td>
                                    <td>✅ Rich animations & states</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import UploadProgress from '../components/UI/Others/UploadProgress.vue'
import ProgressBar from '../components/UI/Others/ProgressBar.vue'
import UploadManager from '../components/Upload/UploadManager.vue'

export default {
    name: 'UploadDemo',
    components: {
        UploadProgress,
        ProgressBar,
        UploadManager,
    },
    data() {
        return {
            //
        }
    }
}
</script>

<style scoped lang="scss">
@import '../sass/vuefilemanager/variables';
@import '../sass/vuefilemanager/mixins';

.upload-demo {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;

    .demo-header {
        text-align: center;
        margin-bottom: 40px;

        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            color: $text;
        }

        p {
            font-size: 18px;
            color: $text;
            opacity: 0.7;
            max-width: 600px;
            margin: 0 auto;
        }
    }

    .demo-grid {
        display: grid;
        gap: 40px;
    }

    .demo-section {
        h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: $text;
        }

        > p {
            color: $text;
            opacity: 0.7;
            margin-bottom: 20px;
        }
    }

    .demo-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;

        h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: $text;
        }
    }

    .progress-examples {
        display: grid;
        gap: 16px;

        .progress-demo {
            span {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: $text;
            }
        }
    }

    .instructions {
        display: grid;
        gap: 20px;

        .instruction-item {
            h4 {
                font-weight: 600;
                color: $text;
                margin-bottom: 8px;
            }

            p {
                color: $text;
                opacity: 0.7;
                line-height: 1.5;
            }
        }
    }

    .comparison-table {
        overflow-x: auto;

        table {
            width: 100%;
            border-collapse: collapse;

            th, td {
                text-align: left;
                padding: 12px;
                border-bottom: 1px solid $light_background;
            }

            th {
                font-weight: 600;
                background: $light_background;
            }

            td:first-child {
                font-weight: 500;
            }
        }
    }
}

.dark {
    .upload-demo {
        .demo-header h1,
        .demo-section h2,
        .demo-card h3 {
            color: $dark_mode_text_primary;
        }

        .demo-header p,
        .demo-section > p {
            color: $dark_mode_text_primary;
        }

        .demo-card {
            background: $dark_mode_foreground;
            color: $dark_mode_text_primary;
        }

        .instructions .instruction-item {
            h4 {
                color: $dark_mode_text_primary;
            }

            p {
                color: $dark_mode_text_primary;
            }
        }

        .comparison-table table {
            th {
                background: $dark_mode_background;
                color: $dark_mode_text_primary;
            }

            th, td {
                border-color: $dark_mode_background;
                color: $dark_mode_text_primary;
            }
        }
    }
}

@media (max-width: 768px) {
    .upload-demo {
        padding: 16px;

        .demo-header h1 {
            font-size: 24px;
        }

        .demo-header p {
            font-size: 16px;
        }

        .demo-card {
            padding: 16px;
        }
    }
}
</style>