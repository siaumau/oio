import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiConfig from '../config/apiConfig'

export const useTaskStore = defineStore('task', () => {
  // 状态
  const tasks = ref([])
  const columns = ref([])
  const selectedDate = ref(new Date().toISOString().split('T')[0])
  const loading = ref(false)
  const error = ref(null)
  const taskImagesCache = ref({}) // 缓存任务的附件图片
  const isAdmin = ref(false) // 当前用户是否为管理员

  // 计算属性 - 按状态分组（动态，根据 columns 生成）
  const tasksByStatus = computed(() => {
    const grouped = {}

    // 初始化每个栏位的空数组
    columns.value.forEach(col => {
      grouped[col.name] = []
    })

    // 分组任务
    tasks.value.forEach(task => {
      if (grouped[task.status]) {
        grouped[task.status].push(task)
      } else if (grouped['待做']) {
        // 兜底：如果工作状態對應的欄位不存在，移到 '待做'
        grouped['待做'].push(task)
      }
    })

    return grouped
  })

  // 方法 - 获取任务列表（单日期）
  const fetchTasks = async (date) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks?date=${date}`,
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

      tasks.value = data.data.tasks || []
      selectedDate.value = date
      isAdmin.value = data.data.isAdmin || false

      return true
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // 方法 - 获取日期范围内的任务（用于周/月视图）
  const fetchTasksForRange = async (startDate, endDate) => {
    try {
      const tasksMap = {}
      const current = new Date(startDate)

      // 生成日期范围内的所有日期
      const dates = []
      while (current <= new Date(endDate)) {
        dates.push(current.toISOString().split('T')[0])
        current.setDate(current.getDate() + 1)
      }

      // 使用 Promise.all 并行请求所有日期的任务
      const responses = await Promise.all(
        dates.map(date =>
          fetch(`${apiConfig.API_BASE_URL}/api/tasks?date=${date}`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' }
          })
        )
      )

      // 解析所有响应
      for (let i = 0; i < responses.length; i++) {
        const data = await responses[i].json()
        if (data.success) {
          tasksMap[dates[i]] = data.data.tasks || []
        }
      }

      return tasksMap
    } catch (err) {
      error.value = err.message
      return {}
    }
  }

  // 方法 - 创建任务
  const createTask = async (title, description = '', createdDate = null) => {
    if (!title.trim()) {
      error.value = '工作標題不能為空'
      return false
    }

    const date = createdDate || selectedDate.value

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/tasks`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: title.trim(),
          description,
          created_date: date
        })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 添加到任务列表
      const newTask = {
        id: data.data.id,
        title: data.data.title,
        description: data.data.description,
        status: data.data.status,
        duration: data.data.duration || 0,
        created_date: data.data.created_date,
        task_order: tasks.value.length
      }
      tasks.value.push(newTask)

      return newTask
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 更新任务状态
  const updateTaskStatus = async (taskId, newStatus) => {
    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/tasks/${taskId}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 更新本地任务
      const task = tasks.value.find(t => t.id === taskId)
      if (task) {
        task.status = newStatus
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 更新任务信息（标题、描述、工时）
  const updateTaskInfo = async (taskId, updates) => {
    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/tasks/${taskId}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 檢查日期是否改變
      const dateChanged = updates.date && updates.date !== selectedDate.value

      // 更新本地任務
      const task = tasks.value.find(t => t.id === taskId)
      if (task) {
        if (updates.title) task.title = updates.title
        if (updates.description !== undefined) task.description = updates.description
        if (updates.duration !== undefined) task.duration = updates.duration
        if (updates.date !== undefined) task.date = updates.date
      }

      // 如果日期改變，需要重新加載任務列表
      if (dateChanged) {
        await fetchTasks(selectedDate.value)
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 删除任务
  const deleteTask = async (taskId) => {
    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/tasks/${taskId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 从列表中移除
      tasks.value = tasks.value.filter(t => t.id !== taskId)

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 改变日期
  const changeDate = (date) => {
    selectedDate.value = date
    return fetchTasks(date)
  }

  // ========================================
  // 栏位管理方法
  // ========================================

  // 方法 - 取得欄位清單
  const fetchColumns = async () => {
    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/columns`,
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

      columns.value = data.data.columns || []
      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 新增欄位
  const addColumn = async (name) => {
    if (!name.trim()) {
      error.value = '欄位名稱不能為空'
      return false
    }

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/columns`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name.trim() })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 新增到本地清單
      columns.value.push({
        id: data.data.id,
        name: data.data.name,
        col_order: data.data.col_order,
        is_enabled: data.data.is_enabled
      })

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 更新欄位名稱
  const updateColumn = async (columnId, newName) => {
    if (!newName.trim()) {
      error.value = '欄位名稱不能為空'
      return false
    }

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/columns/${columnId}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: newName.trim() })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 更新本地栏位
      const col = columns.value.find(c => c.id === columnId)
      if (col) {
        col.name = newName
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 刪除欄位
  const deleteColumn = async (columnId) => {
    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/columns/${columnId}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 从列表中移除
      columns.value = columns.value.filter(c => c.id !== columnId)

      // 刷新任务列表（因为任务状态可能已改为 'todo'）
      await fetchTasks(selectedDate.value)

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 更新欄位排序和啟用狀態
  const updateColumnSettings = async (columnId, updates) => {
    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/columns/${columnId}`, {
        method: 'PUT',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 更新本地欄位
      const col = columns.value.find(c => c.id === columnId)
      if (col) {
        if (updates.col_order !== undefined) col.col_order = updates.col_order
        if (updates.is_enabled !== undefined) col.is_enabled = updates.is_enabled
        if (updates.name !== undefined) col.name = updates.name
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // 方法 - 批量更新欄位排序
  const reorderColumns = async (columnUpdates) => {
    try {
      // 並行執行所有更新請求，並立即解析 JSON
      const promises = columnUpdates.map(update =>
        fetch(`${apiConfig.API_BASE_URL}/api/columns/${update.id}`, {
          method: 'PUT',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ col_order: update.col_order })
        }).then(response => response.json())
      )

      const results = await Promise.all(promises)

      // 檢查所有響應
      for (const data of results) {
        if (!data.success) {
          throw new Error(data.message || '排序更新失敗')
        }
      }

      // 重新排序本地列表
      columnUpdates.forEach(update => {
        const col = columns.value.find(c => c.id === update.id)
        if (col) {
          col.col_order = update.col_order
        }
      })

      // 按 col_order 排序本地列表
      columns.value.sort((a, b) => a.col_order - b.col_order)

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // ========================================
  // 工作圖片方法
  // ========================================

  // 方法 - 獲取工作的所有圖片
  const fetchTaskImages = async (taskId) => {
    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=images`,
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

      return data.data.images || []
    } catch (err) {
      error.value = err.message
      return []
    }
  }

  // 方法 - 上傳工作圖片
  const uploadTaskImage = async (taskId, file, source = 'attachment') => {
    try {
      const formData = new FormData()
      formData.append('image', file)

      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=addImage&source=${source}`,
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

      return data.data
    } catch (err) {
      error.value = err.message
      return null
    }
  }

  // 方法 - 刪除工作圖片
  const deleteTaskImage = async (taskId, imageId) => {
    try {
      const response = await fetch(
        `${apiConfig.API_BASE_URL}/api/tasks/${taskId}?action=deleteImage&imageId=${imageId}`,
        {
          method: 'DELETE',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' }
        }
      )

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 更新快取
      if (taskImagesCache.value[taskId]) {
        taskImagesCache.value[taskId] = taskImagesCache.value[taskId].filter(img => img.id !== imageId)
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  // ========================================
  // 圖片緩存和預加載
  // ========================================

  // 預加載所有任務的附件圖片
  const preloadTaskImages = async () => {
    try {
      // 為每個任務獲取其附件圖片
      const promises = tasks.value.map(task =>
        fetchTaskImages(task.id).then(images => {
          taskImagesCache.value[task.id] = images
        })
      )

      await Promise.all(promises)
    } catch (err) {
      console.error('Error preloading images:', err)
    }
  }

  // 獲取緩存中的任務圖片
  const getTaskImages = (taskId) => {
    return taskImagesCache.value[taskId] || []
  }

  return {
    tasks,
    columns,
    selectedDate,
    loading,
    error,
    tasksByStatus,
    taskImagesCache,
    isAdmin,
    fetchTasks,
    fetchTasksForRange,
    createTask,
    updateTaskStatus,
    updateTaskInfo,
    deleteTask,
    changeDate,
    fetchColumns,
    addColumn,
    updateColumn,
    updateColumnSettings,
    reorderColumns,
    deleteColumn,
    fetchTaskImages,
    uploadTaskImage,
    deleteTaskImage,
    preloadTaskImages,
    getTaskImages
  }
})
