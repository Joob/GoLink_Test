<template>
  <div class="progress-container" v-if="visible">
    <div class="progress-header">
      <h4>{{ title }}</h4>
      <button @click="$emit('close')" class="close-btn">×</button>
    </div>
    
    <div class="progress-body">
      <!-- Upload Global Progress -->
      <div class="overall-progress" v-if="totalFiles > 1">
        <p>Total: {{ completedFiles }} / {{ totalFiles }} ficheiros</p>
        <div class="progress-bar">
          <div 
            class="progress-fill"
            :style="{ width: overallProgress + '%' }"
          ></div>
        </div>
        <span class="progress-text">{{ overallProgress }}%</span>
      </div>

      <!-- Individual File Progress -->
      <div 
        v-for="file in files" 
        :key="file.id"
        class="file-progress"
      >
        <div class="file-info">
          <span class="file-name">{{ file.name }}</span>
          <span class="file-size">{{ formatFileSize(file.size) }}</span>
        </div>
        
        <div class="progress-bar">
          <div 
            class="progress-fill"
            :class="{ 
              'success': file.status === 'completed',
              'error': file.status === 'error',
              'paused': file.status === 'paused'
            }"
            :style="{ width: file.progress + '%' }"
          ></div>
        </div>
        
        <div class="progress-details">
          <span class="progress-percent">{{ file.progress }}%</span>
          <span class="progress-speed" v-if="file.speed">
            {{ formatSpeed(file.speed) }}
          </span>
          <span class="progress-eta" v-if="file.eta">
            ETA: {{ formatTime(file.eta) }}
          </span>
        </div>

        <div class="progress-actions" v-if="file.status !== 'completed'">
          <button 
            @click="togglePause(file.id)"
            :disabled="file.status === 'error'"
          >
            {{ file.status === 'paused' ? 'Retomar' : 'Pausar' }}
          </button>
          <button 
            @click="cancelUpload(file.id)"
            class="cancel-btn"
          >
            Cancelar
          </button>
          <button 
            v-if="file.status === 'error'"
            @click="retryUpload(file.id)"
            class="retry-btn"
          >
            Tentar Novamente
          </button>
        </div>

        <div v-if="file.error" class="error-message">
          {{ file.error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProgressBar',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Upload em Progresso'
    }
  },
  data() {
    return {
      files: {},
      totalFiles: 0,
      completedFiles: 0
    }
  },
  computed: {
    overallProgress() {
      if (this.totalFiles === 0) return 0;
      
      const totalProgress = Object.values(this.files)
        .reduce((sum, file) => sum + file.progress, 0);
      
      return Math.round(totalProgress / this.totalFiles);
    }
  },
  methods: {
    addFile(fileData) {
      this.$set(this.files, fileData.id, {
        id: fileData.id,
        name: fileData.name,
        size: fileData.size,
        progress: 0,
        status: 'uploading',
        speed: 0,
        eta: 0,
        error: null,
        startTime: Date.now(),
        uploadedBytes: 0
      });
      this.totalFiles++;
    },
    
    updateProgress(fileId, data) {
      if (!this.files[fileId]) return;
      
      const file = this.files[fileId];
      const now = Date.now();
      const timeElapsed = (now - file.startTime) / 1000;
      
      file.progress = data.progress;
      file.uploadedBytes = data.uploadedBytes;
      
      // Calculate speed and ETA
      if (timeElapsed > 0) {
        file.speed = file.uploadedBytes / timeElapsed;
        const remainingBytes = file.size - file.uploadedBytes;
        file.eta = remainingBytes / file.speed;
      }
      
      this.$forceUpdate();
    },
    
    setFileStatus(fileId, status, error = null) {
      if (!this.files[fileId]) return;
      
      this.files[fileId].status = status;
      this.files[fileId].error = error;
      
      if (status === 'completed') {
        this.completedFiles++;
        this.files[fileId].progress = 100;
      }
      
      this.$forceUpdate();
    },
    
    togglePause(fileId) {
      this.$emit('toggle-pause', fileId);
    },
    
    cancelUpload(fileId) {
      this.$emit('cancel-upload', fileId);
      this.removeFile(fileId);
    },
    
    retryUpload(fileId) {
      this.$emit('retry-upload', fileId);
      this.files[fileId].status = 'uploading';
      this.files[fileId].error = null;
    },
    
    removeFile(fileId) {
      if (this.files[fileId]) {
        if (this.files[fileId].status === 'completed') {
          this.completedFiles--;
        }
        this.$delete(this.files, fileId);
        this.totalFiles--;
      }
    },
    
    reset() {
      this.files = {};
      this.totalFiles = 0;
      this.completedFiles = 0;
    },
    
    formatFileSize(bytes) {
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
      if (bytes === 0) return '0 Bytes';
      const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
      return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    },
    
    formatSpeed(bytesPerSecond) {
      return this.formatFileSize(bytesPerSecond) + '/s';
    },
    
    formatTime(seconds) {
      if (seconds < 60) return Math.round(seconds) + 's';
      if (seconds < 3600) return Math.round(seconds / 60) + 'm';
      return Math.round(seconds / 3600) + 'h';
    }
  }
}
</script>

<style scoped>
.progress-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 400px;
  max-height: 500px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.progress-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-body {
  max-height: 400px;
  overflow-y: auto;
  padding: 15px;
}

.overall-progress {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.file-progress {
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
}

.file-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.file-name {
  font-weight: 500;
  color: #495057;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 250px;
}

.file-size {
  color: #6c757d;
  font-size: 14px;
}

.progress-bar {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: #007bff;
  transition: width 0.3s ease;
}

.progress-fill.success {
  background: #28a745;
}

.progress-fill.error {
  background: #dc3545;
}

.progress-fill.paused {
  background: #ffc107;
}

.progress-details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #6c757d;
  margin-bottom: 10px;
}

.progress-actions {
  display: flex;
  gap: 10px;
}

.progress-actions button {
  padding: 5px 10px;
  font-size: 12px;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.progress-actions button:hover:not(:disabled) {
  background: #f8f9fa;
}

.progress-actions button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cancel-btn {
  color: #dc3545;
  border-color: #dc3545;
}

.retry-btn {
  color: #28a745;
  border-color: #28a745;
}

.error-message {
  margin-top: 10px;
  padding: 8px;
  background: #f8d7da;
  color: #721c24;
  border-radius: 4px;
  font-size: 12px;
}
</style>