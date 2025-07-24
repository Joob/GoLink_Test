<template>
  <div class="simple-progress-bar">
    <div 
      class="progress-fill"
      :style="{ width: progress + '%' }"
    ></div>
  </div>
</template>

<script>
export default {
  name: 'SimpleProgressBar',
  props: {
    progress: {
      type: Number,
      default: 0
    }
  }
}
</script>

<style scoped>
.simple-progress-bar {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  width: 100%;
}

.progress-fill {
  height: 100%;
  background: #28a745; /* Green color */
  transition: width 0.3s ease;
  border-radius: 4px;
}

.dark .simple-progress-bar {
  background: #374151;
}
</style>