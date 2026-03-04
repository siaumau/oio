<template>
  <div class="dashboard">
    <!-- 头部 -->
    <div class="header">
      <div class="header-left">
        <h1>📝 OIO - 工作記錄</h1>
        <p class="welcome">歡迎, {{ userStore.user.username }}!</p>
      </div>
      <div class="header-actions">
        <button v-if="!taskStore.isAdmin" class="btn btn-secondary" @click="goToColumnsManagement">
          ⚙️ 管理欄位
        </button>
        <button v-if="!taskStore.isAdmin" class="btn btn-secondary" @click="goToHistory">
          📋 異動記錄
        </button>
        <button class="btn btn-secondary" @click="showChangePasswordModal = true">
          🔐 更改密碼
        </button>
        <button class="btn btn-secondary" @click="userStore.logout">
          登出
        </button>
      </div>
    </div>

    <!-- 主要内容 -->
    <div class="main-content">
      <!-- 视图切换 Tab -->
      <div class="view-tabs-container">
        <div class="view-tabs">
          <button
            :class="{ active: viewMode === 'day' }"
            @click="viewMode = 'day'"
            class="view-tab-btn"
          >
            日
          </button>
          <button
            :class="{ active: viewMode === 'week' }"
            @click="viewMode = 'week'"
            class="view-tab-btn"
          >
            周
          </button>
          <button
            :class="{ active: viewMode === 'month' }"
            @click="viewMode = 'month'"
            class="view-tab-btn"
          >
            月
          </button>
        </div>

        <!-- 展开控制按钮 -->
        <button
          v-if="!taskStore.isAdmin"
          :class="{ active: autoExpandAll }"
          @click="toggleAutoExpand"
          class="btn expand-control-btn"
          :title="autoExpandAll ? '點擊停用全展開' : '點擊啟用全展開'"
        >
          {{ autoExpandAll ? '✓ 常態展開' : '常態展開' }}
        </button>
      </div>

      <!-- 日期导航和工具栏 -->
      <div class="toolbar">
        <div class="date-navigator">
          <!-- 日视图导航 -->
          <template v-if="viewMode === 'day'">
            <button class="btn btn-secondary" @click="goToPreviousDay">← 上一天</button>
            <div class="date-with-weekday">
              <input
                v-model="selectedDate"
                type="date"
                @change="handleDateChange"
                class="date-input"
              />
              <span class="weekday-display">{{ selectedDateWeekday }}</span>
            </div>
            <button class="btn btn-secondary" @click="goToToday">今天</button>
            <button class="btn btn-secondary" @click="goToNextDay">下一天 →</button>
          </template>

          <!-- 周视图导航 -->
          <template v-if="viewMode === 'week'">
            <button class="btn btn-secondary" @click="goToPreviousWeek">← 上一周</button>
            <span class="date-display">{{ weekRangeDisplay }} <span class="weekday-info">{{ weekRangeWeekdays }}</span></span>
            <button class="btn btn-secondary" @click="goToThisWeek">本周</button>
            <button class="btn btn-secondary" @click="goToNextWeek">下一周 →</button>
          </template>

          <!-- 月视图导航 -->
          <template v-if="viewMode === 'month'">
            <button class="btn btn-secondary" @click="goToPreviousMonth">← 上一月</button>
            <span class="date-display">{{ monthTitle }}</span>
            <button class="btn btn-secondary" @click="goToThisMonth">本月</button>
            <button class="btn btn-secondary" @click="goToNextMonth">下一月 →</button>
          </template>
        </div>

        <button v-if="!taskStore.isAdmin" class="btn btn-primary" @click="showCreateTaskModal = true">
          ➕ 新建工作
        </button>
      </div>

      <!-- 加载状态 -->
      <div v-if="taskStore.loading" class="loading">
        加載中...
      </div>

      <!-- 日视图 - 看板 -->
      <div v-if="!taskStore.loading && viewMode === 'day'" class="day-view" @click="closeAllExpandedCards">
        <div class="kanban-board">
          <!-- 动态栏位列 -->
          <div
            v-for="col in taskStore.columns"
            :key="col.id"
            class="kanban-column"
          >
            <div class="column-header">
              <span>{{ col.name }}</span>
            </div>

            <!-- 任务列表 - @drop 和 @dragover 都在这里 -->
            <div
              class="task-list"
              @dragover.prevent="onDragOver"
              @drop="onDrop($event, col.name)"
            >
              <div
                v-for="task in taskStore.tasksByStatus[col.name]"
                :key="task.id"
                :class="['task-card', { completed: col.name === '已完成', suspended: col.name === '暫停', expanded: expandedTasks.has(task.id), readonly: taskStore.isAdmin }]"
                :draggable="!taskStore.isAdmin"
                @dragstart="!taskStore.isAdmin && onDragStart($event, task, col.name)"
                @dragenter="!taskStore.isAdmin && onDragEnter"
                @dragleave="!taskStore.isAdmin && onDragLeave"
                @dragend="isDragging = false"
                @click.stop="!isDragging && handleTaskCardClick(task)"
              >
                <div class="task-title">
                  <span v-if="taskStore.isAdmin && task.username">👤 {{ task.username }} - </span>
                  {{ task.title }}
                </div>

                <!-- 展開的內容 -->
                <template v-if="expandedTasks.has(task.id)">
                  <div v-if="task.description" class="task-description">
                    <div v-html="sanitizeHtml(task.description)"></div>
                  </div>
                  <div v-if="task.duration > 0" class="task-duration">
                    ⏱️ {{ task.duration }}h
                  </div>
                  <div class="task-actions">
                    <button v-if="!taskStore.isAdmin" class="edit-btn" @click.stop="openEditTaskModal(task)">
                      ✏️ 編輯
                    </button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 周视图 -->
      <div v-if="!taskStore.loading && viewMode === 'week'" class="week-view">
        <div class="week-grid">
          <div
            v-for="(day, index) in weekDays"
            :key="index"
            :class="['week-day-card', { today: day.isToday, 'has-tasks': day.taskCount > 0 }]"
            @click="switchToDay(day.dateStr)"
          >
            <!-- 今日標記 -->
            <div v-if="day.isToday" class="today-badge">今日</div>

            <!-- 日期信息 -->
            <div class="week-day-header">
              <div class="week-day-date">{{ day.displayDate }}</div>
              <div class="week-day-label">{{ day.label }}</div>
            </div>

            <!-- 任務計數與進度 -->
            <div class="week-day-stats">
              <div class="stat-item">
                <span class="stat-label">任務</span>
                <span class="stat-value">{{ day.taskCount }}</span>
              </div>
              <div class="stat-divider"></div>
              <div class="stat-item">
                <span class="stat-label">完成</span>
                <span class="stat-value">{{ day.completedCount }}</span>
              </div>
            </div>

            <!-- 進度條 -->
            <div v-if="day.taskCount > 0" class="progress-container">
              <div class="progress-bar">
                <div
                  class="progress-fill"
                  :style="{ width: getProgressPercentage(day.completedCount, day.taskCount) + '%' }"
                ></div>
              </div>
              <div class="progress-text">
                {{ Math.round(getProgressPercentage(day.completedCount, day.taskCount)) }}%
              </div>
            </div>

            <!-- 任務狀態指示 -->
            <div v-if="day.taskCount > 0" class="task-status-indicators">
              <div class="pending" v-if="day.taskCount - day.completedCount > 0">
                <span class="dot pending-dot"></span>
                <span class="text">{{ day.taskCount - day.completedCount }} 待做</span>
              </div>
              <div v-if="day.completedCount > 0" class="completed">
                <span class="dot completed-dot"></span>
                <span class="text">{{ day.completedCount }} 完成</span>
              </div>
            </div>

            <!-- 空狀態 -->
            <div v-else class="empty-state">
              <span class="empty-icon">📭</span>
              <span class="empty-text">無任務</span>
            </div>

            <!-- 查看詳情提示 -->
            <div class="view-hint">點擊查看詳情</div>
          </div>
        </div>
      </div>

      <!-- 月视图 -->
      <div v-if="!taskStore.loading && viewMode === 'month'" class="month-view">
        <div class="month-grid">
          <div v-for="dayLabel in weekLabels" :key="dayLabel" class="month-day-label">
            {{ dayLabel }}
          </div>

          <div
            v-for="(day, index) in monthDays"
            :key="index"
            :class="['month-day', { other: !day.currentMonth, today: day.isToday, 'has-tasks': day.taskCount > 0 }]"
            @click="day.currentMonth && switchToDay(day.dateStr)"
          >
            <div class="month-day-number">{{ day.day }}</div>

            <!-- 當有任務時顯示完成比例 -->
            <div v-if="day.currentMonth && day.taskCount > 0" class="month-day-content">
              <div class="task-info">
                <span class="task-total">{{ day.taskCount }}</span>
                <span class="task-label">個工作</span>
              </div>

              <!-- 完成比例進度條 -->
              <div class="completion-container">
                <div class="progress-bar-small">
                  <div
                    class="progress-fill-small"
                    :style="{ width: getProgressPercentage(day.completedCount, day.taskCount) + '%' }"
                  ></div>
                </div>
                <span class="completion-text">
                  {{ day.completedCount }}/{{ day.taskCount }}
                </span>
              </div>

              <!-- 狀態指示 -->
              <div class="completion-status">
                <span v-if="day.completedCount === day.taskCount" class="status-badge all-done">✓ 已完成</span>
                <span v-else-if="day.completedCount > 0" class="status-badge partial">進行中</span>
                <span v-else class="status-badge not-started">⭕ 未開始</span>
              </div>
            </div>

            <!-- 無任務狀態 - 留白 -->
            <div v-else-if="day.currentMonth && day.taskCount === 0" class="month-day-empty">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 新建工作模態框 -->
    <div v-if="showCreateTaskModal" class="modal-overlay" @click="showCreateTaskModal = false">
      <div class="modal-content" @click.stop>
        <h2>新建工作</h2>

        <div class="form-group">
          <label for="task-title">工作標題</label>
          <input
            id="task-title"
            v-model="newTask.title"
            type="text"
            placeholder="輸入工作標題"
            @keyup.enter="handleCreateTask"
          />
        </div>

        <div class="form-group">
          <label>工作描述（可選）</label>
          <RichTextEditor
            v-model="newTask.description"
            :taskId="null"
          />
        </div>

        <div class="form-group">
          <label for="task-date">工作日期</label>
          <input
            id="task-date"
            v-model="newTask.date"
            type="date"
          />
        </div>

        <!-- 圖片上傳區塊 -->
        <div class="form-group">
          <label>工作圖片（可選）</label>
          <div class="image-upload-section">
            <div class="image-buttons">
              <input
                ref="newTaskImageInput"
                type="file"
                multiple
                accept="image/*"
                style="display: none"
                @change="handleNewTaskImageSelect"
              />
              <button
                type="button"
                class="btn btn-secondary"
                @click="$refs.newTaskImageInput.click()"
              >
                📎 選擇圖片
              </button>
            </div>

            <!-- 圖片預覽網格 -->
            <div v-if="newTaskImages.length > 0" class="image-grid">
              <div v-for="(img, idx) in newTaskImages" :key="idx" class="image-item">
                <img :src="img.url" :alt="`圖片 ${idx + 1}`" />
                <button
                  type="button"
                  class="remove-image-btn"
                  @click="removeNewTaskImage(idx)"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="taskStore.error" class="error-message">
          ⚠️ {{ taskStore.error }}
        </div>

        <div class="modal-actions">
          <button class="btn btn-primary" @click="handleCreateTask">
            創建
          </button>
          <button class="btn btn-secondary" @click="showCreateTaskModal = false">
            取消
          </button>
        </div>
      </div>
    </div>

    <!-- 編輯工作模態框 -->
    <div v-if="showEditTaskModal" class="modal-overlay" @click="closeEditTaskModal">
      <div class="modal-content" @click.stop>
        <h2>編輯工作</h2>

        <div class="form-group">
          <label for="edit-task-title">工作標題</label>
          <input
            id="edit-task-title"
            v-model="editingTask.title"
            type="text"
            placeholder="輸入工作標題"
          />
        </div>

        <div class="form-group">
          <label>工作描述</label>
          <RichTextEditor
            v-model="editingTask.description"
            :taskId="editingTask.id"
          />
        </div>

        <div class="form-group">
          <label for="edit-task-duration">工時（小時）</label>
          <input
            id="edit-task-duration"
            v-model.number="editingTask.duration"
            type="number"
            placeholder="輸入工時"
            min="0"
            step="0.5"
          />
        </div>

        <div class="form-group">
          <label for="edit-task-date">工作日期</label>
          <input
            id="edit-task-date"
            v-model="editingTask.date"
            type="date"
          />
        </div>

        <!-- 圖片上傳區塊 -->
        <div class="form-group">
          <label>工作圖片</label>
          <div class="image-upload-section">
            <div class="image-buttons">
              <input
                ref="imageInput"
                type="file"
                multiple
                accept="image/*"
                style="display: none"
                @change="handleImageSelect"
              />
              <button
                type="button"
                class="btn btn-secondary"
                @click="$refs.imageInput.click()"
              >
                📎 選擇圖片
              </button>
            </div>

            <!-- 圖片預覽網格 -->
            <div v-if="editingTaskImages.length > 0" class="image-grid">
              <div v-for="(img, idx) in editingTaskImages" :key="idx" class="image-item">
                <img :src="img.url" :alt="`圖片 ${idx + 1}`" />
                <button
                  type="button"
                  class="remove-image-btn"
                  @click="removeImage(idx)"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="taskStore.error" class="error-message">
          ⚠️ {{ taskStore.error }}
        </div>

        <div class="modal-actions">
          <button class="btn btn-primary" @click="handleUpdateTask">
            保存
          </button>
          <button class="btn btn-secondary" @click="closeEditTaskModal">
            取消
          </button>
        </div>
      </div>
    </div>

    <!-- 更改密碼模態框 -->
    <div v-if="showChangePasswordModal" class="modal-overlay" @click="showChangePasswordModal = false">
      <div class="modal-content" @click.stop>
        <h2>更改密碼</h2>

        <div class="form-group">
          <label for="old-password">舊密碼</label>
          <input
            id="old-password"
            v-model="changePasswordForm.oldPassword"
            type="password"
            placeholder="輸入舊密碼"
          />
        </div>

        <div class="form-group">
          <label for="new-password">新密碼</label>
          <input
            id="new-password"
            v-model="changePasswordForm.newPassword"
            type="password"
            placeholder="輸入新密碼（至少 6 個字符）"
          />
        </div>

        <div class="form-group">
          <label for="confirm-password">確認新密碼</label>
          <input
            id="confirm-password"
            v-model="changePasswordForm.confirmPassword"
            type="password"
            placeholder="確認新密碼"
          />
        </div>

        <div v-if="userStore.error" class="error-message">
          ⚠️ {{ userStore.error }}
        </div>

        <div class="modal-actions">
          <button class="btn btn-primary" @click="handleChangePassword" :disabled="userStore.loading">
            {{ userStore.loading ? '更新中...' : '更改密碼' }}
          </button>
          <button class="btn btn-secondary" @click="showChangePasswordModal = false">
            取消
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useUserStore } from '../stores/userStore.js'
import { useTaskStore } from '../stores/taskStore.js'
import RichTextEditor from '../components/RichTextEditor.vue'
import apiConfig from '../config/apiConfig.js'

