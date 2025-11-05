<script setup lang="ts">
import type { Device, DeviceLocation, DeviceStatus } from '@/types/device';

defineProps<{
  devices: Device[];
}>();

const statusClasses: Record<DeviceStatus, string> = {
  active: 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200',
  inactive: 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
  archived: 'bg-rose-100 text-rose-600 ring-1 ring-inset ring-rose-200',
};

function formatLocation(location: DeviceLocation | null): string {
  if (!location) {
    return 'Localisation inconnue';
  }

  const parts: Array<string> = [];
  if (location.site) {
    parts.push(location.site);
  }
  if (location.floor !== undefined && location.floor !== null) {
    parts.push(`Étage ${location.floor}`);
  }
  if (location.zone) {
    parts.push(location.zone);
  }

  return parts.length > 0 ? parts.join(' · ') : 'Localisation inconnue';
}

function statusClass(status: DeviceStatus): string {
  return statusClasses[status] ?? statusClasses.active;
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  });
}
</script>

<template>
  <ul class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <li
      v-for="device in devices"
      :key="device.id"
      class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <p class="font-medium text-slate-900">
          <span>{{ device.alias || 'Sans alias' }}</span>
          <span class="ml-2 text-sm text-slate-500">#{{ device.serial }}</span>
        </p>
        <p class="mt-1 text-sm text-slate-600">
          {{ formatLocation(device.location) }}
        </p>
        <p class="mt-2 text-xs text-slate-400">
          Créé le {{ formatDate(device.created_at) }}
          <span v-if="device.readings_count !== undefined">· {{ device.readings_count }} mesures</span>
        </p>
      </div>
      <span
        :class="[
          'inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide',
          statusClass(device.status),
        ]"
      >
        {{ device.status }}
      </span>
    </li>
  </ul>
</template>
