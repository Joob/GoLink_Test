<template>
  <div class="simple-progress-bar">
    <div 
      class="progress-fill"
      :style="{ width: progress + '%' }"
      :class="{ 'progress-active': progress > 0 && progress < 100 }"
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
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
  width: 100%;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #059669);
  transition: width 0.3s ease;
  border-radius: 3px;
  position: relative;
  min-width: 0;
}

.progress-active {
  animation: progress-shimmer 2s ease-in-out infinite;
}

/* Dark mode support */
.dark .simple-progress-bar {
  background: #374151;
}

.dark .progress-fill {
  background: linear-gradient(90deg, #10b981, #059669);
}

/* Animation for active progress */
@keyframes progress-shimmer {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
  100% {
    opacity: 1;
  }
}

/* Subtle glow effect for progress */
.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  animation: progress-flow 1.5s ease-in-out infinite;
  opacity: 0;
}

.progress-active::after {
  opacity: 1;
}

@keyframes progress-flow {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>