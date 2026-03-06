# OIO 項目 - AI 快速參考卡

## 🎯 項目摘要
- **名稱**: OIO Kanban 工作記錄工具
- **類型**: 全棧 Web 應用（PHP + Vue 3）
- **用途**: 看板式任務管理系統
- **版本**: 1.0.0
- **最後更新**: 2026-03-06

---

## 📂 核心路徑速查

```
C:\D\sideproject\oio\
├── backend/                 → PHP API 服務 (localhost:6001)
│   ├── index.php           → 入口點
│   ├── config/database.php → 數據庫配置
│   ├── routes/
│   │   ├── auth.php        → 認證 (login, register, changePassword)
│   │   ├── tasks.php       → 任務 CRUD
│   │   └── columns.php     → 欄位管理
│   └── uploads/            → 任務圖片存儲
│
├── frontend/               → Vue 3 前端 (localhost:5173)
│   ├── src/
│   │   ├── views/DashboardView.vue  → 核心看板（1400+ 行）
│   │   ├── views/LoginView.vue      → 登錄
│   │   ├── views/HistoryView.vue    → 異動記錄
│   │   ├── views/ColumnsManagementView.vue → 欄位管理
│   │   ├── stores/
│   │   │   ├── userStore.js        → 用戶狀態管理
│   │   │   └── taskStore.js        → 任務狀態管理
│   │   └── components/RichTextEditor.vue → 富文本編輯器
│   ├── vite.config.js
│   └── package.json
│
├── init_database.sql       → 數據庫初始化腳本
├── migrate_columns.sql     → 欄位數據遷移
└── PROJECT_DOCUMENTATION.md → 完整文檔
```

---

## 🚀 快速啟動命令

### 終端 1：啟動後端
```bash
cd C:\D\sideproject\oio\backend
php -S localhost:6001
```

### 終端 2：啟動前端
```bash
cd C:\D\sideproject\oio\frontend
npm install  # 首次需要
npm run dev
```

### 驗證
```bash
# 後端健康檢查
curl http://localhost:6001/api/health

# 訪問前端
http://localhost:5173
```

---

## 🗄️ 數據庫配置

| 項目 | 值 |
|------|-----|
| 主機 | localhost |
| 端口 | 3306 |
| 數據庫 | oio |
| 用戶 | root |
| 密碼 | password |
| 字符集 | utf8mb4 |
| 時區 | Asia/Taipei (UTC+8) |

### 核心數據表
```
users → 用戶
tasks → 任務 (status VARCHAR 支持動態欄位)
user_columns → 自定義欄位
task_history → 異動記錄
```

---

## 👤 測試帳號

| 帳號 | 用戶名 | 密碼 | 角色 |
|------|--------|------|------|
| 管理員 | admin | admin123 | admin (只讀) |
| 普通用戶 | - | - | user (自行註冊) |

---

## 🔌 API 端點速查

### 認證
- `POST /api/auth?action=login` → 登錄
- `POST /api/auth?action=register` → 註冊
- `POST /api/auth?action=changePassword` → 修改密碼

### 任務
- `GET /api/tasks?date=2026-03-06` → 取得當日任務
- `POST /api/tasks` → 創建任務
- `PUT /api/tasks/:id` → 更新任務
- `DELETE /api/tasks/:id` → 刪除任務
- `POST /api/tasks/:id/upload` → 上傳圖片

### 欄位管理
- `GET /api/columns` → 取得用戶欄位
- `POST /api/columns` → 創建欄位
- `PUT /api/columns/:id` → 更新欄位名
- `DELETE /api/columns/:id` → 刪除欄位
- `POST /api/columns/reorder` → 重新排序

### 系統
- `GET /api/health` → 健康檢查

---

## 🎨 前端主要功能

### DashboardView.vue（核心組件）
| 功能 | 說明 |
|------|------|
| 日視圖 | 看板式展示，支持拖拽排序 |
| 周視圖 | 7 天網格，點擊切換到日視圖 |
| 月視圖 | 日曆視圖，點擊日期切換 |
| 任務卡片 | 點擊展開編輯，顯示標題/描述/時長/圖片 |
| 全展開控制 | localStorage 記憶「常態展開」狀態 |
| 週視圖匯出 | 📥 按鈕，下載 TXT 報告（March 6 新增） |

### 狀態管理 (Pinia)
- `userStore`: 用戶認證、權限、角色
- `taskStore`: 任務 CRUD、欄位管理、視圖狀態

---

## 📊 主要功能清單

### ✅ 已完成
- [x] 用戶認證（註冊/登入/密碼修改）
- [x] 任務 CRUD 操作
- [x] 多視圖（日/周/月）
- [x] 自定義欄位（拖拽排序、編輯、刪除）
- [x] 任務時長記錄
- [x] 圖片上傳（jpg, png, gif, webp）
- [x] 富文本編輯（TipTap）
- [x] 異動記錄追蹤
- [x] 管理員只讀模式
- [x] **週視圖匯出** (2026-03-06 新增)

