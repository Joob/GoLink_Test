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
  position: relative;
}

.progress-fill {
  height: 100%;
  background: #28a745; /* Green color */
  transition: width 0.3s ease;
  border-radius: 4px;
  min-width: 2px; /* Ensure some visibility even at 0% */
}

/* Dark mode support */
.dark .simple-progress-bar {
  background: #374151;
}

.dark .progress-fill {
  background: #10b981; /* Slightly different green for dark mode */
}

/* Animation for smooth progress */
@keyframes progress-pulse {
  0% { opacity: 1; }
  50% { opacity: 0.8; }
  100% { opacity: 1; }
}

.progress-fill {
  animation: progress-pulse 2s ease-in-out infinite;
}
</style>