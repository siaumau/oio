# OIO 項目 - 詳細實施工作內容

**最後更新**: 2026年3月6日
**文檔類型**: 實施細節 + 代碼說明

---

## 目錄

1. [週視圖匯出功能](#週視圖匯出功能)
2. [日視圖看板實現](#日視圖看板實現)
3. [多視圖切換機制](#多視圖切換機制)
4. [自定義欄位管理](#自定義欄位管理)
5. [任務編輯與保存流程](#任務編輯與保存流程)
6. [圖片上傳功能](#圖片上傳功能)
7. [異動記錄追蹤](#異動記錄追蹤)
8. [管理員只讀模式](#管理員只讀模式)
9. [狀態管理詳解](#狀態管理詳解)
10. [API 交互流程](#api交互流程)

---

## 週視圖匯出功能

### 實施位置
- **文件**: `frontend/src/views/DashboardView.vue`
- **行號**: 第 90 行（按鈕）+ 1264-1339 行（方法）
- **完成時間**: 2026年3月6日

### 功能概述
在週視圖的導航欄右側新增「📥 匯出本周」按鈕，點擊後自動下載當周所有任務的統計報告（TXT 格式）。

### 實施細節

#### 1. 按鈕 HTML (line 90)
```vue
<!-- 週視圖導航 -->
<template v-if="viewMode === 'week'">
  <button class="btn btn-secondary" @click="goToPreviousWeek">← 上一周</button>
  <span class="date-display">{{ weekRangeDisplay }} <span class="weekday-info">{{ weekRangeWeekdays }}</span></span>
  <button class="btn btn-secondary" @click="goToThisWeek">本周</button>
  <button class="btn btn-secondary" @click="goToNextWeek">下一周 →</button>
  <button class="btn btn-secondary" @click="exportWeekReport">📥 匯出本周</button>  <!-- 新增 -->
</template>
```

**設計考慮**:
- 按鈕樣式統一使用 `.btn .btn-secondary`
- 位置：「下一周」按鈕之後，方便用戶找到
- 圖標 📥 直觀表示下載/匯出

#### 2. exportWeekReport 方法 (line 1264-1339)

```javascript
const exportWeekReport = () => {
  // ===== 步驟 1: 計算當周日期 =====
  // 從 currentWeekStart 出發，往前推到週日
  const startOfWeek = new Date(currentWeekStart.value)
  startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay())

  // 建構 7 天的日期列表
  const weekDaysList = []
  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    weekDaysList.push({
      dateStr: `${y}-${m}-${d}`,          // 用於查詢 tasksCache 的鍵（例如 "2026-03-06"）
      displayDate: `${date.getMonth() + 1}/${date.getDate()}`,  // 顯示格式（例如 "3/6"）
      label: weekLabels[date.getDay()]    // 週日標籤（日/一/二/三/四/五/六）
    })
  }
  // 結果例子:
  // [
  //   { dateStr: "2026-03-01", displayDate: "3/1", label: "日" },
  //   { dateStr: "2026-03-02", displayDate: "3/2", label: "一" },
  //   ...
  // ]

  // ===== 步驟 2: 收集當周所有任務 =====
  const allTasks = []
  for (const day of weekDaysList) {
    // tasksCache 是前端的任務緩存對象，格式為：
    // { "2026-03-06": [task1, task2, ...], "2026-03-07": [...] }
    const tasks = tasksCache.value[day.dateStr] || []
    for (const t of tasks) {
      allTasks.push({
        ...t,                          // 保留所有任務屬性
        _displayDate: day.displayDate,  // 添加用於顯示的日期
        _label: day.label              // 添加用於顯示的週日標籤
      })
    }
  }
  // 結果: [
  //   { id: 1, title: "任務A", status: "進行中", duration: 2, _displayDate: "3/2", _label: "一", ... },
  //   { id: 2, title: "任務B", status: "已完成", duration: 0, _displayDate: "3/2", _label: "一", ... },
  //   ...
  // ]

  // ===== 步驟 3: 按狀態統計 =====
  const statusMap = {}
  for (const t of allTasks) {
    const s = t.status || '未知'
    statusMap[s] = (statusMap[s] || 0) + 1
  }
  // 結果: { "進行中": 3, "已完成": 5, "待做": 2, "暫停": 1 }

  // ===== 步驟 4: 生成 TXT 內容 =====
  const lines = []

  // 標題部分
  lines.push(`OIO 當周任務報告`)
  lines.push(`${weekRangeDisplay.value} ${weekRangeWeekdays.value}`)  // 例如: "3/1 - 3/7 (日-六)"
  lines.push(`匯出時間: ${new Date().toLocaleString('zh-TW')}`)      // 例如: "2026/3/6 下午 03:00:00"
  lines.push(`${'='.repeat(40)}`)  // 40 個等號作為視覺分隔
  lines.push('')

  // 統計部分
  lines.push('【按狀態統計】')
  lines.push('-'.repeat(20))  // 20 個短橫線
  const total = allTasks.length
  for (const [status, count] of Object.entries(statusMap)) {
    lines.push(`  ${status}: ${count} 個任務`)
  }
  lines.push(`  合計: ${total} 個任務`)
  lines.push('')

  // 詳細列表部分
  lines.push('【詳細任務列表】')
  lines.push('-'.repeat(20))
  for (const day of weekDaysList) {
    const tasks = tasksCache.value[day.dateStr] || []
    if (tasks.length === 0) continue  // 跳過無任務的日期

    lines.push(``)
    lines.push(`[${day.displayDate} (${day.label})]`)  // 例如: "[3/2 (一)]"

    for (const t of tasks) {
      const icon = isTaskCompleted(t) ? '✅' : '○'     // 已完成顯示 ✅，其他顯示 ○
      const duration = t.duration ? ` (⏱️ ${t.duration}h)` : ''  // 有時長才顯示
      lines.push(`  ${icon} [${t.status}] ${t.title}${duration}`)
      // 例如: "  ✅ [已完成] 完成文檔 (⏱️ 2.5h)"
      // 例如: "  ○ [待做] 開會"
    }
  }
  lines.push('')
  lines.push(`${'='.repeat(40)}`)

  // ===== 步驟 5: 觸發下載 =====
  const content = lines.join('\n')  // 用換行符連接所有行

  // 加上 UTF-8 BOM 字節序標記，確保 Windows 記事本正確顯示中文
  // \uFEFF 是 UTF-8 BOM 的 Unicode 表示
  const blob = new Blob(['\uFEFF' + content], { type: 'text/plain;charset=utf-8' })

  // 建立下載連結
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url

  // 檔案名: OIO_週報_3-1_3-7.txt
  // weekRangeDisplay.value 是 "3/1 - 3/7"，
  // 將 "/" 替換為 "-"，" - " 替換為 "_"
  a.download = `OIO_週報_${weekRangeDisplay.value.replace(/\//g, '-').replace(' - ', '_')}.txt`

  // 模擬用戶點擊下載按鈕
  a.click()

  // 清理記憶體
  URL.revokeObjectURL(url)
}
```

### TXT 輸出範例

```
OIO 當周任務報告
3/1 - 3/7 (日-六)
匯出時間: 2026/3/6 下午 03:00:00
========================================

【按狀態統計】
--------------------
  待做: 5 個任務
  進行中: 8 個任務
  已完成: 12 個任務
  暫停: 2 個任務
  合計: 27 個任務

【詳細任務列表】
--------------------

[3/1 (日)]
  ✅ [已完成] 週報彙總 (⏱️ 1.5h)

[3/2 (一)]
  ✅ [已完成] 開會 (⏱️ 1h)
  ○ [待做] 編寫文檔
  ✅ [已完成] 代碼審核 (⏱️ 2h)

[3/5 (四)]
  ○ [進行中] 功能開發 (⏱️ 5h)
  ✅ [已完成] 測試 (⏱️ 2.5h)

========================================
```

### 关键技术点

1. **日期計算**:
   - `getDay()` 返回 0-6（0=週日）
   - 通過減去 `getDay()` 來定位周日
   - `padStart(2, '0')` 確保月日兩位數

2. **UTF-8 BOM**:
   - `\uFEFF` 是 UTF-8 標記
   - Windows 記事本 + Excel 會自動識別並正確顯示中文
   - 不加 BOM 在某些編輯器會亂碼

3. **Blob 和 URL**:
   - `Blob` 將文本轉為二進制數據
   - `URL.createObjectURL()` 建立可下載的連結
   - `URL.revokeObjectURL()` 釋放記憶體

4. **狀態判斷**:
   - `isTaskCompleted(t)` 檢查任務狀態（支持多種已完成的狀態表示）
   - 已完成顯示 ✅，其他顯示 ○

---

## 日視圖看板實現

### 實施位置
- **文件**: `frontend/src/views/DashboardView.vue`
- **行號**: 第 111-164 行
- **對應 API**: `GET /api/tasks?date=YYYY-MM-DD`

### 核心概念

日視圖是一個看板式布局，將任務按狀態（欄位）分組顯示，支持拖拽操作在欄位間移動任務。

### HTML 結構

```vue
<!-- 日視圖 - 看板 -->
<div v-if="!taskStore.loading && viewMode === 'day'" class="day-view" @click="closeAllExpandedCards">
  <div class="kanban-board">

    <!-- 動態欄位列 - 每個用戶的欄位都會生成一列 -->
    <div
      v-for="col in taskStore.columns"
      :key="col.id"
      class="kanban-column"
    >
      <!-- 欄位標題 -->
      <div class="column-header">
        <span>{{ col.name }}</span>
      </div>

      <!-- 任務列表區域 - 接收拖放的目標 -->
      <div
        class="task-list"
        @dragover.prevent="onDragOver"
        @drop="onDrop($event, col.name)"
      >

        <!-- 該欄位的所有任務 -->
        <div
          v-for="task in taskStore.tasksByStatus[col.name]"
          :key="task.id"
          :class="['task-card', {
            completed: col.name === '已完成',   <!-- 已完成欄位的任務卡片有特殊樣式 -->
            suspended: col.name === '暫停',     <!-- 暫停欄位的任務卡片有特殊樣式 -->
            expanded: expandedTasks.has(task.id), <!-- 展開狀態 -->
            readonly: taskStore.isAdmin         <!-- 管理員只讀模式 -->
          }]"
          :draggable="!taskStore.isAdmin"  <!-- 管理員無法拖拽 -->
          @dragstart="!taskStore.isAdmin && onDragStart($event, task, col.name)"
          @dragenter="!taskStore.isAdmin && onDragEnter"
          @dragleave="!taskStore.isAdmin && onDragLeave"
          @dragend="isDragging = false"
          @click.stop="!isDragging && handleTaskCardClick(task)"
        >
          <!-- 任務標題 -->
          <div class="task-title">
            <!-- 管理員模式顯示用戶名 -->
            <span v-if="taskStore.isAdmin && task.username">👤 {{ task.username }} - </span>
            {{ task.title }}
          </div>

          <!-- 展開時才顯示的詳細內容 -->
          <template v-if="expandedTasks.has(task.id)">

            <!-- 任務描述（富文本） -->
            <div v-if="task.description" class="task-description">
              <div v-html="sanitizeHtml(task.description)"></div>
            </div>

            <!-- 任務時長 -->
            <div v-if="task.duration > 0" class="task-duration">
              ⏱️ {{ task.duration }}h
            </div>

            <!-- 任務操作按鈕 -->
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
```

### 拖拽排序實現

#### 關鍵修復（March 3, 2026）
**問題**: 原本在 `task-card` 上設置 `@drop`，導致無法向空欄位拖放任務。
**解決**: 將 `@drop` 和 `@dragover` 移到 `task-list` 元素上。

#### 拖拽方法

```javascript
// ===== 拖拽開始 =====
const onDragStart = (event, task, sourceStatus) => {
  isDragging.value = true
  event.dataTransfer.effectAllowed = 'move'

  // 在拖拽時保存任務信息
  event.dataTransfer.setData('taskId', task.id)
  event.dataTransfer.setData('sourceStatus', sourceStatus)

  // 設置視覺反饋
  event.dataTransfer.setData('text/html', event.target.innerHTML)
}

// ===== 拖拽懸停 =====
const onDragOver = (event) => {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
  // 改變目標區域的視覺樣式
  event.target.classList.add('drag-over')
}

// ===== 拖拽進入 =====
const onDragEnter = (event) => {
  event.target.classList.add('drag-enter')
}

// ===== 拖拽離開 =====
const onDragLeave = (event) => {
  event.target.classList.remove('drag-enter')
}

// ===== 拖拽放下 =====
const onDrop = async (event, targetStatus) => {
  event.preventDefault()
  isDragging.value = false

  // 移除視覺反饋
  event.target.classList.remove('drag-over')

  // 取出保存的數據
  const taskId = event.dataTransfer.getData('taskId')
  const sourceStatus = event.dataTransfer.getData('sourceStatus')

  // 如果放到同一欄位，不執行任何操作
  if (sourceStatus === targetStatus) {
    return
  }

  // 調用 API 更新任務狀態
  try {
    await taskStore.updateTask(taskId, {
      status: targetStatus
    })

    // 重新加載任務列表
    await taskStore.fetchTasks(selectedDate.value)
  } catch (error) {
    console.error('更新任務失敗:', error)
  }
}
```

### 任務卡片展開/編輯

```javascript
// 追蹤哪些任務卡片被展開
const expandedTasks = new Set()

// 點擊任務卡片
const handleTaskCardClick = async (task) => {
  if (isDragging.value) {
    // 如果正在拖拽，不響應點擊
    return
  }

  // 切換展開狀態
  if (expandedTasks.has(task.id)) {
    expandedTasks.delete(task.id)  // 收起
  } else {
    expandedTasks.add(task.id)     // 展開
  }
}

// 打開編輯模態框
const openEditTaskModal = (task) => {
  selectedTask.value = JSON.parse(JSON.stringify(task))  // 深拷貝
  showEditTaskModal.value = true
}

// 關閉所有展開的卡片
const closeAllExpandedCards = () => {
  if (!isInModalOrInput()) {
    expandedTasks.clear()
  }
}
```

### 計算屬性

```javascript
// taskStore.js 中的計算屬性
const tasksByStatus = computed(() => {
  const grouped = {}

  // 先初始化所有欄位
  for (const col of columns.value) {
    grouped[col.name] = []
  }

  // 將任務按狀態分組
  for (const task of tasks.value) {
    if (grouped[task.status]) {
      grouped[task.status].push(task)
    }
  }

  return grouped
})

// 結果格式:
// {
//   "待做": [task1, task2],
//   "進行中": [task3, task4, task5],
//   "已完成": [task6, task7],
//   "暫停": []
// }
```

---

## 多視圖切換機制

### 實施位置
- **文件**: `frontend/src/views/DashboardView.vue`
- **視圖類型**: 日（day）、周（week）、月（month）

### 視圖選擇

```vue
<!-- 視圖切換 Tab -->
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

  <!-- 常態展開控制 -->
  <button v-if="!taskStore.isAdmin" @click="toggleAutoExpand" class="expand-control-btn">
    {{ autoExpandAll ? '✓ 常態展開' : '常態展開' }}
  </button>
</div>
```

### 日視圖 - 看板式

**用途**: 詳細查看和編輯單日的所有任務
**特點**:
- 4 列看板（根據用戶自定義欄位）
- 支持拖拽排序（狀態轉移）
- 任務卡片可展開查看詳情

**導航**:
```javascript
const selectedDate = ref(new Date())

const goToToday = () => {
  selectedDate.value = new Date()
  loadDayData()
}

const goToPreviousDay = async () => {
  selectedDate.value.setDate(selectedDate.value.getDate() - 1)
  await loadDayData()
}

const goToNextDay = async () => {
  selectedDate.value.setDate(selectedDate.value.getDate() + 1)
  await loadDayData()
}

const loadDayData = async () => {
  const y = selectedDate.value.getFullYear()
  const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
  const d = String(selectedDate.value.getDate()).padStart(2, '0')
  const dateStr = `${y}-${m}-${d}`

  await taskStore.fetchTasks(dateStr)
}
```

### 週視圖 - 網格式

**用途**: 概覽當周 7 天的任務總數
**特點**:
- 7 列網格（週日至週六）
- 每個格子顯示該日任務計數
- 點擊日期切換到日視圖
- 「📥 匯出本周」按鈕生成報告

**數據計算**:
```javascript
const currentWeekStart = ref(new Date())

const weekDays = computed(() => {
  const start = new Date(currentWeekStart.value)
  start.setDate(start.getDate() - start.getDay())  // 週日

  const days = []
  for (let i = 0; i < 7; i++) {
    const date = new Date(start)
    date.setDate(start.getDate() + i)
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const dateStr = `${y}-${m}-${d}`

    // 從 tasksCache 獲取該日任務
    const dayTasks = tasksCache.value[dateStr] || []
    const completedCount = dayTasks.filter(t => isTaskCompleted(t)).length

    days.push({
      date: date,
      dateStr: dateStr,
      displayDate: `${date.getMonth() + 1}/${date.getDate()}`,
      weekLabel: ['日','一','二','三','四','五','六'][date.getDay()],
      isToday: isSameDay(date, new Date()),
      taskCount: dayTasks.length,
      completedCount: completedCount
    })
  }
  return days
})

const switchToDay = (dateStr) => {
  const [y, m, d] = dateStr.split('-')
  selectedDate.value = new Date(y, m - 1, d)  // 月份需要 -1
  viewMode.value = 'day'
  loadDayData()
}
```

**HTML**:
```vue
<div v-if="!taskStore.loading && viewMode === 'week'" class="week-view">
  <div class="week-grid">
    <div
      v-for="(day, index) in weekDays"
      :key="index"
      :class="['week-day-card', { today: day.isToday, 'has-tasks': day.taskCount > 0 }]"
      @click="switchToDay(day.dateStr)"
    >
      <div v-if="day.isToday" class="today-badge">今日</div>

      <div class="week-day-header">
        <div class="week-date">{{ day.displayDate }}</div>
        <div class="week-weekday">{{ day.weekLabel }}</div>
      </div>

      <div class="week-day-content">
        <div class="task-count">{{ day.taskCount }} 項</div>
        <div class="completed-count">✅ {{ day.completedCount }}</div>
      </div>
    </div>
  </div>
</div>
```

### 月視圖 - 日曆式

**用途**: 概覽整個月的任務分佈
**特點**:
- 日曆網格（前月補齊、當月、後月）
- 顯示每日任務計數
- 點擊日期切換到日視圖

**數據計算**:
```javascript
const currentMonth = ref(new Date())

const monthDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()

  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const firstDayOfWeek = firstDay.getDay()

  const days = []

  // 前月補齊
  const prevMonth = new Date(year, month, 0)
  const prevLastDate = prevMonth.getDate()
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    days.push({
      date: prevLastDate - i,
      currentMonth: false,
      taskCount: 0
    })
  }

  // 當月
  for (let date = 1; date <= lastDay.getDate(); date++) {
    const y = year
    const m = String(month + 1).padStart(2, '0')
    const d = String(date).padStart(2, '0')
    const dateStr = `${y}-${m}-${d}`

    const dayTasks = tasksCache.value[dateStr] || []
    days.push({
      date: date,
      currentMonth: true,
      dateStr: dateStr,
      isToday: isSameDay(new Date(y, month, date), new Date()),
      taskCount: dayTasks.length,
      completedCount: dayTasks.filter(t => isTaskCompleted(t)).length
    })
  }

  // 後月補齊
  const totalCells = Math.ceil((firstDayOfWeek + lastDay.getDate()) / 7) * 7
  for (let date = 1; date <= totalCells - firstDayOfWeek - lastDay.getDate(); date++) {
    days.push({
      date: date,
      currentMonth: false,
      taskCount: 0
    })
  }

  return days
})
```

### 視圖切換時的數據加載

```javascript
watch(
  () => viewMode.value,
  async (newMode) => {
    switch (newMode) {
      case 'day':
        await loadDayData()
        break
      case 'week':
        await loadWeekData()
        break
      case 'month':
        await loadMonthData()
        break
    }
  }
)

// 批量加載週視圖數據
const loadWeekData = async () => {
  const start = new Date(currentWeekStart.value)
  start.setDate(start.getDate() - start.getDay())

  const end = new Date(start)
  end.setDate(start.getDate() + 6)

  // 使用 fetchTasksForRange 批量加載 7 天的數據
  await taskStore.fetchTasksForRange(start, end)
}

// 批量加載月視圖數據
const loadMonthData = async () => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()

  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  await taskStore.fetchTasksForRange(firstDay, lastDay)
}
```

---

## 自定義欄位管理

### 實施位置
- **文件**:
  - 前端: `frontend/src/views/ColumnsManagementView.vue`（獨立頁面）
  - 後端: `backend/routes/columns.php`
- **路由**: `#/columns`
- **導航**: DashboardView 頂部「⚙️ 管理欄位」按鈕

### 功能

#### 1. 創建欄位

**API 調用**:
```javascript
// stores/taskStore.js
const addColumn = async (name) => {
  try {
    const response = await fetch('http://localhost:6001/api/columns', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',  // 帶上 Cookie（Session）
      body: JSON.stringify({ name })
    })
    const data = await response.json()

    if (data.success) {
      // 添加到本地狀態
      columns.value.push(data.data)
      return data.data
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    error.value = error.message
    throw error
  }
}
```

**後端實現**:
```php
// backend/routes/columns.php
function handleColumns($method, $id, $db) {
  $user_id = $_SESSION['user_id'] ?? null;
  if (!$user_id) {
    Response::error('未授權', 401);
    return;
  }

  switch ($method) {
    case 'POST':
      createColumn($user_id, $db);
      break;
    // ...
  }
}

function createColumn($user_id, $db) {
  $input = json_decode(file_get_contents('php://input'), true);
  $name = $input['name'] ?? null;

  if (!$name) {
    Response::error('欄位名稱不能為空', 400);
    return;
  }

  // 檢查欄位名是否已存在
  $stmt = $db->prepare('SELECT id FROM user_columns WHERE user_id = ? AND name = ?');
  $stmt->bind_param('is', $user_id, $name);
  $stmt->execute();

  if ($stmt->get_result()->num_rows > 0) {
    Response::error('欄位名稱已存在', 400);
    return;
  }

  // 取得最大的排序號
  $result = $db->query('SELECT MAX(col_order) as max_order FROM user_columns WHERE user_id = ' . $user_id);
  $max_order = $result->fetch_assoc()['max_order'] ?? -1;

  // 插入新欄位
  $stmt = $db->prepare('INSERT INTO user_columns (user_id, name, col_order) VALUES (?, ?, ?)');
  $new_order = $max_order + 1;
  $stmt->bind_param('isi', $user_id, $name, $new_order);

  if ($stmt->execute()) {
    Response::success([
      'id' => $db->insert_id,
      'name' => $name,
      'col_order' => $new_order
    ], '欄位創建成功', 201);
  } else {
    Response::error('創建欄位失敗', 500);
  }
}
```

#### 2. 編輯欄位名稱

**前端**:
```javascript
const saveColumnName = async (columnId, newName) => {
  try {
    const response = await fetch(`http://localhost:6001/api/columns/${columnId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ name: newName })
    })
    const data = await response.json()

    if (data.success) {
      // 關鍵修復（March 3）：先更新本地，再退出編輯模式
      const column = columns.value.find(c => c.id === columnId)
      column.name = newName
      editingColumnId.value = null  // 退出編輯模式
      return data.data
    }
  } catch (error) {
    console.error(error)
  }
}
```

**後端**（包含自動遷移）:
```php
function updateColumn($user_id, $id, $db) {
  $input = json_decode(file_get_contents('php://input'), true);
  $newName = $input['name'] ?? null;

  // 取得舊名稱
  $stmt = $db->prepare('SELECT name FROM user_columns WHERE id = ? AND user_id = ?');
  $stmt->bind_param('ii', $id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if (!$result) {
    Response::error('欄位不存在', 404);
    return;
  }

  $oldName = $result['name'];

  // 開始事務（確保原子性）
  $db->begin_transaction();

  try {
    // 1. 更新欄位名稱
    $stmt = $db->prepare('UPDATE user_columns SET name = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('sii', $newName, $id, $user_id);
    $stmt->execute();

    // 2. 自動遷移任務狀態
    $stmt = $db->prepare('UPDATE tasks SET status = ? WHERE status = ? AND user_id = ?');
    $stmt->bind_param('ssi', $newName, $oldName, $user_id);
    $stmt->execute();

    $db->commit();

    Response::success([
      'id' => $id,
      'name' => $newName
    ], '欄位更新成功');

  } catch (Exception $e) {
    $db->rollback();
    Response::error('更新失敗: ' . $e->getMessage(), 500);
  }
}
```

#### 3. 刪除欄位

**前端**:
```javascript
const deleteColumn = async (columnId) => {
  if (!confirm('確定要刪除此欄位？該欄位的任務將移到「待做」')) {
    return
  }

  try {
    const response = await fetch(`http://localhost:6001/api/columns/${columnId}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include'
    })
    const data = await response.json()

    if (data.success) {
      columns.value = columns.value.filter(c => c.id !== columnId)
    }
  } catch (error) {
    console.error(error)
  }
}
```

**後端**:
```php
function deleteColumn($user_id, $id, $db) {
  $db->begin_transaction();

  try {
    // 1. 將該欄位的任務移到「待做」
    $stmt = $db->prepare('SELECT name FROM user_columns WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
      Response::error('欄位不存在', 404);
      return;
    }

    $columnName = $result['name'];

    $stmt = $db->prepare('UPDATE tasks SET status = ? WHERE status = ? AND user_id = ?');
    $todo = '待做';
    $stmt->bind_param('ssi', $todo, $columnName, $user_id);
    $stmt->execute();

    // 2. 刪除欄位
    $stmt = $db->prepare('DELETE FROM user_columns WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();

    $db->commit();
    Response::success([], '欄位刪除成功');

  } catch (Exception $e) {
    $db->rollback();
    Response::error('刪除失敗: ' . $e->getMessage(), 500);
  }
}
```

#### 4. 拖拽重新排序

**前端**（使用 vue-draggable-plus）:
```javascript
import { VueDraggable } from 'vue-draggable-plus'

export default {
  components: {
    VueDraggable
  }
}
```

**HTML**:
```vue
<VueDraggable
  v-model="columns"
  @update="reorderColumns"
  class="columns-list"
>
  <div v-for="col in columns" :key="col.id" class="column-item">
    {{ col.name }}
  </div>
</VueDraggable>
```

**重新排序方法**:
```javascript
const reorderColumns = async () => {
  try {
    const order = columns.value.map((col, index) => ({
      id: col.id,
      col_order: index
    }))

    const response = await fetch('http://localhost:6001/api/columns/reorder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(order)
    })

    const data = await response.json()

    if (data.success) {
      // 樂觀更新已經反映在 UI 上
      console.log('欄位順序已更新')
    }
  } catch (error) {
    // 失敗時回滾（刷新數據）
    await fetchColumns()
    console.error('排序失敗:', error)
  }
}
```

**後端**:
```php
function reorderColumns($user_id, $db) {
  $input = json_decode(file_get_contents('php://input'), true);

  $db->begin_transaction();

  try {
    foreach ($input as $item) {
      $stmt = $db->prepare('UPDATE user_columns SET col_order = ? WHERE id = ? AND user_id = ?');
      $stmt->bind_param('iii', $item['col_order'], $item['id'], $user_id);
      $stmt->execute();
    }

    $db->commit();
    Response::success([], '排序已更新');

  } catch (Exception $e) {
    $db->rollback();
    Response::error('更新失敗: ' . $e->getMessage(), 500);
  }
}
```

---

## 任務編輯與保存流程

### 實施位置
- **文件**: `frontend/src/views/DashboardView.vue`
- **對應 API**: `PUT /api/tasks/:id`
- **模態框**: `showEditTaskModal`

### 完整流程

#### 1. 打開編輯模態框

```javascript
const selectedTask = ref(null)
const showEditTaskModal = ref(false)

const openEditTaskModal = (task) => {
  // 深拷貝任務，避免修改原數據
  selectedTask.value = JSON.parse(JSON.stringify(task))
  showEditTaskModal.value = true
}
```

#### 2. 編輯表單

```vue
<div v-if="showEditTaskModal && selectedTask" class="modal">
  <div class="modal-content">
    <h2>編輯工作</h2>

    <!-- 標題 -->
    <div class="form-group">
      <label>標題</label>
      <input v-model="selectedTask.title" type="text" placeholder="輸入標題">
    </div>

    <!-- 描述（富文本編輯器） -->
    <div class="form-group">
      <label>描述</label>
      <RichTextEditor v-model="selectedTask.description" />
    </div>

    <!-- 時長 -->
    <div class="form-group">
      <label>時長（小時）</label>
      <input v-model.number="selectedTask.duration" type="number" min="0" step="0.5">
    </div>

    <!-- 狀態 -->
    <div class="form-group">
      <label>狀態</label>
      <select v-model="selectedTask.status">
        <option v-for="col in taskStore.columns" :key="col.id" :value="col.name">
          {{ col.name }}
        </option>
      </select>
    </div>

    <!-- 圖片上傳 -->
    <div class="form-group">
      <label>圖片</label>
      <input type="file" @change="onImageSelect" accept="image/*">
      <img v-if="selectedTask.image_url" :src="selectedTask.image_url" class="preview">
    </div>

    <!-- 按鈕 -->
    <button @click="handleUpdateTask">保存</button>
    <button @click="showEditTaskModal = false">取消</button>
  </div>
</div>
```

#### 3. 保存任務

```javascript
const handleUpdateTask = async () => {
  try {
    // 更新任務基本信息
    await taskStore.updateTask(selectedTask.value.id, {
      title: selectedTask.value.title,
      description: selectedTask.value.description,
      status: selectedTask.value.status,
      duration: selectedTask.value.duration
    })

    // 如果有新圖片要上傳
    if (pendingImageFile.value) {
      await uploadTaskImage(selectedTask.value.id, pendingImageFile.value)
      pendingImageFile.value = null
    }

    // 關閉模態框
    showEditTaskModal.value = false

    // 重新加載任務
    await taskStore.fetchTasks(selectedDate.value)
  } catch (error) {
    console.error('保存失敗:', error)
  }
}
```

#### 4. 後端更新邏輯

```php
// backend/routes/tasks.php
function updateTask($user_id, $task_id, $db) {
  $input = json_decode(file_get_contents('php://input'), true);

  // 驗證任務所有者
  $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
  $stmt->bind_param('ii', $task_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    Response::error('任務不存在或無權限', 404);
    return;
  }

  $task = $result->fetch_assoc();

  // 記錄舊值（用於異動記錄）
  $oldValues = [
    'title' => $task['title'],
    'description' => $task['description'],
    'status' => $task['status'],
    'duration' => $task['duration']
  ];

  // 更新字段
  $updateFields = [];
  $bindTypes = '';
  $bindValues = [];

  if (isset($input['title'])) {
    $updateFields[] = 'title = ?';
    $bindTypes .= 's';
    $bindValues[] = &$input['title'];
  }

  if (isset($input['description'])) {
    $updateFields[] = 'description = ?';
    $bindTypes .= 's';
    $bindValues[] = &$input['description'];
  }

  if (isset($input['status'])) {
    $updateFields[] = 'status = ?';
    $bindTypes .= 's';
    $bindValues[] = &$input['status'];
  }

  if (isset($input['duration']) && is_numeric($input['duration']) && $input['duration'] >= 0) {
    $updateFields[] = 'duration = ?';
    $bindTypes .= 'd';
    $bindValues[] = &$input['duration'];
  }

  if (empty($updateFields)) {
    Response::error('沒有要更新的字段', 400);
    return;
  }

  // 執行更新
  $bindTypes .= 'ii';  // 加上 id 和 user_id
  $bindValues[] = &$task_id;
  $bindValues[] = &$user_id;

  $sql = 'UPDATE tasks SET ' . implode(', ', $updateFields) . ' WHERE id = ? AND user_id = ?';
  $stmt = $db->prepare($sql);

  call_user_func_array(
    [$stmt, 'bind_param'],
    array_merge([$bindTypes], $bindValues)
  );

  if (!$stmt->execute()) {
    Response::error('更新失敗', 500);
    return;
  }

  // 記錄異動（task_history）
  recordTaskHistory($user_id, $task_id, 'update', $oldValues, $input, $db);

  Response::success(['id' => $task_id], '任務已更新');
}
```

---

## 圖片上傳功能

### 實施位置
- **前端**: `DashboardView.vue`
- **後端**: `backend/routes/tasks.php` 中的 `uploadTaskImage()`
- **存儲**: `backend/uploads/` 目錄

### 上傳流程

#### 1. 前端圖片選擇

```javascript
const pendingImageFile = ref(null)

const onImageSelect = (event) => {
  const file = event.target.files?.[0]

  if (!file) return

  // 驗證文件類型
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    alert('只支持 JPG, PNG, GIF, WebP 格式')
    return
  }

  // 驗證文件大小（最大 5MB）
  if (file.size > 5 * 1024 * 1024) {
    alert('圖片大小不能超過 5MB')
    return
  }

  pendingImageFile.value = file

  // 顯示預覽
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target.result
  }
  reader.readAsDataURL(file)
}
```

#### 2. 上傳到後端

```javascript
const uploadTaskImage = async (taskId, file) => {
  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await fetch(`http://localhost:6001/api/tasks/${taskId}/upload`, {
      method: 'POST',
      credentials: 'include',  // 帶上 Session Cookie
      body: formData
    })

    const data = await response.json()

    if (data.success) {
      // 更新任務的 image_url
      selectedTask.value.image_url = data.data.image_url
      return data.data
    } else {
      throw new Error(data.message)
    }
  } catch (error) {
    console.error('上傳失敗:', error)
    throw error
  }
}
```

#### 3. 後端處理

```php
// backend/routes/tasks.php
function uploadTaskImage($user_id, $task_id, $db) {
  // 驗證任務所有者
  $stmt = $db->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
  $stmt->bind_param('ii', $task_id, $user_id);
  $stmt->execute();

  if ($stmt->get_result()->num_rows === 0) {
    Response::error('任務不存在', 404);
    return;
  }

  // 檢查是否有上傳文件
  if (!isset($_FILES['image'])) {
    Response::error('沒有圖片文件', 400);
    return;
  }

  $file = $_FILES['image'];

  // 驗證上傳成功
  if ($file['error'] !== UPLOAD_ERR_OK) {
    Response::error('文件上傳失敗: ' . $file['error'], 400);
    return;
  }

  // 驗證文件類型（MIME type）
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!in_array($mimeType, $allowedMimes)) {
    Response::error('不支持的圖片格式', 400);
    return;
  }

  // 驗證文件大小（5MB）
  if ($file['size'] > 5 * 1024 * 1024) {
    Response::error('圖片大小不能超過 5MB', 400);
    return;
  }

  // 生成唯一的文件名
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'task_' . $task_id . '_' . time() . '.' . strtolower($ext);
  $uploadDir = __DIR__ . '/../uploads/';
  $uploadPath = $uploadDir . $filename;

  // 確保上傳目錄存在
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // 移動上傳的文件
  if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    Response::error('無法保存圖片文件', 500);
    return;
  }

  // 刪除舊圖片（如果存在）
  $stmt = $db->prepare('SELECT image_url FROM tasks WHERE id = ?');
  $stmt->bind_param('i', $task_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if ($result && $result['image_url']) {
    // 提取文件名
    $oldFile = basename($result['image_url']);
    $oldPath = $uploadDir . $oldFile;
    if (file_exists($oldPath)) {
      unlink($oldPath);
    }
  }

  // 更新數據庫
  $imageUrl = 'http://localhost:6001/uploads/' . $filename;
  $stmt = $db->prepare('UPDATE tasks SET image_url = ? WHERE id = ? AND user_id = ?');
  $stmt->bind_param('sii', $imageUrl, $task_id, $user_id);

  if ($stmt->execute()) {
    Response::success([
      'image_url' => $imageUrl,
      'filename' => $filename
    ], '圖片上傳成功');
  } else {
    Response::error('保存圖片信息失敗', 500);
  }
}
```

#### 4. 圖片展示

```vue
<!-- 任務卡片中顯示圖片 -->
<div v-if="task.image_url" class="task-image">
  <img :src="task.image_url" :alt="task.title" class="task-img">
</div>
```

**CSS**（March 3 修復）:
```css
.task-image {
  margin-top: 10px;
  overflow: hidden;
  border-radius: 4px;
}

.task-img {
  width: 100%;           /* 填滿容器寬度 */
  height: auto;
  object-fit: cover;     /* 按比例裁剪 */
  display: block;        /* 移除內嵌空間 */
  max-height: 150px;
}
```

---

## 異動記錄追蹤

### 實施位置
- **前端**: `HistoryView.vue`
- **後端**: `backend/routes/tasks.php` 中的 `recordTaskHistory()`
- **數據表**: `task_history`

### 後端記錄

每當任務發生 create/update/delete 操作，都會自動記錄：

```php
// backend/routes/tasks.php
function recordTaskHistory($user_id, $task_id, $action, $oldValues, $newValues, $db) {
  $oldValuesJson = json_encode($oldValues);
  $newValuesJson = json_encode($newValues);

  $stmt = $db->prepare(
    'INSERT INTO task_history (task_id, user_id, action, old_value, new_value)
     VALUES (?, ?, ?, ?, ?)'
  );

  $stmt->bind_param(
    'iisss',
    $task_id,
    $user_id,
    $action,
    $oldValuesJson,
    $newValuesJson
  );

  return $stmt->execute();
}
```

### 前端展示

```vue
<!-- HistoryView.vue -->
<div class="history-container">
  <!-- 篩選器 -->
  <div class="filters">
    <input v-model="filterTask" placeholder="篩選任務標題">
    <select v-model="filterAction">
      <option value="">所有操作</option>
      <option value="create">創建</option>
      <option value="update">更新</option>
      <option value="delete">刪除</option>
    </select>
  </div>

  <!-- 時間線 -->
  <div class="timeline">
    <div v-for="record in filteredHistory" :key="record.id" class="timeline-item">
      <div class="timeline-dot" :class="record.action"></div>
      <div class="timeline-content">
        <span class="action-icon">
          {{ record.action === 'create' ? '➕' : record.action === 'delete' ? '🗑️' : '✏️' }}
        </span>
        <span class="action-text">{{ getActionText(record.action) }}</span>
        <span class="time">{{ formatTime(record.created_at) }}</span>
        <span class="user">by {{ record.username }}</span>

        <div v-if="record.action === 'update'" class="changes">
          <p v-for="(newVal, key) in JSON.parse(record.new_value)" :key="key">
            <strong>{{ key }}:</strong>
            {{ JSON.parse(record.old_value)[key] }} → {{ newVal }}
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
```

---

## 管理員只讀模式

### 實施位置
- **前端**: `DashboardView.vue` 多處
- **後端**: `backend/routes/tasks.php` 中的 `getTasksList()`

### 後端實現

```php
// backend/routes/tasks.php
function getTasksList($user_id, $date, $db) {
  // 檢查是否為管理員
  $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $userRole = $result['role'] ?? 'user';

  if ($userRole === 'admin') {
    // 管理員模式：返回所有用戶的任務 + username
    $sql = '
      SELECT
        t.*,
        u.username
      FROM tasks t
      JOIN users u ON t.user_id = u.id
      WHERE t.created_date = ?
      ORDER BY t.created_at DESC
    ';
  } else {
    // 普通用戶：只返回自己的任務
    $sql = '
      SELECT *
      FROM tasks
      WHERE user_id = ? AND created_date = ?
      ORDER BY created_at DESC
    ';
  }

  $stmt = $db->prepare($sql);

  if ($userRole === 'admin') {
    $stmt->bind_param('s', $date);
  } else {
    $stmt->bind_param('is', $user_id, $date);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $tasks = [];
  while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
  }

  Response::success($tasks);
}
```

### 前端實現

```javascript
// stores/userStore.js - 在登錄時保存角色
const user = ref({
  id: null,
  username: null,
  email: null,
  role: 'user'  // 'user' 或 'admin'
})

// stores/taskStore.js - 暴露管理員狀態
const isAdmin = computed(() => userStore.user.role === 'admin')
```

**UI 控制**:

```vue
<!-- 1. 禁用拖拽 -->
<div
  :draggable="!taskStore.isAdmin"
  @dragstart="!taskStore.isAdmin && onDragStart(...)"
>

<!-- 2. 隱藏編輯按鈕 -->
<button v-if="!taskStore.isAdmin" class="edit-btn" @click="openEditTaskModal(task)">
  ✏️ 編輯
</button>

<!-- 3. 隱藏新建按鈕 -->
<button v-if="!taskStore.isAdmin" @click="showCreateTaskModal = true">
  ➕ 新建工作
</button>

<!-- 4. 隱藏欄位管理 -->
<button v-if="!taskStore.isAdmin" @click="goToColumnsManagement">
  ⚙️ 管理欄位
</button>

<!-- 5. 隱藏異動記錄 -->
<button v-if="!taskStore.isAdmin" @click="goToHistory">
  📋 異動記錄
</button>

<!-- 6. 任務標題前顯示用戶名 -->
<span v-if="taskStore.isAdmin && task.username">👤 {{ task.username }} - </span>

<!-- 7. 任務卡片只讀樣式 -->
<div :class="['task-card', { readonly: taskStore.isAdmin }]">
```

**CSS**:
```css
.task-card.readonly {
  opacity: 0.8;
  cursor: default;  /* 無法拖拽 */
}

.task-card.readonly:hover {
  transform: none;  /* 無懸停效果 */
}
```

---

## 狀態管理詳解

### userStore.js

```javascript
// 定義
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUserStore = defineStore('user', () => {
  // 狀態
  const user = ref({
    id: null,
    username: null,
    email: null,
    role: 'user'
  })
  const isAuthenticated = ref(false)
  const loading = ref(false)
  const error = ref(null)

  // 登錄
  const login = async (username, password) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('http://localhost:6001/api/auth?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',  // 保存 Cookie
        body: JSON.stringify({ username, password })
      })

      const data = await response.json()

      if (data.success) {
        user.value = data.data
        isAuthenticated.value = true
        return true
      } else {
        error.value = data.message
        return false
      }
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // 註冊
  const register = async (username, email, password) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('http://localhost:6001/api/auth?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, email, password })
      })

      const data = await response.json()

      if (data.success) {
        error.value = null
        return true
      } else {
        error.value = data.message
        return false
      }
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // 登出
  const logout = () => {
    user.value = { id: null, username: null, email: null, role: 'user' }
    isAuthenticated.value = false
  }

  return {
    user,
    isAuthenticated,
    loading,
    error,
    login,
    register,
    logout
  }
})
```

### taskStore.js

```javascript
export const useTaskStore = defineStore('task', () => {
  const tasks = ref([])
  const columns = ref([])
  const loading = ref(false)
  const error = ref(null)
  const tasksCache = ref({})  // 按日期緩存任務

  // 計算已登入用戶的角色
  const userStore = useUserStore()
  const isAdmin = computed(() => userStore.user.role === 'admin')

  // 取得單日任務
  const fetchTasks = async (dateStr) => {
    loading.value = true
    try {
      const response = await fetch(`http://localhost:6001/api/tasks?date=${dateStr}`, {
        credentials: 'include'
      })
      const data = await response.json()

      if (data.success) {
        tasks.value = data.data
        tasksCache.value[dateStr] = data.data  // 緩存
      }
    } finally {
      loading.value = false
    }
  }

  // 批量取得日期範圍的任務
  const fetchTasksForRange = async (startDate, endDate) => {
    loading.value = true
    try {
      const requests = []
      const current = new Date(startDate)

      while (current <= endDate) {
        const y = current.getFullYear()
        const m = String(current.getMonth() + 1).padStart(2, '0')
        const d = String(current.getDate()).padStart(2, '0')
        const dateStr = `${y}-${m}-${d}`

        requests.push(
          fetch(`http://localhost:6001/api/tasks?date=${dateStr}`, {
            credentials: 'include'
          }).then(res => res.json())
        )

        current.setDate(current.getDate() + 1)
      }

      const results = await Promise.all(requests)

      // 將所有結果合併到緩存
      results.forEach((data, index) => {
        if (data.success) {
          const current = new Date(startDate)
          current.setDate(current.getDate() + index)
          const y = current.getFullYear()
          const m = String(current.getMonth() + 1).padStart(2, '0')
          const d = String(current.getDate()).padStart(2, '0')
          const dateStr = `${y}-${m}-${d}`
          tasksCache.value[dateStr] = data.data
        }
      })
    } finally {
      loading.value = false
    }
  }

  // 建立任務
  const createTask = async (task) => {
    try {
      const response = await fetch('http://localhost:6001/api/tasks', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(task)
      })
      const data = await response.json()

      if (data.success) {
        tasks.value.push(data.data)
        return data.data
      }
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  // 更新任務
  const updateTask = async (taskId, updates) => {
    try {
      const response = await fetch(`http://localhost:6001/api/tasks/${taskId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(updates)
      })
      const data = await response.json()

      if (data.success) {
        const index = tasks.value.findIndex(t => t.id === taskId)
        if (index >= 0) {
          tasks.value[index] = { ...tasks.value[index], ...updates }
        }
        return data.data
      }
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  // 刪除任務
  const deleteTask = async (taskId) => {
    try {
      const response = await fetch(`http://localhost:6001/api/tasks/${taskId}`, {
        method: 'DELETE',
        credentials: 'include'
      })
      const data = await response.json()

      if (data.success) {
        tasks.value = tasks.value.filter(t => t.id !== taskId)
      }
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  // 按狀態分組
  const tasksByStatus = computed(() => {
    const grouped = {}

    // 初始化所有欄位
    for (const col of columns.value) {
      grouped[col.name] = []
    }

    // 分組
    for (const task of tasks.value) {
      if (grouped[task.status]) {
        grouped[task.status].push(task)
      }
    }

    return grouped
  })

  // 欄位管理
  const fetchColumns = async () => {
    try {
      const response = await fetch('http://localhost:6001/api/columns', {
        credentials: 'include'
      })
      const data = await response.json()

      if (data.success) {
        columns.value = data.data.sort((a, b) => a.col_order - b.col_order)
      }
    } catch (err) {
      console.error(err)
    }
  }

  return {
    tasks,
    columns,
    loading,
    error,
    isAdmin,
    tasksCache,
    tasksByStatus,
    fetchTasks,
    fetchTasksForRange,
    createTask,
    updateTask,
    deleteTask,
    fetchColumns
  }
})
```

---

## API 交互流程

### 典型流程：創建任務

```
1. 用戶點擊「➕ 新建工作」按鈕
   ↓
2. 前端打開模態框 (showCreateTaskModal = true)
   ↓
3. 用戶填寫標題、描述、日期
   ↓
4. 用戶點擊「創建」按鈕
   ↓
5. 前端調用 taskStore.createTask()
   ↓
6. HTTP POST 請求發送到 http://localhost:6001/api/tasks
   {
     "title": "新任務",
     "description": "<p>描述</p>",
     "created_date": "2026-03-06"
   }
   ↓
7. 後端 routes/tasks.php 接收請求
   ├─ 驗證用戶登入
   ├─ 驗證必填字段
   ├─ 查詢該用戶的第一個欄位（默認狀態）
   ├─ INSERT 到 tasks 表
   └─ 記錄到 task_history 表
   ↓
8. 後端返回 JSON 響應
   {
     "success": true,
     "data": {
       "id": 123,
       "title": "新任務",
       "status": "待做",
       "duration": 0,
       ...
     }
   }
   ↓
9. 前端接收響應，更新本地 tasks 數組
   ↓
10. 關閉模態框，重新加載任務列表
    ↓
11. UI 更新，顯示新任務在看板上
```

### 典型流程：拖拽排序

```
1. 用戶在看板上拖拽任務卡片
   ↓
2. @dragstart 事件觸發
   └─ 保存任務 ID 和源欄位到 dataTransfer
   ↓
3. 拖拽移動，@dragover 事件觸發
   └─ 改變目標欄位的視覺反饋
   ↓
4. 用戶放開鼠標，@drop 事件觸發
   ↓
5. 前端驗證：
   ├─ 檢查新狀態是否不同
   ├─ 調用 taskStore.updateTask(taskId, { status: newStatus })
   └─ HTTP PUT 請求到 http://localhost:6001/api/tasks/{taskId}
   ↓
6. 後端驗證用戶權限，更新 tasks 表
   ↓
7. 後端返回成功響應
   ↓
8. 前端重新加載任務列表
   ↓
9. 看板重新渲染，任務出現在新欄位
```

---

**文檔完成日期**: 2026年3月6日
**包含內容**: 10 個核心功能的詳細實施說明
**代碼行數**: 2000+ 行代碼示例和說明