### 🔮 未來功能
- [ ] 任務標籤與搜索
- [ ] 任務重複功能
- [ ] 團隊協作
- [ ] 通知與提醒
- [ ] JWT 認證
- [ ] 暗黑模式

---

## 🛠️ 技術棧

### 後端
```
PHP 7.4+
MySQL 5.7+
RESTful API
Session 認證
```

### 前端
```
Vue 3 (Composition API)
Vite 5
Pinia 2
Axios
TipTap (富文本)
vue-draggable-plus (拖拽)
CSS 3
```

---

## 📝 重要文件與功能對應

| 文件 | 用途 | 關鍵方法/功能 |
|------|------|-------------|
| `DashboardView.vue` | 主看板視圖 | exportWeekReport(), switchToDay(), onDrop() |
| `taskStore.js` | 任務狀態管理 | fetchTasks(), updateTask(), fetchColumns() |
| `userStore.js` | 用戶狀態管理 | login(), register(), logout() |
| `tasks.php` | 任務 API | getTasksList(), createTask(), updateTask() |
| `columns.php` | 欄位 API | getColumns(), createColumn(), deleteColumn() |
| `auth.php` | 認證 API | loginUser(), registerUser(), changePassword() |

---

## 🔄 數據流示意

```
用戶操作 (點擊、輸入)
    ↓
Vue 組件 (DashboardView, LoginView)
    ↓
Pinia 狀態管理 (userStore, taskStore)
    ↓
Axios HTTP 請求
    ↓
PHP 路由 (routes/auth.php, routes/tasks.php)
    ↓
MySQL 數據庫 (執行 SQL 操作)
    ↓
JSON 響應 → 前端展示
```

---

## 🐛 常見故障排除

### 後端問題
```bash
# 檢查端口占用
netstat -ano | findstr :6001

# 啟動 MySQL
net start MySQL80

# 驗證 PHP
php -v
```

### 前端問題
```bash
# 清除 node_modules
rm -r node_modules package-lock.json

# 重新安裝
npm install

# 檢查 Vite 配置
cat vite.config.js
```

### 數據庫問題
```bash
# 進入 MySQL
mysql -u root -p

# 驗證數據庫
USE oio;
SHOW TABLES;
```

---

## 💾 重要配置文件

| 文件 | 用途 |
|------|------|
| `backend/config/database.php` | 數據庫連接 |
| `backend/config/cors.php` | CORS 跨域配置 |
| `backend/config/response.php` | 統一響應格式 |
| `frontend/vite.config.js` | 前端構建配置 |
| `frontend/package.json` | 前端依賴 |

---

## 📚 文檔位置

| 文檔 | 位置 | 內容 |
|------|------|------|
| 完整文檔 | `PROJECT_DOCUMENTATION.md` | 詳細的項目文檔 |
| 後端文檔 | `backend/README.md` | API 端點詳解 |
| 前端文檔 | `frontend/README.md` | 前端開發指南 |
| 管理員指南 | `ADMIN_FEATURE_GUIDE.md` | 管理員功能說明 |
| 任務編輯指南 | `TASK_EDITING_GUIDE.md` | 任務編輯功能 |
| 此文檔 | `AI_REFERENCE_CARD.md` | AI 快速參考 |

---

## 🔐 安全注意事項

1. **SQL 注入防護**: 使用 prepared statement
2. **文件上傳**: 檢查文件類型和大小
3. **CORS 配置**: 限制允許的來源
4. **Session 管理**: Cookie 自動帶送
5. **密碼存儲**: 使用 password_hash()

---

## 🎯 最近變更 (March 2026)

### 2026-03-06：週視圖匯出功能
- 新增「📥 匯出本周」按鈕（位置：週視圖導航，「下一周」按鈕右側）
- 生成 TXT 報告，包含：
  - 當周日期範圍
  - 按狀態分類的任務統計
  - 詳細任務列表（按日期、狀態、時長）
- 檔案名格式：`OIO_週報_3-1_3-7.txt`
- UTF-8 BOM 編碼（Windows 相容）
- 實施文件：`frontend/src/views/DashboardView.vue` (line 90, 1264-1339)

### 2026-03-03：修復多個問題
1. ColumnsManagementView 欄位名稱編輯顯示問題
2. 拖拽排序後重新整理失效
3. 新增任務 status 動態查詢
4. 任務描述圖片填滿容器寬度
5. 全展開控制按鈕 + localStorage

### 2026-02-26：初始版本
- 基礎 CRUD 操作
- 多視圖支持
- 欄位管理系統
- 圖片上傳功能

---

## 📞 聯絡與支援

- **問題回報**: 檢查瀏覽器 Console 和後端日誌
- **性能優化**: 使用 tasksCache 避免重複請求
- **生產部署**: 修改數據庫密碼、啟用 HTTPS、配置 Apache/Nginx

---

**本文檔為 AI 快速參考**
**完整詳情請參閱 PROJECT_DOCUMENTATION.md**
**最後更新: 2026-03-06**
