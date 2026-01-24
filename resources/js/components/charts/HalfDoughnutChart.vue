<script setup lang="ts">
import { computed } from "vue";

interface ChartData {
  label: string;
  value: number;
  color: string;
}

interface Props {
  data: ChartData[];
  radius?: number;
  strokeWidth?: number;
  labelOffset?: number; // distance des labels par rapport à l'arc
  padding?: number;     // marge interne pour éviter que le stroke soit coupé
}

const props = withDefaults(defineProps<Props>(), {
  radius: 150,
  strokeWidth: 35,
  labelOffset: 18,
  padding: 0,
});

// Calcul du total des valeurs
const total = computed(() =>
  props.data.reduce((sum, item) => sum + item.value, 0)
);

// Dimensions du viewBox (demi-donut "arc-en-ciel")
const width = computed(() => props.radius * 2 + props.strokeWidth * 2 + props.padding * 2);

// hauteur = demi-cercle + épaisseur + padding
const height = computed(() => props.radius + props.strokeWidth * 2 + props.padding * 2);

// Centre du cercle dans le viewBox
const cx = computed(() => width.value / 2);
const cy = computed(() => props.radius + props.strokeWidth + props.padding);

// Circonférence complète
const circumference = computed(() => 2 * Math.PI * props.radius);

// Segments (angles sur 180°)
const segments = computed(() => {
  const t = total.value || 1; // évite division par 0
  return props.data.map((item) => {
    const angle = (item.value / t) * 180;
    return {
      ...item,
      angle,
      percentage: ((item.value / t) * 100).toFixed(1),
    };
  });
});

// Stroke dasharray / dashoffset
const segmentStrokeDasharray = (angle: number) =>
  `${(angle / 360) * circumference.value} ${circumference.value}`;

const segmentStrokeDashoffset = (index: number) => {
  const prevAngles = segments.value
    .slice(0, index)
    .reduce((sum, s) => sum + s.angle, 0);

  return `-${(prevAngles / 360) * circumference.value}`;
};

// Labels positionnés selon cx/cy dynamiques
const labelPositions = computed(() => {
  const labelRadius = props.radius + props.strokeWidth + props.labelOffset;
  let currentAngle = 180; //  pour avoir les labels sur l'arc en haut (gauche -> droite)

  return segments.value.map((segment) => {
    const midAngle = currentAngle - segment.angle / 2; // on avance de gauche vers droite
    const rad = (midAngle * Math.PI) / 180;

    const x = cx.value + labelRadius * Math.cos(rad);
    const y = (cy.value - labelRadius * Math.sin(rad)); // y vers le haut

    currentAngle -= segment.angle;

    return { x, y, label: segment.label, percentage: segment.percentage };
  });
});
</script>

<template>
  <div class="flex flex-col items-center gap-4">
    <svg :viewBox="`0 0 ${width} ${height}`" class="w-full max-w-sm h-40">
      
      <g :transform="`translate(${cx}, ${cy}) scale(1,-1)`">
        <!-- Background arc (demi-cercle) -->
        <circle
          cx="0"
          cy="0"
          :r="radius"
          fill="none"
          :stroke-width="strokeWidth"
          stroke="#ede4df"
          :stroke-dasharray="`${circumference / 2} ${circumference}`"
          stroke-dashoffset="0"
          stroke-linecap="round"
        />

        <!-- Segments -->
        <circle
          v-for="(segment, index) in segments"
          :key="`segment-${index}`"
          cx="0"
          cy="0"
          :r="radius"
          fill="none"
          :stroke="segment.color"
          :stroke-width="strokeWidth"
          :stroke-dasharray="segmentStrokeDasharray(segment.angle)"
          :stroke-dashoffset="segmentStrokeDashoffset(index)"
        :stroke-linecap="index === segments.length - 1 ? 'round' : 'butt'"
          class="transition-all duration-300"
        />
      </g>

      <!-- Labels (non inversés) -->
      <text
        v-for="(label, index) in labelPositions"
        :key="`label-${index}`"
        :x="label.x"
        :y="label.y"
        text-anchor="middle"
        dominant-baseline="middle"
        class="fill-black text-[10px] font-semibold"
      >
        {{ label.label }}
      </text>
    </svg>
  </div>
</template>