const userStore = useUserStore()
const taskStore = useTaskStore()

// ========================================
// 日期工具函数（必须在前面定义）
// ========================================
// 获取本地日期字符串，格式为 YYYY-MM-DD（修复时区问题）
const getLocalDateStr = (date = null) => {
  const d = date || new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// 视图模式
const viewMode = ref('day')

// 日期相关
const selectedDate = ref(getLocalDateStr())

// 任务创建
const showCreateTaskModal = ref(false)
const newTask = ref({
  title: '',
  description: '',
  date: selectedDate.value
})

// 新增工作時的圖片
const newTaskImages = ref([])
const newTaskImageInput = ref(null)

// 任务编辑
const showEditTaskModal = ref(false)
const editingTask = ref({
  id: null,
  title: '',
  description: '',
  duration: 0,
  date: ''
})

// 編輯時的圖片
const editingTaskImages = ref([])
const imageInput = ref(null)

// 任務卡片展開狀態
const expandedTasks = ref(new Set())

// 全展開控制
const autoExpandAll = ref(false)

// 更改密碼
const showChangePasswordModal = ref(false)
const changePasswordForm = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// 拖拽相关
let draggedTask = null
const isDragging = ref(false)

// 日期范围任务缓存
const tasksCache = ref({})

// 生命周期钩子
onMounted(async () => {
  // 從 localStorage 讀取全展開狀態
  const saved = localStorage.getItem('autoExpandAll')
  if (saved !== null) {
    autoExpandAll.value = JSON.parse(saved)
  }

  await taskStore.fetchColumns()
  await taskStore.fetchTasks(selectedDate.value)
  selectedDate.value = taskStore.selectedDate

  // 如果啟用了全展開，展開所有任務
  if (autoExpandAll.value) {
    taskStore.tasks.forEach(task => {
      expandedTasks.value.add(task.id)
    })
  }
})

// 监听视图模式变化，加载对应的数据
watch(viewMode, async (newMode) => {
  if (newMode === 'week') {
    await loadWeekData()
  } else if (newMode === 'month') {
    await loadMonthData()
  }
})

// 監視全展開狀態變化
watch(autoExpandAll, (newVal) => {
  localStorage.setItem('autoExpandAll', JSON.stringify(newVal))

  if (newVal) {
    // 展開所有卡片
    const allTaskCards = document.querySelectorAll('.task-card')
    allTaskCards.forEach(card => {
      card.classList.add('expanded')
    })

    // 也要更新 expandedTasks 集合
    taskStore.tasks.forEach(task => {
      expandedTasks.value.add(task.id)
    })
  } else {
    // 收起所有卡片
    expandedTasks.value.clear()
  }
})

// ========================================
// 工具方法
// ========================================
const sanitizeHtml = (html) => {
  if (!html) return ''
  // 移除危險的腳本標籤，但保留圖片標籤
  return html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
}

// ========================================
// 日期导航方法
// ========================================
const weekLabels = ['日', '一', '二', '三', '四', '五', '六']

const selectedDateWeekday = computed(() => {
  const date = new Date(selectedDate.value)
  return weekLabels[date.getDay()]
})

const applyAutoExpand = () => {
  // 如果啟用了全展開，展開所有任務
  if (autoExpandAll.value) {
    expandedTasks.value.clear()
    taskStore.tasks.forEach(task => {
      expandedTasks.value.add(task.id)
    })
  }
}

const goToPreviousDay = () => {
  const date = new Date(selectedDate.value)
  date.setDate(date.getDate() - 1)
  selectedDate.value = getLocalDateStr(date)
  taskStore.changeDate(selectedDate.value)
  applyAutoExpand()
}

const goToToday = () => {
  selectedDate.value = getLocalDateStr()
  taskStore.changeDate(selectedDate.value)
  applyAutoExpand()
}

const goToNextDay = () => {
  const date = new Date(selectedDate.value)
  date.setDate(date.getDate() + 1)
  selectedDate.value = getLocalDateStr(date)
  taskStore.changeDate(selectedDate.value)
  applyAutoExpand()
}

const handleDateChange = () => {
  taskStore.changeDate(selectedDate.value)
  applyAutoExpand()
}

// ========================================
// 任务操作方法
// ========================================
const handleCreateTask = async () => {
  // 先創建任務，然後處理base64圖片
  const newTaskObj = await taskStore.createTask(
    newTask.value.title,
    newTask.value.description,
    newTask.value.date
  )

  if (newTaskObj && typeof newTaskObj === 'object') {
    // 如果返回的是新任务对象，添加到缓存
    const dateStr = newTaskObj.created_date
    if (!tasksCache.value[dateStr]) {
      tasksCache.value[dateStr] = []
    }
    tasksCache.value[dateStr].push(newTaskObj)

    // 處理 description 中的 base64 圖片
    const processedDesc = await processBase64Images(newTaskObj.id, newTask.value.description)
    if (processedDesc !== null && processedDesc !== newTask.value.description) {
      // 有 base64 圖片被轉換，更新任務描述
      await taskStore.updateTaskInfo(newTaskObj.id, {
        description: processedDesc
      })
      // 更新緩存
      newTaskObj.description = processedDesc
    }

    // 如果有圖片要上傳，現在上傳
    if (newTaskImages.value.length > 0) {
      const uploadSuccess = await uploadNewTaskImages(newTaskObj.id)
      if (!uploadSuccess) {
        return // 上傳失敗，不關閉模態框
      }
    }

    newTask.value = {
      title: '',
      description: '',
      date: selectedDate.value
    }
    newTaskImages.value = []
    showCreateTaskModal.value = false
  }
}

const handleNewTaskImageSelect = (event) => {
  const files = event.target.files
  if (!files) return

  Array.from(files).forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      newTaskImages.value.push({
        file: file,
        url: e.target.result
      })
    }
    reader.readAsDataURL(file)
  })

  // 清空 input
  if (newTaskImageInput.value) {
    newTaskImageInput.value.value = ''
  }
}

