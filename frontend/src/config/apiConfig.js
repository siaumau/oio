// API Configuration
// 统一管理后端 API 地址，方便在不同环境中修改

// 自動偵測當前訪問的域名/IP，然後使用相同的域名訪問後端
// 這樣開發環境和生產環境都能正常工作
let API_BASE_URL

if (typeof window !== 'undefined') {
  // 瀏覽器環境 - 使用當前訪問的域名
  const protocol = window.location.protocol  // http: 或 https:
  const hostname = window.location.hostname  // 域名或 IP
  const port = 6001

  API_BASE_URL = `${protocol}//${hostname}:${port}`
} else {
  // 服務器端環境（如需要）
  API_BASE_URL = 'http://localhost:6001'
}

export default {
  API_BASE_URL
}
