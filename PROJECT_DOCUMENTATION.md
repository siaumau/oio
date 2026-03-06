# OIO Kanban 工作記錄工具 - 完整項目文檔

**生成日期**: 2026年3月6日
**項目版本**: 1.0.0
**最後更新**: 實施週視圖匯出功能

---

## 📋 目錄
1. [項目概述](#項目概述)
2. [技術棧](#技術棧)
3. [項目架構](#項目架構)
4. [快速啟動](#快速啟動)
5. [數據庫配置](#數據庫配置)
6. [後端API詳細文檔](#後端api詳細文檔)
7. [前端目錄結構](#前端目錄結構)
8. [已實施功能](#已實施功能)
9. [數據模型](#數據模型)
10. [環境配置](#環境配置)

---

## 項目概述

### 什麼是 OIO？
OIO 是一個功能完整的 **看板式工作記錄工具**，使用拖拽式操作管理任務，支持多種視圖（日/周/月），以及任務分類、時長記錄、圖片上傳等功能。

### 核心特性
- ✅ 用戶認證與授權（普通用戶 + 管理員）
- ✅ 多視圖展示（日視圖、周視圖、月視圖）
- ✅ 任務管理（創建、編輯、刪除、拖拽排序）
- ✅ 自定義欄位（動態欄位管理）
- ✅ 任務時長記錄（以小時為單位）
- ✅ 圖片上傳（任務關聯圖片）
- ✅ 任務異動記錄（操作歷史追蹤）
- ✅ 週視圖匯出（TXT 格式報告）
- ✅ 密碼修改（安全性）

### 用戶角色
| 角色 | 功能 |
|------|------|
| **普通用戶** | 創建、編輯、刪除任務；管理個人欄位；查看歷史記錄 |
| **管理員** | 只讀模式；查看所有用戶的任務（不可修改） |

### 管理員帳號
```
用戶名: admin
密碼: admin123
```

---

## 技術棧

### 後端 (Backend)
- **運行環境**: PHP 7.4+
- **數據庫**: MySQL 5.7+
- **服務器**: PHP 內置服務器（開發）或 Apache/Nginx（生產）
- **API 模式**: RESTful
- **身份驗證**: PHP Session + Token（可擴展）
- **CORS**: 已配置跨域支持

### 前端 (Frontend)
- **框架**: Vue 3（Composition API）
- **構建工具**: Vite 5
- **狀態管理**: Pinia 2
- **HTTP 客戶端**: Axios
- **富文本編輯**: TipTap（支持圖片上傳）
- **拖拽庫**: vue-draggable-plus
- **樣式**: CSS 3（自定義）

### 開發工具
- **Node.js**: 14+ 或 16+
- **npm**: 6+ 或 yarn
- **PHP**: 7.4 或更高版本

---

## 項目架構

### 目錄結構
```
C:\D\sideproject\oio\
├── backend/                     # PHP 後端 (localhost:6001)
│   ├── index.php               # 入口文件
│   ├── router.php              # 路由配置
│   ├── .htaccess               # Apache 重寫規則
│   ├── config/
│   │   ├── database.php        # 數據庫連接
│   │   ├── cors.php            # CORS 配置
│   │   └── response.php        # 響應格式封裝
│   ├── routes/
│   │   ├── auth.php            # 認證路由（登錄、註冊、密碼修改）
│   │   ├── tasks.php           # 任務路由（CRUD）
│   │   └── columns.php         # 欄位管理路由
│   ├── sql/
│   │   ├── init_database.sql   # 初始化數據庫
│   │   └── migrate_columns.sql # 欄位遷移腳本
│   ├── uploads/                # 任務圖片上傳目錄
│   └── README.md               # 後端 API 文檔
│
├── frontend/                    # Vue 3 + Vite 前端 (localhost:5173)
│   ├── src/
│   │   ├── main.js             # 應用入口
│   │   ├── App.vue             # 根組件
│   │   ├── styles/
│   │   │   └── main.css        # 全局樣式
│   │   ├── views/
│   │   │   ├── LoginView.vue   # 登錄/註冊頁面
│   │   │   ├── DashboardView.vue # 看板視圖（核心）
│   │   │   ├── HistoryView.vue # 異動記錄頁面
│   │   │   └── ColumnsManagementView.vue # 欄位管理頁面
│   │   ├── stores/
│   │   │   ├── userStore.js    # 用戶狀態（登入、權限）
│   │   │   └── taskStore.js    # 任務狀態（CRUD、視圖）
│   │   ├── components/
│   │   │   └── RichTextEditor.vue # 富文本編輯器
│   │   ├── router/
│   │   │   └── index.js        # 路由配置
│   │   └── api/
│   │       └── (API 調用統一處理)
│   ├── index.html              # HTML 入口
│   ├── vite.config.js          # Vite 構建配置
│   ├── package.json            # 依賴配置
│   └── README.md               # 前端開發文檔
│
├── init_database.sql           # 數據庫初始化腳本
├── migrate_columns.sql         # 欄位數據遷移
├── package-lock.json
├── ADMIN_FEATURE_GUIDE.md      # 管理員功能指南
├── TASK_EDITING_GUIDE.md       # 任務編輯指南
├── PROJECT_DOCUMENTATION.md    # 此文檔
└── README.md
```

### 數據流
```
[用戶操作]
    ↓
[Vue 組件 (DashboardView)]
    ↓
[Pinia 狀態管理 (taskStore, userStore)]
    ↓
[Axios HTTP 請求]
    ↓
[PHP 後端 (routes/)]
    ↓
[MySQL 數據庫]
```

---

## 快速啟動

### 前置條件
1. **Node.js** (v14+) 和 **npm** 已安裝
2. **PHP** (7.4+) 已安裝
3. **MySQL** (5.7+) 正在運行
4. **Git** 已安裝（克隆項目用）

### 步驟 1: 初始化數據庫

```bash
# 進入 MySQL CLI
mysql -u root -p

# 在 MySQL 中執行
source C:\D\sideproject\oio\init_database.sql;
```

或直接使用 MySQL Workbench 導入 SQL 檔案。

**驗證**:
```bash
mysql -u root -p oio -e "SHOW TABLES;"
```

### 步驟 2: 啟動後端（終端 1）

```bash
cd C:\D\sideproject\oio\backend
php -S localhost:6001
```

**驗證**:
```bash
curl http://localhost:6001/api/health
# 應返回: {"success":true,"data":{"status":"ok"}}
```

### 步驟 3: 安裝前端依賴（終端 2）

```bash
cd C:\D\sideproject\oio\frontend
npm install
```

### 步驟 4: 啟動前端開發服務器

```bash
npm run dev
```

應自動打開 http://localhost:5173

### 步驟 5: 登入測試

**管理員帳號**:
- 用戶名: `admin`
- 密碼: `admin123`

**普通用戶**: 點擊「註冊」創建新帳號

---

## 數據庫配置

### 連接信息
```php
主機: localhost
端口: 3306
數據庫名: oio
用戶名: root
密碼: password
字符集: utf8mb4
時區: Asia/Taipei (UTC+8)
```

### 配置文件位置
- **文件**: `backend/config/database.php`
- **類**: `Database`
- **方法**: `Database::getInstance()` (單例模式)

### 主要數據表

#### 1. `users` 表
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') DEFAULT 'user',  -- 角色
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. `tasks` 表
```sql
CREATE TABLE tasks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT,
  status VARCHAR(50) DEFAULT '待做',      -- 動態欄位
  created_date DATE,
  duration DECIMAL(8,2) DEFAULT 0,        -- 工時
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (created_date)
);
```

#### 3. `user_columns` 表（自定義欄位）
```sql
CREATE TABLE user_columns (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,        -- 欄位名稱
  col_order INT DEFAULT 0,            -- 排序
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY (user_id, name)
);
```

#### 4. `task_history` 表（異動記錄）
```sql
CREATE TABLE task_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  action VARCHAR(50),                -- create/update/delete
  old_value LONGTEXT,
  new_value LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (task_id)
);
```

### 默認欄位
每個新用戶自動創建 4 個默認欄位：
1. 待做 (待做)
2. 進行中 (進行中)
3. 已完成 (已完成)
4. 暫停 (暫停)

---

## 後端API詳細文檔

### 基礎 URL
```
http://localhost:6001/api
```

### 認證端點 (`/api/auth`)

#### 1. 用戶登錄
```http
POST /api/auth?action=login
Content-Type: application/json

{
  "username": "testuser",
  "password": "password123"
}
```

**成功回應** (200):
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "role": "user",
    "token": "abc123..."
  }
}
```

#### 2. 用戶註冊
```http
POST /api/auth?action=register
Content-Type: application/json

{
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "password123"
}
```

**成功回應** (201):
```json
{
  "success": true,
  "message": "註冊成功",
  "data": {
    "user_id": 2,
    "username": "newuser"
  }
}
```

#### 3. 修改密碼
```http
POST /api/auth?action=changePassword
Content-Type: application/json

{
  "oldPassword": "password123",
  "newPassword": "newpassword456"
}
```

**須登錄**: 是 (Cookie 自動帶送)

### 任務端點 (`/api/tasks`)

#### 1. 取得任務列表（按日期）
```http
GET /api/tasks?date=2026-03-06
```

**參數**:
- `date`: 日期 (YYYY-MM-DD 格式)

**回應**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "完成文檔",
      "description": "編寫 API 文檔",
      "status": "進行中",
      "duration": 2.5,
      "image_url": "http://localhost:6001/uploads/task_1.jpg",
      "created_date": "2026-03-06",
      "username": "testuser"  -- 僅管理員可見
    }
  ]
}
```

#### 2. 創建任務
```http
POST /api/tasks
Content-Type: application/json

{
  "title": "新任務",
  "description": "任務描述",
  "created_date": "2026-03-06"
}
```

**回應** (201):
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "新任務",
    "description": "任務描述",
    "status": "待做",  -- 自動使用該用戶的第一個欄位
    "duration": 0,
    "image_url": null,
    "created_date": "2026-03-06"
  }
}
```

#### 3. 更新任務
```http
PUT /api/tasks/1
Content-Type: application/json

{
  "title": "更新的標題",
  "description": "更新的描述",
  "status": "進行中",
  "duration": 3
}
```

#### 4. 刪除任務
```http
DELETE /api/tasks/1
```

#### 5. 上傳任務圖片
```http
POST /api/tasks/1/upload
Content-Type: multipart/form-data

[二進制圖片數據]
```

**支持格式**: jpg, jpeg, png, gif, webp

### 欄位管理端點 (`/api/columns`)

#### 1. 取得用戶欄位
```http
GET /api/columns
```

**回應**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "待做",
      "col_order": 0
    },
    {
      "id": 2,
      "name": "進行中",
      "col_order": 1
    }
  ]
}
```

#### 2. 創建新欄位
```http
POST /api/columns
Content-Type: application/json

{
  "name": "新欄位"
}
```

#### 3. 更新欄位名稱
```http
PUT /api/columns/1
Content-Type: application/json

{
  "name": "更新的欄位名"
}
```

#### 4. 刪除欄位
```http
DELETE /api/columns/1
```

**注意**: 刪除欄位會自動將該欄位的任務移到 '待做'

#### 5. 重新排序欄位
```http
POST /api/columns/reorder
Content-Type: application/json

[
  { "id": 2, "col_order": 0 },
  { "id": 1, "col_order": 1 },
  { "id": 3, "col_order": 2 }
]
```

### 健康檢查
```http
GET /api/health
```

**回應**:
```json
{
  "success": true,
  "data": {
    "status": "ok"
  }
}
```

---

## 前端目錄結構

### 核心視圖

#### `LoginView.vue` - 登錄/註冊頁面
- 職責: 用戶身份驗證
- 功能:
  - 用戶登錄（Cookie 存儲）
  - 新用戶註冊
  - 錯誤提示
- 路由: `#/`（根路徑）

#### `DashboardView.vue` - 主看板（核心組件）
- **大小**: ~1400 行（最複雜的組件）
- **職責**: 任務展示、管理、多視圖切換
- **主要功能**:
  1. **日視圖**: 看板式展示（拖拽排序）
  2. **周視圖**: 7 天網格，顯示任務計數，點擊日期切換到日視圖
  3. **月視圖**: 日曆網格，顯示任務計數，點擊日期切換
  4. **任務卡片**:
     - 點擊展開/編輯
     - 顯示標題、描述、時長、圖片
     - 管理員只讀模式
  5. **模態框**:
     - 創建任務（標題、描述、日期、圖片）
     - 編輯任務（所有字段）
     - 修改密碼
  6. **高級功能**:
     - 全展開控制（「常態展開」按鈕）
     - 週視圖匯出（「📥 匯出本周」按鈕）
     - 自動展開配置（localStorage）

#### `HistoryView.vue` - 異動記錄
- 職責: 顯示任務操作歷史
- 功能:
  - 時間線視圖（按日期反向排序）
  - 操作類型篩選（create/update/delete）
  - 任務名稱篩選
  - 舊值/新值比較

#### `ColumnsManagementView.vue` - 欄位管理
- 職責: 自定義欄位管理
- 功能:
  - 拖拽重新排序（vue-draggable-plus）
  - 編輯欄位名稱
  - 添加新欄位
  - 刪除欄位（帶確認）

### 狀態管理 (Pinia Store)

#### `stores/userStore.js`
```javascript
// 狀態
user: {
  id: null,
  username: null,
  email: null,
  role: 'user'  // 'user' | 'admin'
}
isAuthenticated: boolean
loading: boolean
error: null

// 方法
login(username, password)
register(username, email, password)
logout()
fetchUser()
changePassword(oldPassword, newPassword)
```

#### `stores/taskStore.js`
```javascript
// 狀態
tasks: Array
columns: Array
isAdmin: boolean
loading: boolean
tasksCache: {}

// 計算屬性
tasksByStatus: 按狀態分組的任務
tasksByDate: 按日期分組的任務

// 方法
fetchTasks(date)
fetchTasksForRange(startDate, endDate)
createTask(task)
updateTask(id, updates)
deleteTask(id)
fetchColumns()
addColumn(name)
updateColumn(id, name)
deleteColumn(id)
uploadTaskImage(taskId, file)
```

### 組件通信
```
LoginView ←→ userStore
DashboardView ←→ taskStore ←→ userStore
HistoryView ←→ taskStore
ColumnsManagementView ←→ taskStore
```

---

## 已實施功能

### ✅ 核心功能（完成）

#### 用戶管理
- [x] 用戶註冊（郵箱驗證可選）
- [x] 用戶登錄（Session 管理）
- [x] 修改密碼
- [x] 登出
- [x] 管理員角色（只讀模式）

#### 任務管理
- [x] 創建任務
- [x] 編輯任務（標題、描述、狀態、時長）
- [x] 刪除任務
- [x] 拖拽排序（狀態轉移）
- [x] 時長記錄（以小時為單位）

#### 多視圖支持
- [x] 日視圖（看板式）
- [x] 周視圖（7 天網格）
- [x] 月視圖（日曆視圖）
- [x] 視圖之間的無縫切換

#### 欄位管理
- [x] 默認 4 個欄位（待做、進行中、已完成、暫停）
- [x] 自定義添加欄位
- [x] 編輯欄位名稱
- [x] 刪除欄位（任務自動轉移）
- [x] 拖拽重新排序欄位
- [x] 數據遷移（欄位名稱變更自動更新任務）

#### 任務詳情管理
- [x] 圖片上傳（支持 jpg, png, gif, webp）
- [x] 圖片展示（任務卡片內內聯顯示）
- [x] 圖片刪除
- [x] 富文本描述（TipTap 編輯器）
- [x] 行內編輯器圖片禁用（防止 base64 損壞）

#### 異動追蹤
- [x] 記錄所有任務操作（create/update/delete）
- [x] 顯示操作者、時間、舊值、新值
- [x] 時間線視圖
- [x] 操作篩選

#### 管理員功能
- [x] 只讀視圖（查看所有用戶任務）
- [x] 任務名稱前顯示用戶名 (👤 用戶名 -)
- [x] 禁用所有編輯操作
- [x] 禁用拖拽排序

#### 新增功能（March 6, 2026）
- [x] **週視圖匯出** (📥 匯出本周)
  - 導出當周任務為 TXT 檔案
  - 按狀態統計
  - 按日期組織詳細列表
  - UTF-8 BOM 編碼（Windows 相容）
  - 檔名格式: `OIO_週報_3-1_3-7.txt`

#### UI/UX 增強
- [x] 全展開控制按鈕（localStorage 記憶狀態）
- [x] 響應式設計（桌面优化）
- [x] 圖片縮圖顯示（對象填充模式）
- [x] 任務卡片動畫（展開/收縮）
- [x] 模態框確認機制
- [x] 錯誤提示（使用者友好）

---

## 數據模型

### 用戶模型
```javascript
{
  id: 1,
  username: "testuser",
  email: "test@example.com",
  password: "hashed_password",
  role: "user",              // 'user' | 'admin'
  created_at: "2026-02-26T10:00:00",
  updated_at: "2026-03-06T10:00:00"
}
```

### 任務模型
```javascript
{
  id: 1,
  user_id: 1,
  title: "完成項目文檔",
  description: "<p>編寫詳細的 API 文檔</p>",
  status: "進行中",           // 動態，來自 user_columns
  created_date: "2026-03-06",
  duration: 2.5,              // 小時
  image_url: "http://localhost:6001/uploads/task_1_1234567890.jpg",
  created_at: "2026-02-26T10:00:00",
  updated_at: "2026-03-06T15:30:00",
  username: "testuser"        // 僅在管理員查詢時返回
}
```

### 欄位模型
```javascript
{
  id: 1,
  user_id: 1,
  name: "待做",
  col_order: 0,
  created_at: "2026-02-26T10:00:00"
}
```

### 異動記錄模型
```javascript
{
  id: 1,
  task_id: 1,
  user_id: 1,
  action: "update",           // 'create' | 'update' | 'delete'
  old_value: '{"status": "待做"}',
  new_value: '{"status": "進行中"}',
  created_at: "2026-03-06T10:00:00"
}
```

---

## 環境配置

### 後端環境變數（內置於代碼）
```php
// backend/config/database.php
HOST: localhost
PORT: 3306
DATABASE: oio
USER: root
PASSWORD: password
CHARSET: utf8mb4
TIMEZONE: Asia/Taipei
```

### 前端環境配置

#### Vite 配置 (`vite.config.js`)
```javascript
server: {
  port: 5173,
  host: 'localhost',
  cors: true
}
```

#### API 基礎 URL
```javascript
// 在 axios 攔截器中設置
VITE_API_BASE_URL=http://localhost:6001
```

### CORS 配置（後端）
```php
// backend/config/cors.php
允許來源: http://localhost:5173
允許方法: GET, POST, PUT, DELETE, OPTIONS
允許頭部: Content-Type, Authorization
允許認證: true (Cookie)
```

### 本地存儲 (localStorage)
```javascript
// 鍵名與用途
autoExpandAll       // bool: 自動展開任務卡片
currentViewMode     // string: 當前視圖模式
selectedDate        // string: 選定的日期
user_auth           // object: 用戶認證信息
```

---

## 常見問題 & 故障排除

### 後端問題

#### Q: PHP 服務器無法啟動
**A**: 檢查端口 6001 是否被占用
```bash
# Windows 查看端口占用
netstat -ano | findstr :6001

# 殺死占用進程
taskkill /PID <PID> /F
```

#### Q: 數據庫連接失敗
**A**: 確認 MySQL 正在運行
```bash
# Windows 啟動 MySQL
net start MySQL80

# 驗證連接
mysql -u root -p -e "SELECT 1;"
```

#### Q: 文件上傳失敗
**A**: 檢查 `backend/uploads/` 目錄權限
```bash
# Linux/Mac
chmod 755 backend/uploads

# Windows 確保有寫入權限
```

### 前端問題

#### Q: 前端無法連接後端
**A**: 檢查後端是否運行在 localhost:6001
```bash
curl http://localhost:6001/api/health
```

#### Q: 無法登錄
**A**:
1. 確認後端正在運行
2. 確認數據庫已初始化
3. 檢查瀏覽器 Console 是否有錯誤

#### Q: 圖片無法上傳
**A**:
1. 檢查 `backend/uploads/` 目錄是否存在
2. 確認 PHP 的文件上傳限制 (`php.ini`)
3. 檢查圖片格式是否支持（jpg, png, gif, webp）

### 性能優化

#### 數據庫優化
```sql
-- 創建索引加速查詢
CREATE INDEX idx_user_created_date ON tasks(user_id, created_date);
CREATE INDEX idx_task_status ON tasks(status);
```

#### 前端優化
- 使用 `tasksCache` 緩存避免重複請求
- 周視圖使用 `fetchTasksForRange` 批量請求
- 啟用瀏覽器 localStorage 緩存用戶數據

---

## 開發注意事項

### 代碼規範

#### 後端 (PHP)
```php
// 1. 始終使用 mysqli prepare 防止 SQL 注入
$stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// 2. 返回標準響應格式
Response::success($data, '成功消息');
Response::error('錯誤消息', 400);

// 3. 使用事務管理複雜操作
$db->begin_transaction();
// ... 多個操作
$db->commit();
```

#### 前端 (Vue 3)
```javascript
// 1. 使用 Composition API
import { ref, computed } from 'vue'
const count = ref(0)
const doubled = computed(() => count.value * 2)

// 2. 使用 Pinia 管理狀態
import { useTaskStore } from '@/stores/taskStore'
const taskStore = useTaskStore()

// 3. 非同步操作使用 async/await
const fetchTasks = async (date) => {
  try {
    const data = await taskStore.fetchTasks(date)
    return data
  } catch (error) {
    console.error(error)
  }
}
```

### 測試清單

#### 部署前檢查
- [ ] 所有 API 端點都能正常工作
- [ ] 圖片上傳功能正常
- [ ] 在不同瀏覽器中測試
- [ ] 管理員帳號權限正確
- [ ] 欄位管理無異常
- [ ] 異動記錄詳確
- [ ] 週視圖匯出格式正確

#### 性能測試
- [ ] 大量任務（>1000）時的加載速度
- [ ] 圖片上傳的大小限制
- [ ] 長期使用時的內存占用

---

## 部署指南

### 生產環境配置

#### 後端部署（使用 Apache）
1. 將 `backend/` 目錄上傳到服務器
2. 配置 Apache VirtualHost
3. 啟用 mod_rewrite 模塊
4. 修改 `.htaccess` 中的路徑

#### 前端部署
```bash
# 構建生產版本
npm run build

# 生成文件在 dist/ 目錄
# 將 dist/ 上傳到靜態文件服務器
```

#### 安全建議
1. 修改數據庫密碼
2. 啟用 HTTPS
3. 設置適當的 CORS 政策
4. 定期備份數據庫
5. 監控日誌文件

---

## 聯絡與支持

### 項目信息
- **名稱**: OIO Kanban 工作記錄工具
- **版本**: 1.0.0
- **最後更新**: 2026-03-06
- **開發者**: Claude AI
- **許可證**: MIT

### 未來功能規劃
- [ ] 任務標籤與搜索
- [ ] 任務重複功能
- [ ] 團隊協作模式
- [ ] 通知與提醒
- [ ] API Token 認證
- [ ] 暗黑模式
- [ ] 移動應用版本
- [ ] 數據導出（CSV, Excel）

---

**文檔生成日期**: 2026年3月6日
**下一次更新預計**: 2026年3月20日（或有重大功能發佈時）