const removeNewTaskImage = (index) => {
  newTaskImages.value.splice(index, 1)
}

const uploadNewTaskImages = async (taskId) => {
  if (newTaskImages.value.length === 0) return true

  for (const imgData of newTaskImages.value) {
    const formData = new FormData()
    formData.append('image', imgData.file)

    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=addImage`,
        {
          method: 'POST',
          credentials: 'include',
          body: formData
        }
      )

      const data = await response.json()
      if (!data.success) {
        throw new Error(data.message)
      }
    } catch (err) {
      taskStore.error = `圖片上傳失敗: ${err.message}`
      return false
    }
  }

  return true
}

const handleDeleteTask = async (taskId) => {
  if (confirm('確定要刪除這個工作嗎？')) {
    const success = await taskStore.deleteTask(taskId)

    // 如果任务删除成功，从缓存中移除
    if (success) {
      for (const dateStr in tasksCache.value) {
        tasksCache.value[dateStr] = tasksCache.value[dateStr].filter(t => t.id !== taskId)
      }
    }
  }
}

const moveTask = async (taskId, newStatus) => {
  return await taskStore.updateTaskStatus(taskId, newStatus)
}

// ========================================
// 任务编辑方法
// ========================================
const handleTaskCardClick = (task) => {
  // 如果啟用了全展開，點擊直接編輯
  if (autoExpandAll.value) {
    openEditTaskModal(task)
    return
  }

  // 如果卡片已展開，點擊編輯
  if (expandedTasks.value.has(task.id)) {
    openEditTaskModal(task)
  } else {
    // 如果卡片未展開，點擊展開卡片
    expandedTasks.value.add(task.id)
  }
}

const openEditTaskModal = (task) => {
  editingTask.value = {
    id: task.id,
    title: task.title,
    description: task.description || '',
    duration: task.duration || 0,
    date: selectedDate.value
  }

  editingTaskImages.value = []
  showEditTaskModal.value = true
}

const handleUpdateTask = async () => {
  if (!editingTask.value.title.trim()) {
    taskStore.error = '工作標題不能為空'
    return
  }

  // 處理 description 中的 base64 圖片
  let description = editingTask.value.description
  const processedDesc = await processBase64Images(editingTask.value.id, description)

  if (processedDesc === null) {
    return // 處理失敗
  }

  const success = await taskStore.updateTaskInfo(editingTask.value.id, {
    title: editingTask.value.title,
    description: processedDesc,
    duration: editingTask.value.duration,
    date: editingTask.value.date
  })

  if (success) {
    // 更新缓存中的任务
    for (const dateStr in tasksCache.value) {
      const task = tasksCache.value[dateStr].find(t => t.id === editingTask.value.id)
      if (task) {
        task.title = editingTask.value.title
        task.description = processedDesc
        task.duration = editingTask.value.duration
        // 如果date改变，需要移除旧的，添加新的
        if (task.created_date !== editingTask.value.date) {
          tasksCache.value[dateStr] = tasksCache.value[dateStr].filter(t => t.id !== editingTask.value.id)
          if (!tasksCache.value[editingTask.value.date]) {
            tasksCache.value[editingTask.value.date] = []
          }
          task.created_date = editingTask.value.date
          tasksCache.value[editingTask.value.date].push(task)
        }
        break
      }
    }

    // 如果有圖片要上傳，現在上傳
    if (editingTaskImages.value.length > 0) {
      const uploadSuccess = await uploadImages(editingTask.value.id)
      if (!uploadSuccess) {
        return // 上傳失敗，不關閉模態框
      }
    }

    closeEditTaskModal()
  }
}

const closeEditTaskModal = () => {
  showEditTaskModal.value = false
  taskStore.error = null
  editingTaskImages.value = []
  // 編輯完成或取消後，收起卡片
  expandedTasks.value.delete(editingTask.value.id)
}

// ========================================
// 圖片上傳方法
// ========================================
const handleImageSelect = (event) => {
  const files = event.target.files
  if (!files) return

  Array.from(files).forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      editingTaskImages.value.push({
        file: file,
        url: e.target.result
      })
    }
    reader.readAsDataURL(file)
  })

  // 清空 input
  if (imageInput.value) {
    imageInput.value.value = ''
  }
}

const removeImage = (index) => {
  editingTaskImages.value.splice(index, 1)
}

const uploadImages = async (taskId) => {
  if (editingTaskImages.value.length === 0) return

  for (const imgData of editingTaskImages.value) {
    const formData = new FormData()
    formData.append('image', imgData.file)

    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=addImage`,
        {
          method: 'POST',
          credentials: 'include',
          body: formData
        }
      )

      const data = await response.json()
      if (!data.success) {
        throw new Error(data.message)
      }
    } catch (err) {
      taskStore.error = `圖片上傳失敗: ${err.message}`
      return false
    }
  }

  return true
}

