<script setup lang="ts">
import { Icon } from '@iconify/vue';

defineProps<{
  modelValue: boolean;
  title?: string;
  showClose?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'close'): void;
}>();

const close = () => {
  emit('update:modelValue', false);
  emit('close');
};
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-end justify-center"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div 
        class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" 
        @click="close"
      ></div>

      <!-- Sheet Container -->
      <div 
        class="relative w-full max-w-lg rounded-t-[32px] bg-white p-6 shadow-2xl transition-transform duration-300 ease-out transform translate-y-0"
      >
        <!-- Handle for dragging (visual only for now) -->
        <div class="mx-auto mb-6 h-1 w-12 rounded-full bg-gray-200"></div>

        <!-- Header -->
        <div v-if="title || showClose" class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                 <slot name="header-icon"></slot>
                 <h3 v-if="title" class="text-2xl font-bold text-[#2F2C36]">{{ title }}</h3>
            </div>
          
          <button 
            v-if="showClose" 
            type="button" 
            class="rounded-full p-2 hover:bg-gray-100" 
            @click="close"
          >
            <Icon icon="ph:x" class="h-6 w-6 text-[#2F2C36]" />
          </button>
        </div>

        <!-- Content -->
        <div class="max-h-[85vh] overflow-y-auto">
          <slot></slot>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* Add transition classes if using <Transition> component later, 
   but for now v-if is sufficient for the structure. 
   Ideally we would wrap with <Transition> for smooth enter/leave.
*/
</style>