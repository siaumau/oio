<template>
  <div class="columns-management">
    <!-- 頭部 -->
    <div class="header">
      <div class="header-left">
        <button class="btn-back" @click="goBackToDashboard">← 返回看板</button>
        <h1>⚙️ 欄位管理</h1>
      </div>
    </div>

    <!-- 主要內容 -->
    <div class="main-content">
      <div class="columns-container">
        <!-- 欄位列表（可拖拽排序） -->
        <div class="columns-list-wrapper">
          <h2>欄位列表</h2>
          <VueDraggable
            v-model="columnsCopy"
            class="columns-list"
            :options="dragOptions"
            @change="onColumnsReordered"
          >
            <div
              v-for="(col, index) in columnsCopy"
              :key="col.id"
              class="column-item"
            >
              <div class="column-item-content">
                <!-- 拖拽句柄 -->
                <span class="drag-handle">⋮⋮</span>

                <!-- 編輯模式 vs 顯示模式 -->
                <template v-if="editingColumnId === col.id">
                  <input
                    v-model="editingColumnName"
                    type="text"
                    class="column-name-input"
                    @keyup.enter="saveColumnName(col)"
                    @keyup.escape="cancelEdit"
                    autofocus
                  />
                </template>
                <template v-else>
                  <span class="column-name">{{ col.name }}</span>
                  <span class="column-order">#{{ index + 1 }}</span>
                </template>

                <!-- 編輯按鈕 -->
                <button
                  v-if="editingColumnId !== col.id"
                  class="btn-edit"
                  @click="startEditColumn(col)"
                  title="編輯欄位名稱"
                >
                  ✏️
                </button>

                <!-- 儲存/取消按鈕（編輯模式） -->
                <template v-else>
                  <button class="btn-save" @click="saveColumnName(col)">
                    ✓
                  </button>
                  <button class="btn-cancel" @click="cancelEdit">
                    ✕
                  </button>
                </template>

                <!-- 刪除按鈕（非預設欄位） -->
                <button
                  v-if="!isDefaultColumn(col.name) && editingColumnId !== col.id"
                  class="btn-delete"
                  @click="handleDeleteColumn(col.id)"
                  title="刪除欄位"
                >
                  🗑️
                </button>
              </div>
            </div>
          </VueDraggable>
        </div>

        <!-- 新增欄位區塊 -->
        <div class="add-column-wrapper">
          <h2>新增欄位</h2>
          <div class="add-column-form">
            <input
              v-model="newColumnName"
              type="text"
              placeholder="輸入新欄位名稱"
              class="column-name-input"
              @keyup.enter="handleAddColumn"
            />
            <button class="btn btn-primary" @click="handleAddColumn">
              ➕ 新增
            </button>
          </div>
        </div>
      </div>

      <!-- 說明文字 -->
      <div class="info-section">
        <h3>⚠️ 提示</h3>
        <ul>
          <li>使用 ⋮⋮ 句柄拖拽欄位來調整順序</li>
          <li>預設欄位（待做、進行中、已完成、暫停）無法刪除</li>
          <li>刪除欄位後，該欄位中的工作將自動移到「待做」欄位</li>
          <li>編輯後系統將自動保存</li>
        </ul>
      </div>

      <!-- 錯誤提示 -->
      <div v-if="taskStore.error" class="error-message">
        {{ taskStore.error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTaskStore } from '../stores/taskStore.js'
import { VueDraggable } from 'vue-draggable-plus'

const taskStore = useTaskStore()

// 狀態
const columnsCopy = ref([]) // 本地副本，用於拖拽排序
const editingColumnId = ref(null)
const editingColumnName = ref('')
const newColumnName = ref('')
const isReordering = ref(false)

// 計算屬性
const defaultColumnNames = computed(() => [
  '待做',
  '進行中',
  '已完成',
  '暫停'
])

// 拖拽配置
const dragOptions = {
  animation: 150,
  ghostClass: 'dragging-ghost',
  dragClass: 'dragging'
}

// 方法
const isDefaultColumn = (columnName) => {
  return defaultColumnNames.value.includes(columnName)
}

const startEditColumn = (col) => {
  editingColumnId.value = col.id
  editingColumnName.value = col.name
}

const cancelEdit = () => {
  editingColumnId.value = null
  editingColumnName.value = ''
}

const saveColumnName = async (col) => {
  if (!editingColumnName.value.trim()) {
    taskStore.error = '欄位名稱不能為空'
    return
  }

  if (editingColumnName.value === col.name) {
    cancelEdit()
    return
  }

  const success = await taskStore.updateColumn(col.id, editingColumnName.value)

  if (success) {
    // 先更新本地副本（重要：在退出編輯模式之前）
    const local = columnsCopy.value.find(c => c.id === col.id)
    if (local) {
      local.name = editingColumnName.value
    }
    // 再退出編輯模式（此時 UI 會顯示新的名稱）
    cancelEdit()
  }
}

const onColumnsReordered = async () => {
  if (isReordering.value) return
  isReordering.value = true

  try {
    // 保存舊的順序，以備回滾
    const oldOrder = columnsCopy.value.map(col => ({ ...col }))

    // 準備更新數據
    const updates = columnsCopy.value.map((col, index) => ({
      id: col.id,
      col_order: index + 1
    }))

    // 立即更新本地副本中的 col_order（樂觀更新）
    columnsCopy.value.forEach((col, index) => {
      col.col_order = index + 1
    })

    // 呼叫 reorderColumns 方法
    const success = await taskStore.reorderColumns(updates)

    if (success) {
      // 已經在 reorderColumns 中更新 taskStore.columns 了
      // 確保 taskStore 也是正確的順序
      taskStore.columns.sort((a, b) => a.col_order - b.col_order)
    } else {
      // 如果失敗，恢復舊順序
      columnsCopy.value = oldOrder
    }
  } finally {
    isReordering.value = false
  }
}

const handleAddColumn = async () => {
  if (!newColumnName.value.trim()) {
    taskStore.error = '欄位名稱不能為空'
    return
  }

  const success = await taskStore.addColumn(newColumnName.value)

  if (success) {
    newColumnName.value = ''
    // 刷新副本列表
    columnsCopy.value = [...taskStore.columns].sort(
      (a, b) => a.col_order - b.col_order
    )
  }
}

const handleDeleteColumn = async (columnId) => {
  const column = taskStore.columns.find(c => c.id === columnId)

  if (column && isDefaultColumn(column.name)) {
    alert('預設欄位無法刪除')
    return
  }

  if (taskStore.columns.length <= 1) {
    alert('至少需要保留一個欄位')
    return
  }

  if (
    confirm(
      '刪除該欄位後，其中的工作將移到「待做」欄位，確定刪除嗎？'
    )
  ) {
    const success = await taskStore.deleteColumn(columnId)

    if (success) {
      // 刷新副本列表
      columnsCopy.value = columnsCopy.value.filter(c => c.id !== columnId)
    }
  }
}

const goBackToDashboard = () => {
  window.$router.push('dashboard')
}

// 頁面初始化
onMounted(async () => {
  // 確保已經獲取欄位
  if (taskStore.columns.length === 0) {
    await taskStore.fetchColumns()
  }

  // 複製欄位數據到本地副本（用於拖拽排序）
  columnsCopy.value = [...taskStore.columns].sort(
    (a, b) => a.col_order - b.col_order
  )
})
</script>

<style scoped>
.columns-management {
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
  overflow: hidden;
}

.header {
  background-color: white;
  padding: 20px 24px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-back {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
  color: #0066cc;
  padding: 8px 12px;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.btn-back:hover {
  background-color: #f0f0f0;
  color: #0052a3;
}

.header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

.main-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.columns-container {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

/* 欄位列表 */
.columns-list-wrapper {
  background-color: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.columns-list-wrapper h2 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.columns-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.column-item {
  padding: 12px;
  background-color: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.column-item:hover {
  background-color: #f0f0f0;
  border-color: #0066cc;
}

.column-item.dragging-ghost {
  opacity: 0.5;
  background-color: #e3f2fd;
  border-color: #0066cc;
}

.column-item-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.drag-handle {
  cursor: grab;
  color: #999;
  user-select: none;
  font-weight: bold;
  font-size: 14px;
}

.drag-handle:active {
  cursor: grabbing;
}

.column-name {
  flex: 1;
  font-size: 16px;
  color: #333;
  font-weight: 500;
}

.column-order {
  font-size: 12px;
  color: #999;
  background-color: #f0f0f0;
  padding: 4px 8px;
  border-radius: 4px;
}

.column-name-input {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid #0066cc;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
}

.column-name-input:focus {
  outline: none;
  border-color: #0052a3;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.btn-edit,
.btn-save,
.btn-cancel,
.btn-delete {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 6px 8px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-edit {
  color: #0066cc;
}

.btn-edit:hover {
  background-color: #f0f0f0;
}

.btn-save {
  color: #4caf50;
  font-size: 18px;
}

.btn-save:hover {
  background-color: #e8f5e9;
}

.btn-cancel {
  color: #999;
  font-size: 18px;
}

.btn-cancel:hover {
  background-color: #f0f0f0;
}

.btn-delete {
  color: #f44336;
}

.btn-delete:hover {
  background-color: #ffebee;
}

/* 新增欄位 */
.add-column-wrapper {
  background-color: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  height: fit-content;
}

.add-column-wrapper h2 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.add-column-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.add-column-form .column-name-input {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  width: 100%;
}

.add-column-form .column-name-input:focus {
  border-color: #0066cc;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
  outline: none;
}

.add-column-form .btn {
  width: 100%;
}

/* 按鈕樣式 */
.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background-color: #0066cc;
  color: white;
}

.btn-primary:hover {
  background-color: #0052a3;
}

.btn-primary:active {
  background-color: #003d7a;
}

/* 說明區塊 */
.info-section {
  background-color: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 24px;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
}

.info-section h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #f57c00;
}

.info-section ul {
  margin: 0;
  padding-left: 24px;
  color: #666;
  font-size: 14px;
  line-height: 1.8;
}

.info-section li {
  margin-bottom: 8px;
}

/* 錯誤提示 */
.error-message {
  background-color: #ffebee;
  color: #f44336;
  padding: 12px 16px;
  border-radius: 6px;
  margin-top: 16px;
  border-left: 4px solid #f44336;
  font-size: 14px;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .columns-container {
    grid-template-columns: 1fr;
  }

  .main-content {
    padding: 16px;
  }

  .header {
    padding: 16px;
  }

  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .header h1 {
    font-size: 20px;
  }
}
</style>