// 處理 description 中的 base64 圖片
const processBase64Images = async (taskId, description) => {
  if (!description) return description

  // 查找所有 base64 圖片
  const base64Regex = /data:image\/[^;]+;base64,[A-Za-z0-9+/=]+/g
  const base64Images = description.match(base64Regex) || []

  if (base64Images.length === 0) {
    return description
  }

  let updatedDescription = description

  // 逐個處理 base64 圖片
  for (const base64Data of base64Images) {
    try {
      // 將 base64 轉換為 Blob
      // 提取 base64 部分和 MIME 類型
      const matches = base64Data.match(/data:([^;]+);base64,(.+)/)
      if (!matches || matches.length < 3) {
        continue
      }

      const mimeType = matches[1]
      const base64String = matches[2]

      // 解碼 base64 為二進位數據
      const binaryString = atob(base64String)
      const bytes = new Uint8Array(binaryString.length)
      for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i)
      }

      // 創建 Blob
      const blob = new Blob([bytes], { type: mimeType })

      // 上傳到服務器
      const formData = new FormData()
      formData.append('image', blob)

      const uploadResponse = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=addImage&source=description`,
        {
          method: 'POST',
          credentials: 'include',
          body: formData
        }
      )

      const uploadData = await uploadResponse.json()

      if (uploadData.success && uploadData.data?.url) {
        // 將 description 中的 base64 替換為實際 URL
        updatedDescription = updatedDescription.replace(
          base64Data,
          uploadData.data.url
        )
      }
    } catch (err) {
      taskStore.error = `圖片轉換失敗: ${err.message}`
      return null
    }
  }

  return updatedDescription
}

const closeAllExpandedCards = () => {
  // 點擊卡片外時，閉合所有展開的卡片
  if (!autoExpandAll.value) {
    expandedTasks.value.clear()
  }
}

const toggleAutoExpand = () => {
  autoExpandAll.value = !autoExpandAll.value
}

// 計算進度百分比
const getProgressPercentage = (completed, total) => {
  if (total === 0) return 0
  return (completed / total) * 100
}

// 檢查任務是否完成
const isTaskCompleted = (task) => {
  if (!task || !task.status) return false
  // 支持多種狀態值：繁體、簡體、英文、各種拼寫
  const completedStates = ['已完成', 'completed', 'done', '完成', 'Done']
  return completedStates.includes(task.status)
}

// ========================================
// 更改密碼方法
// ========================================
const handleChangePassword = async () => {
  if (!changePasswordForm.value.oldPassword) {
    userStore.error = '舊密碼不能為空'
    return
  }

  if (!changePasswordForm.value.newPassword) {
    userStore.error = '新密碼不能為空'
    return
  }

  if (changePasswordForm.value.newPassword.length < 6) {
    userStore.error = '新密碼至少 6 個字符'
    return
  }

  if (changePasswordForm.value.newPassword !== changePasswordForm.value.confirmPassword) {
    userStore.error = '新密碼和確認密碼不相符'
    return
  }

  const success = await userStore.changePassword(
    changePasswordForm.value.oldPassword,
    changePasswordForm.value.newPassword,
    changePasswordForm.value.confirmPassword
  )

  if (success) {
    showChangePasswordModal.value = false
    userStore.error = null
    changePasswordForm.value = {
      oldPassword: '',
      newPassword: '',
      confirmPassword: ''
    }
    alert('密碼已成功更改')
  }
}

// ========================================
// 拖拽方法
// ========================================
const onDragStart = (event, task, status) => {
  isDragging.value = true
  draggedTask = { ...task, fromStatus: status }
  event.dataTransfer.effectAllowed = 'move'
  // 拖動時自動閉合所有展開的卡片
  expandedTasks.value.clear()
}

const onDragOver = (event) => {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
}

const onDragEnter = (event) => {
  // 可选：在这里添加视觉反馈
}

const onDragLeave = (event) => {
  // 可选：在这里移除视觉反馈
}

const onDrop = async (event, toStatus) => {
  event.preventDefault()

  if (!draggedTask) return

  // 如果拖拽到不同的列，更新状态
  if (draggedTask.fromStatus !== toStatus) {
    const success = await moveTask(draggedTask.id, toStatus)

    // 如果任务状态更新成功，同步更新缓存中的任务
    if (success) {
      for (const dateStr in tasksCache.value) {
        const task = tasksCache.value[dateStr].find(t => t.id === draggedTask.id)
        if (task) {
          task.status = toStatus
          break
        }
      }
    }
  }

  draggedTask = null
  isDragging.value = false
}

// ========================================
// 周视图方法
// ========================================
const currentWeekStart = ref(new Date())

const weekRangeDisplay = computed(() => {
  const start = new Date(currentWeekStart.value)
  start.setDate(start.getDate() - start.getDay())

  const end = new Date(start)
  end.setDate(end.getDate() + 6)

  const formatDate = (date) => `${date.getMonth() + 1}/${date.getDate()}`
  return `${formatDate(start)} - ${formatDate(end)}`
})

const weekRangeWeekdays = computed(() => {
  const start = new Date(currentWeekStart.value)
  start.setDate(start.getDate() - start.getDay())

  const end = new Date(start)
  end.setDate(end.getDate() + 6)

  return `(${weekLabels[start.getDay()]}-${weekLabels[end.getDay()]})`
})

const weekDays = computed(() => {
  const days = []
  const startOfWeek = new Date(currentWeekStart.value)
  startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay())

  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    // 使用本地時間格式而不是 ISO UTC 格式，以匹配後端數據
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const dateStr = `${year}-${month}-${day}`

    // 从缓存获取该日期的任务
    const dayTasks = tasksCache.value[dateStr] || []
    const taskCount = dayTasks.length
    const completedCount = dayTasks.filter(t => isTaskCompleted(t)).length

    // 计算今天的日期（同样使用本地时间）
    const today = new Date()
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

    days.push({
      dateStr,
      label: ['日', '一', '二', '三', '四', '五', '六'][date.getDay()],
      displayDate: `${date.getMonth() + 1}/${date.getDate()}`,
      isToday: dateStr === todayStr,
      taskCount,
      completedCount
    })
  }

  return days
})

const loadWeekData = async () => {
  const startOfWeek = new Date(currentWeekStart.value)
  startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay())

  // 使用本地時間格式而不是 ISO UTC 格式
  const startYear = startOfWeek.getFullYear()
  const startMonth = String(startOfWeek.getMonth() + 1).padStart(2, '0')
  const startDay = String(startOfWeek.getDate()).padStart(2, '0')
  const startDateStr = `${startYear}-${startMonth}-${startDay}`

  const endDate = new Date(startOfWeek)
  endDate.setDate(startOfWeek.getDate() + 6)
  const endYear = endDate.getFullYear()
  const endMonth = String(endDate.getMonth() + 1).padStart(2, '0')
  const endDay = String(endDate.getDate()).padStart(2, '0')
  const endDateStr = `${endYear}-${endMonth}-${endDay}`

  const tasksMap = await taskStore.fetchTasksForRange(startDateStr, endDateStr)
  tasksCache.value = { ...tasksCache.value, ...tasksMap }
}

const switchToDay = (dateStr) => {
  selectedDate.value = dateStr
  viewMode.value = 'day'
  taskStore.changeDate(dateStr)
}

const goToPreviousWeek = async () => {
  currentWeekStart.value = new Date(currentWeekStart.value)
  currentWeekStart.value.setDate(currentWeekStart.value.getDate() - 7)
  await loadWeekData()
}

const goToNextWeek = async () => {
  currentWeekStart.value = new Date(currentWeekStart.value)
  currentWeekStart.value.setDate(currentWeekStart.value.getDate() + 7)
  await loadWeekData()
}

const goToThisWeek = async () => {
  currentWeekStart.value = new Date()
  await loadWeekData()
}

// ========================================
// 月视图方法
// ========================================
const currentMonth = ref(new Date())

const monthTitle = computed(() => {
  return `${currentMonth.value.getFullYear()}年 ${currentMonth.value.getMonth() + 1}月`
})

const monthDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()

  // 当月第一天
  const firstDay = new Date(year, month, 1)
  // 当月最后一天
  const lastDay = new Date(year, month + 1, 0)

  // 前月补齐天数
  const prevMonth = new Date(year, month, 0)
  const prevLastDate = prevMonth.getDate()
  const firstDayOfWeek = firstDay.getDay()

  const days = []

  // 添加前月的天数
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const date = prevLastDate - i
    days.push({
      day: date,
      currentMonth: false,
      isToday: false,
      dateStr: '',
      taskCount: 0,
      completedCount: 0
    })
  }

  // 添加当月的天数
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const dateObj = new Date(year, month, i)
    // 使用本地時間格式而不是 ISO UTC 格式
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`

    // 计算今天的日期（同样使用本地时间）
    const today = new Date()
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
    const isToday = dateStr === todayStr

    // 从缓存获取该日期的任务
    const dayTasks = tasksCache.value[dateStr] || []
    const taskCount = dayTasks.length
    const completedCount = dayTasks.filter(t => isTaskCompleted(t)).length

    days.push({
      day: i,
      currentMonth: true,
      isToday,
      dateStr,
      taskCount,
      completedCount
    })
  }

  // 添加后月的天数
  const remainingDays = 42 - days.length // 6行 * 7列 = 42
  for (let i = 1; i <= remainingDays; i++) {
    days.push({
      day: i,
      currentMonth: false,
      isToday: false,
      dateStr: '',
      taskCount: 0,
      completedCount: 0
    })
  }

  return days
})

