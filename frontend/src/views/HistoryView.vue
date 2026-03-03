<template>
  <div class="history-container">
    <!-- 頭部 -->
    <div class="history-header">
      <div class="header-left">
        <h1>📋 工作異動記錄</h1>
        <p class="subtitle">查看所有工作狀態變化記錄</p>
      </div>
      <button class="btn btn-secondary" @click="goToDashboard">← 返回工作記錄</button>
    </div>

    <!-- 篩選器 -->
    <div class="filter-section">
      <div class="filter-group">
        <label for="filter-date">篩選日期範圍：</label>
        <div class="date-range">
          <input
            id="filter-start-date"
            v-model="filterStartDate"
            type="date"
            @change="loadHistory"
            class="date-input"
          />
          <span class="separator">至</span>
          <input
            id="filter-end-date"
            v-model="filterEndDate"
            type="date"
            @change="loadHistory"
            class="date-input"
          />
        </div>
      </div>
      <div class="filter-group">
        <label for="filter-status">篩選新狀態：</label>
        <select
          v-model="filterStatus"
          @change="loadHistory"
          class="status-select"
        >
          <option value="">全部狀態</option>
          <option value="待做">待做</option>
          <option value="進行中">進行中</option>
          <option value="已完成">已完成</option>
          <option value="暫停">暫停</option>
        </select>
      </div>
    </div>

    <!-- 加載狀態 -->
    <div v-if="loading" class="loading">
      加載中...
    </div>

    <!-- 歷史記錄表 -->
    <div v-else class="history-table-container">
      <table class="history-table">
        <thead>
          <tr>
            <th>工作標題</th>
            <th>舊狀態</th>
            <th class="arrow-cell">→</th>
            <th>新狀態</th>
            <th>變更時間</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in filteredRecords" :key="record.id" class="history-row">
            <td class="title-cell">{{ record.task_title }}</td>
            <td class="status-cell">
              <span class="status-badge" :class="getStatusClass(record.old_status)">
                {{ record.old_status || '—' }}
              </span>
            </td>
            <td class="arrow-cell">→</td>
            <td class="status-cell">
              <span class="status-badge" :class="getStatusClass(record.new_status)">
                {{ record.new_status }}
              </span>
            </td>
            <td class="time-cell">{{ formatDateTime(record.changed_at) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- 空狀態 -->
      <div v-if="records.length === 0" class="empty-state">
        <p>📭 暫無異動記錄</p>
      </div>

      <!-- 分頁 -->
      <div v-if="totalPages > 1" class="pagination">
        <button
          :disabled="currentPage === 1"
          @click="currentPage--"
          class="btn btn-secondary"
        >
          ← 上一頁
        </button>
        <span class="page-info">
          第 {{ currentPage }} / {{ totalPages }} 頁 (共 {{ total }} 筆記錄)
        </span>
        <button
          :disabled="currentPage === totalPages"
          @click="currentPage++"
          class="btn btn-secondary"
        >
          下一頁 →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useUserStore } from '../stores/userStore.js'

const userStore = useUserStore()

const loading = ref(false)
const records = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = 20

const filterStartDate = ref(getDefaultStartDate())
const filterEndDate = ref(new Date().toISOString().split('T')[0])
const filterStatus = ref('')

function getDefaultStartDate() {
  const date = new Date()
  date.setDate(date.getDate() - 30)
  return date.toISOString().split('T')[0]
}

const totalPages = computed(() => Math.ceil(total.value / pageSize))

const filteredRecords = computed(() => {
  return records.value.filter(record => {
    if (filterStatus.value && record.new_status !== filterStatus.value) {
      return false
    }
    return true
  })
})

async function loadHistory() {
  loading.value = true
  try {
    const offset = (currentPage.value - 1) * pageSize
    const response = await fetch(
      `http://localhost:6001/api/tasks?action=history&limit=${pageSize}&offset=${offset}`,
      {
        method: 'GET',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
      }
    )

    const data = await response.json()

    if (!data.success) {
      throw new Error(data.message)
    }

    records.value = data.data.records || []
    total.value = data.data.total || 0
  } catch (error) {
    console.error('加載歷史記錄失敗:', error)
    records.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function getStatusClass(status) {
  switch (status) {
    case '待做':
      return 'status-todo'
    case '進行中':
      return 'status-progress'
    case '已完成':
      return 'status-completed'
    case '暫停':
      return 'status-suspended'
    default:
      return ''
  }
}

function formatDateTime(dateStr) {
  const date = new Date(dateStr)
  return date.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

watch(currentPage, () => {
  loadHistory()
})

const goToDashboard = () => {
  window.location.hash = '#dashboard'
}

onMounted(() => {
  loadHistory()
})
</script>

<style scoped>
.history-container {
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100vh;
  background-color: var(--color-gray-50);
  padding: var(--spacing-lg);
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg) var(--spacing-xl);
  background: linear-gradient(135deg, #ffffff 0%, #f7f9fc 100%);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: var(--spacing-lg);
  border: 1px solid var(--color-gray-200);
}

.header-left h1 {
  margin: 0 0 6px 0;
  color: var(--color-gray-900);
  font-size: 24px;
  font-weight: 600;
}

.subtitle {
  margin: 0;
  color: var(--color-gray-500);
  font-size: 13px;
}

.filter-section {
  display: flex;
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  background-color: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: var(--spacing-lg);
  border: 1px solid var(--color-gray-200);
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.filter-group label {
  color: var(--color-gray-700);
  font-weight: 500;
  font-size: 13px;
}

.date-range {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.date-input {
  padding: 8px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  font-size: 13px;
  transition: all 0.25s ease;
}

.date-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.08);
}

.separator {
  color: var(--color-gray-400);
  font-weight: 500;
}

.status-select {
  padding: 8px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  font-size: 13px;
  background-color: white;
  cursor: pointer;
  transition: all 0.25s ease;
}

.status-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.08);
}

.history-table-container {
  background-color: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-gray-200);
  overflow: hidden;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.history-table thead {
  background: linear-gradient(135deg, var(--color-gray-100) 0%, var(--color-gray-50) 100%);
  border-bottom: 1px solid var(--color-gray-300);
}

.history-table th {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  font-weight: 600;
  color: var(--color-gray-700);
  white-space: nowrap;
}

.history-table tbody tr {
  border-bottom: 1px solid var(--color-gray-200);
  transition: all 0.2s ease;
}

.history-table tbody tr:hover {
  background-color: var(--color-gray-50);
}

.history-table td {
  padding: var(--spacing-md) var(--spacing-lg);
}

.title-cell {
  max-width: 300px;
  word-break: break-word;
  color: var(--color-gray-900);
  font-weight: 500;
}

.status-cell {
  white-space: nowrap;
}

.arrow-cell {
  text-align: center;
  color: var(--color-gray-400);
  width: 30px;
}

.time-cell {
  color: var(--color-gray-500);
  white-space: nowrap;
  font-size: 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 500;
}

.status-todo {
  background-color: #f0f2f7;
  color: var(--color-gray-700);
}

.status-progress {
  background-color: rgba(0, 102, 204, 0.1);
  color: var(--color-primary);
}

.status-completed {
  background-color: rgba(0, 168, 84, 0.1);
  color: var(--color-success);
}

.status-suspended {
  background-color: rgba(255, 122, 69, 0.1);
  color: var(--color-warning);
}

.empty-state {
  padding: var(--spacing-xl);
  text-align: center;
  color: var(--color-gray-500);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  border-top: 1px solid var(--color-gray-200);
  background-color: var(--color-gray-50);
}

.page-info {
  color: var(--color-gray-600);
  font-size: 13px;
  min-width: 150px;
  text-align: center;
}

.loading {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--color-gray-600);
}
</style>
