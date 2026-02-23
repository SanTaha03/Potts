<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';

const props = defineProps({
  value: { type: Number, required: true },
  duration: { type: Number, default: 3000 }, // Durée en ms
  precision: { type: Number, default: 0 },
  suffix: { type: String, default: '' },
  prefix: { type: String, default: '' }
});

const displayValue = ref(props.value);

// Fonction d'easing (Ease Out Quart) pour un effet fluide
const easeOutQuart = (x: number): number => {
  return 1 - Math.pow(1 - x, 4);
};

function animate(start: number, end: number) {
    if (start === end) {
        displayValue.value = end;
        return;
    }
    
    const startTime = performance.now();
    const change = end - start;
    
    function update(currentTime: number) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / props.duration, 1);
        
        const easedProgress = easeOutQuart(progress);
        
        displayValue.value = start + (change * easedProgress);
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            displayValue.value = end;
        }
    }
    
    requestAnimationFrame(update);
}

// Observer les changements de valeur
watch(() => props.value, (newValue, oldValue) => {
    // Si oldValue est undefined (premier chargement), on part de 0 pour faire une intro sympa
    animate(oldValue ?? 0, newValue);
}, { immediate: true });

const formattedValue = computed(() => {
    return props.prefix + displayValue.value.toFixed(props.precision) + props.suffix;
});
</script>

<template>
  <span>{{ formattedValue }}</span>
</template>
