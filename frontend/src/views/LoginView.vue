<template>
  <div class="login-container">
    <div class="login-box card">
      <h1 class="logo">📝 OIO</h1>
      <p class="subtitle">工作紀錄工具</p>

      <!-- 标签页切换 -->
      <div class="tabs">
        <button
          :class="['tab', { active: currentTab === 'login' }]"
          @click="currentTab = 'login'"
        >
          登录
        </button>
        <button
          :class="['tab', { active: currentTab === 'register' }]"
          @click="currentTab = 'register'"
        >
          注册
        </button>
      </div>

      <!-- 登录表单 -->
      <form v-if="currentTab === 'login'" @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="login-username">用户名</label>
          <input
            id="login-username"
            v-model="loginForm.username"
            type="text"
            placeholder="输入用户名"
            required
          />
        </div>

        <div class="form-group">
          <label for="login-password">密码</label>
          <input
            id="login-password"
            v-model="loginForm.password"
            type="password"
            placeholder="输入密码"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="userStore.loading">
          {{ userStore.loading ? '登录中...' : '登录' }}
        </button>
      </form>

      <!-- 注册表单 -->
      <form v-if="currentTab === 'register'" @submit.prevent="handleRegister">
        <div class="form-group">
          <label for="register-username">用户名</label>
          <input
            id="register-username"
            v-model="registerForm.username"
            type="text"
            placeholder="输入用户名（至少3个字符）"
            required
          />
        </div>

        <div class="form-group">
          <label for="register-email">邮箱</label>
          <input
            id="register-email"
            v-model="registerForm.email"
            type="email"
            placeholder="输入邮箱"
            required
          />
        </div>

        <div class="form-group">
          <label for="register-password">密码</label>
          <input
            id="register-password"
            v-model="registerForm.password"
            type="password"
            placeholder="输入密码（至少6个字符）"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="userStore.loading">
          {{ userStore.loading ? '注册中...' : '注册' }}
        </button>
      </form>

      <!-- 错误消息 -->
      <div v-if="userStore.error" class="error-message">
        ⚠️ {{ userStore.error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useUserStore } from '../stores/userStore.js'

const userStore = useUserStore()

const currentTab = ref('login')

const loginForm = ref({
  username: '',
  password: ''
})

const registerForm = ref({
  username: '',
  email: '',
  password: ''
})

const handleLogin = async () => {
  const success = await userStore.login(
    loginForm.value.username,
    loginForm.value.password
  )

  if (success) {
    // 跳转到仪表板（通过 App.vue 的 isLoggedIn 计算属性自动切换）
    loginForm.value = { username: '', password: '' }
  }
}

const handleRegister = async () => {
  const success = await userStore.register(
    registerForm.value.username,
    registerForm.value.email,
    registerForm.value.password
  )

  if (success) {
    // 注册成功后自动切换到仪表板
    registerForm.value = { username: '', email: '', password: '' }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-box {
  width: 100%;
  max-width: 400px;
  background-color: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.logo {
  font-size: 48px;
  text-align: center;
  margin-bottom: 8px;
}

.subtitle {
  text-align: center;
  color: var(--color-gray-600);
  margin-bottom: 30px;
}

.tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  border-bottom: 2px solid var(--color-gray-200);
}

.tab {
  flex: 1;
  padding: 12px 16px;
  border: none;
  background: none;
  border-bottom: 3px solid transparent;
  color: var(--color-gray-600);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.form-group {
  margin-bottom: 18px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--color-gray-700);
  font-size: 14px;
}

.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--color-gray-300);
  border-radius: 6px;
  font-size: 14px;
}

.btn-block {
  width: 100%;
  margin-top: 12px;
}

.error-message {
  background-color: #fee;
  color: var(--color-danger);
  padding: 12px;
  border-radius: 6px;
  margin-top: 16px;
  font-size: 14px;
}

.test-account {
  background-color: #f0f9ff;
  border-left: 3px solid var(--color-info);
  padding: 12px;
  border-radius: 4px;
  margin-top: 20px;
  font-size: 12px;
  color: var(--color-gray-700);
}

.test-account p {
  margin: 4px 0;
}
</style>
