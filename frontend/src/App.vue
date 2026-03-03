<template>
  <div id="app">
    <component :is="currentComponent" />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useUserStore } from './stores/userStore.js'
import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'
import HistoryView from './views/HistoryView.vue'
import ColumnsManagementView from './views/ColumnsManagementView.vue'

const userStore = useUserStore()
const currentRoute = ref(window.location.hash.slice(1) || 'dashboard')
const isInitialized = ref(false)

const currentComponent = computed(() => {
  if (!isInitialized.value) {
    return LoginView // 初始化中，先顯示登入頁
  }

  if (!userStore.isLoggedIn) {
    return LoginView
  }

  switch (currentRoute.value) {
    case 'history':
      return HistoryView
    case 'columns':
      return ColumnsManagementView
    case 'dashboard':
    default:
      return DashboardView
  }
})

onMounted(async () => {
  // 嘗試恢復會話
  await userStore.restoreSession()
  isInitialized.value = true

  // 監聽 hash 變化
  window.addEventListener('hashchange', () => {
    currentRoute.value = window.location.hash.slice(1) || 'dashboard'
  })
})

// 提供全域的路由函數
window.$router = {
  push: (path) => {
    window.location.hash = path
  }
}
</script>

<style scoped>
#app {
  width: 100%;
  height: 100vh;
}
</style>
