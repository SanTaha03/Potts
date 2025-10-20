<template>
    <div>
        <FirstComponent></FirstComponent>
        <a href="/api/devices">View Devices </a>
        <div v-if="loading">Loading...</div>
        <ul v-else>
            <li v-for="item in items" :key="item.id">
                {{ item.alias || item.serial }} - Status: {{ item.status }}
            </li>
        </ul>

    </div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from 'vue'
import FirstComponent from './components/FirstComponent.vue'
import { api } from './services/api'
const loading = ref(false)
const items = ref([])

onMounted(async () => {
    loading.value = true
    const { data } = await api.get('/devices')
    items.value = data.data ?? []
    loading.value = false
})
</script>

<style>
    /* Add your styles here */
</style>