const loadMonthData = async () => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()

  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  // 使用本地時間格式而不是 ISO UTC 格式
  const startDateStr = `${year}-${String(month + 1).padStart(2, '0')}-01`
  const endDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(lastDay.getDate()).padStart(2, '0')}`

  const tasksMap = await taskStore.fetchTasksForRange(startDateStr, endDateStr)
  tasksCache.value = { ...tasksCache.value, ...tasksMap }
}

const goToPreviousMonth = async () => {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() - 1,
    1
  )
  await loadMonthData()
}

const goToNextMonth = async () => {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() + 1,
    1
  )
  await loadMonthData()
}

const goToThisMonth = async () => {
  const today = new Date()
  currentMonth.value = new Date(today.getFullYear(), today.getMonth(), 1)
  await loadMonthData()
}

// 前往異動記錄頁面
const goToHistory = () => {
  window.location.hash = '#history'
}

// 前往欄位管理頁面
const goToColumnsManagement = () => {
  window.$router.push('columns')
}
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100vh;
  background-color: var(--color-gray-50);
}

/* 頭部 */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg) var(--spacing-xl);
  background: linear-gradient(135deg, #ffffff 0%, #f7f9fc 100%);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-gray-200);
  margin: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
}

.header-left h1 {
  margin: 0 0 6px 0;
  color: var(--color-gray-900);
  font-size: 24px;
  font-weight: 600;
  letter-spacing: 0;
}

.welcome {
  color: var(--color-gray-500);
  font-size: 13px;
  margin: 0;
  font-weight: 400;
}

.header-actions {
  display: flex;
  gap: var(--spacing-md);
  align-items: center;
}

/* 主内容 */
.main-content {
  flex: 1;
  overflow: auto;
  padding: 0 var(--spacing-lg) var(--spacing-lg) var(--spacing-lg);
  display: flex;
  flex-direction: column;
}

/* 視圖選擇 Tab 容器 */
.view-tabs-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: var(--spacing-lg);
}

.view-tabs {
  display: flex;
  gap: 8px;
}

/* 全展開控制按鈕 */
.expand-control-btn {
  padding: 8px 16px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  background-color: white;
  color: var(--color-gray-700);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.expand-control-btn:hover {
  background-color: var(--color-gray-50);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.expand-control-btn.active {
  background-color: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.view-tab-btn {
  padding: 8px 20px;
  border: 1px solid var(--color-gray-300);
  background-color: white;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.25s ease;
  color: var(--color-gray-700);
  letter-spacing: 0;
}

.view-tab-btn:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
  background-color: rgba(0, 102, 204, 0.04);
}

.view-tab-btn.active {
  background-color: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

/* 工具欄 */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  flex-wrap: nowrap;
  padding: var(--spacing-lg) 0;
}

.date-navigator {
  display: flex;
  gap: var(--spacing-md);
  align-items: center;
  flex-shrink: 0;
  white-space: nowrap;
}

.date-with-weekday {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  background-color: white;
}

.date-input {
  border: none;
  padding: 0;
  font-size: 14px;
  min-width: 130px;
}

.date-input:focus {
  outline: none;
}

.weekday-display {
  font-size: 14px;
  color: var(--color-gray-600);
  font-weight: 400;
  min-width: 30px;
  text-align: center;
  letter-spacing: 0;
}

.date-display {
  padding: 8px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  font-size: 14px;
  min-width: 180px;
  text-align: center;
  background-color: white;
  color: var(--color-gray-700);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 400;
  letter-spacing: 0;
}

.weekday-info {
  color: var(--color-gray-500);
  font-size: 13px;
  font-weight: 400;
  letter-spacing: 0;
}

.loading {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--color-gray-600);
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  letter-spacing: 0;
}

/* ========================================
   日视图 - 看板
   ======================================== */
.day-view {
  flex: 1;
  overflow: auto;
}

.kanban-board {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-md);
  height: fit-content;
  min-height: 100%;
}

.kanban-column {
  display: flex;
  flex-direction: column;
  background-color: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-gray-200);
  max-height: 70vh;
  transition: all 0.25s ease;
}

.kanban-column:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--color-gray-300);
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-md);
  background: linear-gradient(135deg, var(--color-gray-100) 0%, var(--color-gray-50) 100%);
  font-weight: 600;
  font-size: 15px;
  color: var(--color-gray-900);
  border-bottom: 1px solid var(--color-gray-200);
  letter-spacing: 0;
}

.task-list {
  flex: 1;
  padding: var(--spacing-md);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

/* 工作卡片 */
.task-card {
  background-color: white;
  border: 1px solid var(--color-gray-200);
  border-left: 3px solid var(--color-primary);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  cursor: pointer;
  transition: all 0.25s ease;
}

.task-card:hover {
  box-shadow: var(--shadow-md);
  border-left-color: var(--color-primary-hover);
  transform: translateY(-2px);
}

.task-card.completed {
  background-color: #f6fffe;
  border-left-color: var(--color-success);
  opacity: 0.9;
}

.task-card.suspended {
  background-color: #fffbf0;
  border-left-color: var(--color-warning);
  opacity: 0.9;
}

.task-card.readonly {
  cursor: default;
  background-color: #f8f9fa;
  border-left-color: #999;
}

.task-card.readonly:hover {
  box-shadow: none;
  border-left-color: #999;
  transform: none;
}

.task-title {
  font-weight: 600;
  color: var(--color-gray-900);
  margin-bottom: 6px;
  word-break: break-word;
  font-size: 15px;
  line-height: 1.4;
  letter-spacing: 0;
}

.task-description {
  color: var(--color-gray-600);
  font-size: 13px;
  margin-bottom: 8px;
  line-height: 1.5;
  word-break: break-word;
  letter-spacing: 0;
}

.task-description :deep(img) {
  width: 100%;
  height: auto;
  border-radius: var(--radius-sm);
  margin: 4px 0;
  object-fit: cover;
  display: block;
}

.task-description :deep(p) {
  margin: 4px 0;
}

.task-description :deep(ul) {
  margin: 4px 0;
  padding-left: 16px;
}

.task-description :deep(li) {
  margin: 2px 0;
}

.task-duration {
  color: var(--color-gray-500);
  font-size: 13px;
  margin-bottom: 8px;
  font-weight: 400;
  background-color: var(--color-gray-50);
  display: inline-block;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  letter-spacing: 0;
}

.task-card.expanded {
  border-left-color: var(--color-primary-hover);
  background-color: rgba(0, 102, 204, 0.02);
}

.task-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--color-gray-200);
}

.edit-btn {
  flex: 1;
  padding: 8px 12px;
  background-color: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  letter-spacing: 0;
}

.edit-btn:hover {
  background-color: var(--color-primary-hover);
  transform: translateY(-1px);
}

/* ========================================
   周视图
   ======================================== */
.week-view {
  flex: 1;
  overflow: auto;
  padding: var(--spacing-lg);
}

.week-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: var(--spacing-lg);
  max-width: 1400px;
  margin: 0 auto;
}

.week-day-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid var(--color-gray-200);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  position: relative;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  min-height: 240px;
}

.week-day-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 8px 24px rgba(0, 102, 204, 0.15);
  transform: translateY(-4px);
  background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
}

.week-day-card.today {
  border: 2px solid var(--color-success);
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(34, 197, 94, 0.02) 100%);
}

.week-day-card.has-tasks {
  border-color: var(--color-primary);
}

.today-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background-color: var(--color-primary);
  color: white;
  padding: 4px 10px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0;
}

.week-day-header {
  padding-bottom: var(--spacing-sm);
  border-bottom: 1px solid var(--color-gray-200);
}

.week-day-date {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-gray-900);
  line-height: 1.2;
  letter-spacing: 0;
}

.week-day-label {
  font-size: 14px;
  color: var(--color-gray-600);
  font-weight: 400;
  margin-top: 4px;
  letter-spacing: 0;
}

.week-day-stats {
  display: flex;
  align-items: center;
  justify-content: space-around;
  gap: var(--spacing-md);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex: 1;
}

.stat-label {
  font-size: 12px;
  color: var(--color-gray-600);
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: 0;
}

.stat-divider {
  width: 1px;
  height: 32px;
  background-color: var(--color-gray-300);
}

.progress-container {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.progress-bar {
  flex: 1;
  height: 6px;
  background-color: var(--color-gray-200);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #00a854;
  transition: width 0.3s ease;
  border-radius: 3px;
}

.progress-text {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-success);
  min-width: 40px;
  text-align: right;
  letter-spacing: 0;
}

.task-status-indicators {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  letter-spacing: 0;
}

.task-status-indicators .pending,
.task-status-indicators .completed {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border-radius: var(--radius-sm);
  background-color: var(--color-gray-50);
}

.pending {
  color: var(--color-warning);
}

.completed {
  color: var(--color-success);
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.pending-dot {
  background-color: var(--color-warning);
}

.completed-dot {
  background-color: var(--color-success);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  gap: 8px;
  color: var(--color-gray-500);
}

.empty-icon {
  font-size: 32px;
  opacity: 0.6;
}

.empty-text {
  font-size: 14px;
  font-weight: 400;
  letter-spacing: 0;
}

.view-hint {
  font-size: 12px;
  color: var(--color-gray-500);
  text-align: center;
  margin-top: auto;
  padding-top: var(--spacing-sm);
  border-top: 1px solid var(--color-gray-200);
  opacity: 0.7;
  transition: opacity 0.2s ease;
  font-weight: 400;
  letter-spacing: 0;
}

.week-day-card:hover .view-hint {
  opacity: 1;
  color: var(--color-primary);
}

/* ========================================
   月视图
   ======================================== */
.month-view {
  flex: 1;
  overflow: auto;
  display: flex;
  flex-direction: column;
  padding: 0;
}

.month-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0;
  background-color: var(--color-gray-300);
  padding: 0;
  flex: 1;
}

.month-day-label {
  background: linear-gradient(135deg, var(--color-gray-100) 0%, var(--color-gray-50) 100%);
  padding: var(--spacing-md);
  text-align: center;
  font-weight: 500;
  color: var(--color-gray-700);
  font-size: 14px;
  border-right: 1px solid var(--color-gray-300);
  border-bottom: 1px solid var(--color-gray-300);
  letter-spacing: 0;
}

.month-day {
  background-color: white;
  padding: var(--spacing-md);
  min-height: 120px;
  cursor: pointer;
  transition: all 0.25s ease;
  border-right: 1px solid var(--color-gray-300);
  border-bottom: 1px solid var(--color-gray-300);
  display: flex;
  flex-direction: column;
  position: relative;
}

.month-day:hover {
  background-color: rgba(0, 102, 204, 0.04);
  box-shadow: inset 0 2px 8px rgba(0, 102, 204, 0.08);
}

.month-day.today {
  background: linear-gradient(135deg, rgba(0, 102, 204, 0.08) 0%, rgba(0, 102, 204, 0.02) 100%);
  border-right-color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.month-day.today::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background-color: var(--color-primary);
}

.month-day.other {
  background-color: var(--color-gray-50);
  cursor: default;
  color: var(--color-gray-400);
}

.month-day-number {
  font-weight: 600;
  color: var(--color-gray-900);
  margin-bottom: 8px;
  font-size: 15px;
  letter-spacing: 0;
}

.month-day-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.task-info {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.task-total {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: 0;
}

.task-label {
  font-size: 12px;
  color: var(--color-gray-600);
  font-weight: 400;
  letter-spacing: 0;
}

.completion-container {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  letter-spacing: 0;
}

.progress-bar-small {
  flex: 1;
  height: 4px;
  background-color: var(--color-gray-200);
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill-small {
  height: 100%;
  background: #00a854;
  transition: width 0.3s ease;
  border-radius: 2px;
}

.completion-text {
  color: var(--color-gray-600);
  font-weight: 400;
  min-width: 35px;
  text-align: right;
  font-size: 12px;
  letter-spacing: 0;
}

.completion-status {
  display: flex;
  justify-content: center;
}

.status-badge {
  font-size: 12px;
  font-weight: 500;
  padding: 3px 6px;
  border-radius: var(--radius-sm);
  display: inline-block;
  text-align: center;
  letter-spacing: 0;
}

.status-badge.all-done {
  background-color: rgba(34, 197, 94, 0.1);
  color: var(--color-success);
}

.status-badge.partial {
  background-color: rgba(251, 146, 60, 0.1);
  color: #f97316;
}

.status-badge.not-started {
  background-color: rgba(156, 163, 175, 0.1);
  color: var(--color-gray-700);
}

.month-day-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
  opacity: 0.5;
}

.empty-hint {
  font-size: 12px;
  color: var(--color-gray-600);
  font-weight: 400;
  letter-spacing: 0;
}

/* 模態框 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background-color: white;
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg) var(--spacing-xl);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-xl);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 美化滚动条 */
.modal-content::-webkit-scrollbar {
  width: 8px;
}

.modal-content::-webkit-scrollbar-track {
  background: var(--color-gray-50);
  border-radius: var(--radius-lg);
}

.modal-content::-webkit-scrollbar-thumb {
  background: var(--color-gray-300);
  border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
  background: var(--color-gray-400);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-content h2 {
  margin-top: 0;
  margin-bottom: var(--spacing-lg);
  color: var(--color-gray-900);
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0;
}

.form-group {
  margin-bottom: var(--spacing-lg);
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--color-gray-700);
  font-size: 14px;
  letter-spacing: 0;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-family: inherit;
  transition: all 0.25s ease;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.08);
}

.error-message {
  background-color: #fff5f5;
  color: var(--color-danger);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-lg);
  font-size: 14px;
  border-left: 3px solid var(--color-danger);
  font-weight: 400;
  letter-spacing: 0;
}

/* 圖片上傳區塊 */
.image-upload-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.image-buttons {
  display: flex;
  gap: var(--spacing-md);
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: var(--spacing-md);
}

.image-item {
  position: relative;
  border: 1px solid var(--color-gray-200);
  border-radius: var(--radius-md);
  overflow: hidden;
  aspect-ratio: 1;
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-image-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background-color: rgba(0, 0, 0, 0.6);
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  padding: 0;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.remove-image-btn:hover {
  background-color: rgba(0, 0, 0, 0.8);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: flex-end;
  margin-top: var(--spacing-xl);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-gray-200);
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .date-navigator {
    justify-content: space-between;
  }

  .kanban-board {
    grid-template-columns: 1fr;
  }

  .week-grid {
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  }
}
</style>
