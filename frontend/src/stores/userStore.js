import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiConfig from '../config/apiConfig'

export const useUserStore = defineStore('user', () => {
  // 状态
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const error = ref(null)

  // 计算属性
  const isLoggedIn = computed(() => !!token.value && !!user.value)

  // 方法 - 注册
  const register = async (username, email, password) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/auth?action=register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ username, email, password })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 注册成功后自动登录
      token.value = data.data.token
      user.value = {
        id: data.data.user_id,
        username: data.data.username,
        email: data.data.email,
        role: data.data.role || 'user'
      }

      // 保存 Token 和用户信息
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))

      return true
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // 方法 - 登录
  const login = async (username, password) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/auth?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ username, password })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      // 保存 Token 和用户信息
      token.value = data.data.token
      user.value = {
        id: data.data.user_id,
        username: data.data.username,
        email: data.data.email,
        role: data.data.role || 'user'
      }

      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))

      return true
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // 方法 - 登出
  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  // 方法 - 恢復會話（從 localStorage）
  const restoreSession = async () => {
    const savedUser = localStorage.getItem('user')
    const savedToken = localStorage.getItem('token')

    if (!savedUser || !savedToken) {
      return false
    }

    try {
      user.value = JSON.parse(savedUser)
      token.value = savedToken

      // 驗證 Token 是否仍然有效
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/auth?action=verify`, {
        method: 'GET',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
      })

      const data = await response.json()

      if (!data.success) {
        // Token 無效，清除會話
        logout()
        return false
      }

      // Token 有效，更新用戶角色信息
      if (user.value) {
        user.value.role = data.data.role || 'user'
        localStorage.setItem('user', JSON.stringify(user.value))
      }

      return true
    } catch (e) {
      console.error('Failed to restore session:', e)
      logout()
      return false
    }
  }

  // 方法 - 更改密碼
  const changePassword = async (oldPassword, newPassword, confirmPassword) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${apiConfig.API_BASE_URL}/api/auth?action=changePassword`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ oldPassword, newPassword, confirmPassword })
      })

      const data = await response.json()

      if (!data.success) {
        throw new Error(data.message)
      }

      return true
    } catch (err) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isLoggedIn,
    register,
    login,
    logout,
    restoreSession,
    changePassword
  }
})
