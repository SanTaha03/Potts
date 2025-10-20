<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { login, getDevices } from '@/services/api'

const items = ref<any[]>([])
const err = ref<string|null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    await login('admin@demo.test','password') // dev only
    const res = await getDevices()
    items.value = res.data ?? res
  } catch (e:any) {
    err.value = e?.response?.data?.message || e?.message
  } finally { loading.value = false }
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl font-semibold">Devices</h1>
    <div v-if="loading">Chargement…</div>
    <div v-else-if="err" class="text-red-600">{{ err }}</div>
    <ul v-else>
      <li v-for="d in items" :key="d.id">{{ d.alias || d.serial }}</li>
    </ul>
  </div>
</template>
