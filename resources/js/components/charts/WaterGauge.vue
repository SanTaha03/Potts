<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

interface Props {
  value: number; // current level
  max?: number; // maximum value, defaults to 1
  size?: number; // px size of the square container
  strokeWidth?: number; // thickness of the ring
  backgroundStroke?: string;
  gradientFrom?: string;
  gradientTo?: string;
  centerIcon?: string; // optional iconify name; if empty and showLabel true, shows %
  showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  max: 1,
  size: 32,
  strokeWidth: 8,
  backgroundStroke: '#ede4df',
  gradientFrom: '#7fb4f6',
  gradientTo: '#57a6f2',
  centerIcon: 'ph:drop-duotone',
  showLabel: false,
});

const radius = 45;
const circumference = 2 * Math.PI * radius;
const gradientId = `waterGaugeGradient-${Math.random().toString(36).slice(2, 8)}`;

const clampedValue = computed(() => Math.min(Math.max(props.value, 0), props.max));
const dashOffset = computed(() => circumference - (clampedValue.value / props.max) * circumference);
const percentText = computed(() => Math.round((clampedValue.value / props.max) * 100));
</script>

<template>
  <div class="relative" :style="{ width: `${size}px`, height: `${size}px` }">
    <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
      <circle
        cx="50"
        cy="50"
        :r="radius"
        fill="none"
        :stroke="backgroundStroke"
        :stroke-width="strokeWidth"
      />
      <circle
        cx="50"
        cy="50"
        :r="radius"
        fill="none"
        :stroke="`url(#${gradientId})`"
        :stroke-width="strokeWidth"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        stroke-linecap="round"
        class="transition-all duration-300"
      />
      <defs>
        <linearGradient :id="gradientId" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" :stop-color="gradientFrom" />
          <stop offset="100%" :stop-color="gradientTo" />
        </linearGradient>
      </defs>
    </svg>
    <div class="absolute inset-0 flex items-center justify-center">
      <slot>
        <Icon v-if="centerIcon && !showLabel" :icon="centerIcon" class="h-4 w-4 text-blue-500" />
        <span v-else-if="showLabel" class="text-[10px] font-semibold text-black">{{ percentText }}%</span>
      </slot>
    </div>
  </div>
</template>
