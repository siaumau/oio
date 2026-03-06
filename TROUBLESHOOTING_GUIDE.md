# OIO 項目 - 故障排除指南

## 🆘 快速診斷流程

```
問題發生
    ↓
1️⃣ 後端運行？ (localhost:6001)
    ├─ 否 → 後端啟動問題
    └─ 是 ↓
2️⃣ 前端運行？ (localhost:5173)
    ├─ 否 → 前端啟動問題
    └─ 是 ↓
3️⃣ MySQL 運行？
    ├─ 否 → 數據庫問題
    └─ 是 ↓
4️⃣ 瀏覽器 Console 有錯誤？
    ├─ 是 → 檢查 API 響應
    └─ 否 → 數據問題
```

---

## ❌ 後端問題

### 問題 1: PHP 服務器無法啟動

**症狀**:
```
Bind failed for 127.0.0.1:6001
```

**原因**: 端口 6001 被占用

**解決方案**:

#### Windows
```bash
# 查看占用端口的進程
netstat -ano | findstr :6001

# 輸出範例: TCP    127.0.0.1:6001    0.0.0.0:0    LISTENING    12345

# 殺死進程
taskkill /PID 12345 /F

# 驗證端口已釋放
netstat -ano | findstr :6001

# 重新啟動 PHP 服務器
cd C:\D\sideproject\oio\backend
php -S localhost:6001
```

#### Linux/Mac
```bash
# 查看占用端口
lsof -i :6001

# 殺死進程
kill -9 <PID>

# 重新啟動
php -S localhost:6001
```

**驗證**:
```bash
curl http://localhost:6001/api/health
# 應返回: {"success":true,"data":{"status":"ok"}}
```

---

### 問題 2: 數據庫連接失敗

**症狀**:
```
{"success":false,"message":"数据库连接失败: Unknown server host ..."}
```

**原因**: MySQL 未運行或連接信息錯誤

**解決方案**:

#### Windows
```bash
# 啟動 MySQL
net start MySQL80
# 或
net start MySQL57  # 如果版本不同

# 驗證連接
mysql -u root -p
# 輸入密碼: password

# 列出數據庫
SHOW DATABASES;

# 選擇 OIO 數據庫
USE oio;
SHOW TABLES;
```

#### Linux
```bash
# 啟動 MySQL
sudo systemctl start mysql

# 驗證
mysql -u root -p
```

#### 檢查數據庫配置
- 文件: `backend/config/database.php`
- 確認以下信息:
  ```php
  $host = 'localhost';
  $port = 3306;
  $db = 'oio';
  $user = 'root';
  $password = 'password';
  ```

---

### 問題 3: 登錄返回 401 未授權

**症狀**:
```
{"success":false,"message":"用户不存在或密码错误"}
```

**原因**: 用戶不存在或密碼錯誤

**解決方案**:

```bash
# 登錄 MySQL
mysql -u root -p oio

# 查看用戶表
SELECT * FROM users;

# 創建測試用戶（如果表為空）
INSERT INTO users (username, email, password, role) VALUES
('testuser', 'test@example.com', '$2y$10$...', 'user');

# 驗證管理員帳號是否存在
SELECT * FROM users WHERE username = 'admin';

# 如果不存在，創建管理員
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86.sxIPi2CO', 'admin');
# 密碼 hash 對應: admin123
```

**測試登錄**:
```bash
curl -X POST http://localhost:6001/api/auth?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

---

### 問題 4: 圖片無法上傳

**症狀**:
- 上傳按鈕無反應
- 前端 Console 有 404 或 500 錯誤

**原因**: `uploads/` 目錄不存在或無寫入權限

**解決方案**:

```bash
# Windows
# 1. 檢查目錄是否存在
dir C:\D\sideproject\oio\backend\uploads

# 2. 如果不存在，創建目錄
mkdir C:\D\sideproject\oio\backend\uploads

# 3. 檢查 PHP 文件上傳配置
php -i | findstr upload_max_filesize

# 4. 如果限制太小，修改 php.ini
# upload_max_filesize = 32M
# post_max_size = 32M
```

**權限檢查**:
```bash
# Linux/Mac
ls -la backend/uploads
# 應該是: drwxrwxr-x

# 如果沒有寫入權限
chmod 755 backend/uploads
chmod 777 backend/uploads  # 更寬鬆（開發環境可接受）
```

---

### 問題 5: API 返回 500 Internal Server Error

**症狀**:
```json
{"success":false,"message":"服务器错误: ..."}
```

**解決方案**:

```bash
# 1. 查看 PHP 錯誤日誌
# Windows: C:\xampp\php\logs\php_error.log
# Linux: /var/log/php-errors.log

# 2. 查看後端 console 輸出（如果在終端運行）
# 重新啟動服務器並查看錯誤消息
php -S localhost:6001

# 3. 在 backend/index.php 檢查錯誤報告設置
# 應該是開啟的:
error_reporting(E_ALL);
ini_set('display_errors', 1);

# 4. 測試簡單 API
curl http://localhost:6001/api/health
```

---

## ❌ 前端問題

### 問題 1: npm 依賴安裝失敗

**症狀**:
```
npm ERR! code ERESOLVE
npm ERR! ERESOLVE unable to resolve dependency tree
```

**原因**: 版本衝突或網絡問題

**解決方案**:

```bash
cd C:\D\sideproject\oio\frontend

# 方案 1: 清除並重新安裝
rm -r node_modules package-lock.json
npm install

# 方案 2: 強制解決衝突
npm install --legacy-peer-deps

# 方案 3: 使用 yarn（如果已安裝）
yarn install

# 驗證
npm list
```

---

### 問題 2: Vite 開發服務器無法啟動

**症狀**:
```
Error: listen EADDRINUSE: address already in use :::5173
```

**原因**: 端口 5173 被占用

**解決方案**:

```bash
# Windows
netstat -ano | findstr :5173
taskkill /PID <PID> /F

# Linux/Mac
lsof -i :5173
kill -9 <PID>

# 或修改 Vite 配置（vite.config.js）
export default {
  server: {
    port: 5174  # 改為其他端口
  }
}

# 重新啟動
npm run dev
```

---

### 問題 3: 無法連接到後端 API

**症狀**:
- 網頁打開但無法登錄
- Console 顯示: `GET http://localhost:6001/api/... net::ERR_CONNECTION_REFUSED`

**原因**: 後端服務器未運行

**解決方案**:

```bash
# 1. 驗證後端是否運行
curl http://localhost:6001/api/health

# 2. 如果返回錯誤，啟動後端
cd C:\D\sideproject\oio\backend
php -S localhost:6001

# 3. 重新刷新前端頁面
# 按 Ctrl+Shift+Delete 清除緩存
# 或在 DevTools 中禁用緩存

# 4. 檢查 CORS 配置
# 文件: backend/config/cors.php
# 應該包含:
// Allow http://localhost:5173
```

**檢查 CORS 配置**:
```bash
curl -i -X OPTIONS http://localhost:6001/api/tasks \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET"

# 應返回 200 OK 和 CORS 頭部
```

---

### 問題 4: 登錄成功但看不到任務

**症狀**:
- 登錄後頁面空白或 KanBan 為空
- Console 無錯誤

**原因**: 數據庫無數據或查詢失敗

**解決方案**:

```bash
# 1. 驗證數據庫有任務數據
mysql -u root -p oio
SELECT * FROM tasks LIMIT 5;

# 2. 如果為空，添加測試數據
INSERT INTO tasks (user_id, title, status, created_date) VALUES
(1, '測試任務 1', '待做', '2026-03-06'),
(1, '測試任務 2', '進行中', '2026-03-06');

# 3. 驗證 API 端點
curl "http://localhost:6001/api/tasks?date=2026-03-06" \
  -b "PHPSESSID=<your-session-id>"

# 4. 檢查前端 taskStore 是否正確加載數據
# 打開 DevTools → Console 輸入:
import { useTaskStore } from '@/stores/taskStore'
const store = useTaskStore()
console.log(store.tasks)
```

---

### 問題 5: 圖片無法預覽

**症狀**:
- 上傳圖片後，圖片不顯示
- Console: `GET http://localhost:6001/uploads/... 404`

**原因**:
1. 圖片未真正上傳
2. 上傳目錄無讀取權限
3. image_url 存儲錯誤

**解決方案**:

```bash
# 1. 檢查上傳目錄是否有文件
ls -la backend/uploads/

# 2. 檢查數據庫中的 image_url 是否正確
mysql -u root -p oio
SELECT id, title, image_url FROM tasks;

# 3. 測試圖片直接訪問
curl http://localhost:6001/uploads/task_1_1234567890.jpg

# 4. 檢查文件權限（Linux/Mac）
chmod 644 backend/uploads/*

# 5. 查看後端上傳處理代碼
# 文件: backend/routes/tasks.php
# 搜索: file_upload 或 move_uploaded_file
```

---

### 問題 6: 頁面加載很慢

**症狀**:
- 首次加載或視圖切換時延遲明顯

**原因**:
1. 數據庫查詢慢
2. 圖片加載多
3. 缺少索引

**解決方案**:

```bash
# 1. 添加數據庫索引
mysql -u root -p oio
CREATE INDEX idx_user_date ON tasks(user_id, created_date);
CREATE INDEX idx_status ON tasks(status);

# 2. 檢查前端緩存機制
# 查看 taskStore.js 中的 tasksCache 使用

# 3. 優化圖片大小
# 確保上傳圖片 < 500KB

# 4. 使用瀏覽器 DevTools 分析性能
# F12 → Performance 標籤 → 錄製並分析
```

---

## 🗄️ 數據庫問題

### 問題 1: 數據庫未初始化

**症狀**:
```
Table 'oio.users' doesn't exist
```

**原因**: 未執行初始化腳本

**解決方案**:

```bash
# 登錄 MySQL
mysql -u root -p

# 檢查數據庫是否存在
SHOW DATABASES;

# 如果 oio 不存在，創建
CREATE DATABASE oio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 導入初始化腳本
mysql -u root -p oio < C:\D\sideproject\oio\init_database.sql

# 驗證表
USE oio;
SHOW TABLES;

# 應該看到:
# ┌─────────────┐
# │ Tables      │
# ├─────────────┤
# │ users       │
# │ tasks       │
# │ user_columns│
# │ task_history│
# └─────────────┘
```

---

### 問題 2: 欄位名稱亂碼

**症狀**:
- 欄位名稱顯示為 ??? 或 ㄈㄉㄎ

**原因**: 字符集設置不同

**解決方案**:

```bash
# 1. 驗證數據庫字符集
mysql -u root -p oio
SHOW CREATE DATABASE oio;

# 2. 修改數據庫字符集
ALTER DATABASE oio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3. 修改表字符集
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tasks CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE user_columns CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE task_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. 驗證連接字符集（backend/config/database.php）
$this->conn->set_charset('utf8mb4');

# 5. 刷新頁面
```

---

### 問題 3: 數據庫連接超時

**症狀**:
```
MySQL server has gone away
Connection timed out
```

**原因**: 連接閒置過久或 MySQL max_allowed_packet 過小

**解決方案**:

```bash
# 1. 在 backend/config/database.php 中添加重連機制
if (!$this->conn->ping()) {
    $this->conn->close();
    $this->conn = new mysqli(...);
}

# 2. 檢查 MySQL 配置
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_allowed_packet';"

# 3. 修改 my.cnf (Linux) 或 my.ini (Windows)
[mysqld]
max_allowed_packet=16M
wait_timeout=28800
interactive_timeout=28800

# 4. 重啟 MySQL
net stop MySQL80
net start MySQL80
```

---

## 🔄 數據同步問題

### 問題: 欄位被重命名後任務狀態丟失

**症狀**:
- 修改欄位名稱後，該欄位的所有任務狀態變為 NULL

**原因**: 欄位重命名未同步任務狀態

**解決方案**:

```bash
# 1. 檢查是否有遷移腳本
cat C:\D\sideproject\oio\migrate_columns.sql

# 2. 如果沒有，執行手動更新
mysql -u root -p oio
UPDATE tasks SET status = '新欄位名' WHERE status = '舊欄位名' AND user_id = <用戶ID>;

# 3. 驗證更新
SELECT DISTINCT status FROM tasks WHERE user_id = <用戶ID>;

# 4. 檢查後端是否實現了自動遷移
# 文件: backend/routes/columns.php
# 搜索: UPDATE tasks SET status
```

---

## 🧹 清理與重置

### 完全重置開發環境

```bash
# 1. 停止所有服務
# 關閉 PHP 服務器 (Ctrl+C)
# 關閉 Vite 開發服務器 (Ctrl+C)

# 2. 清除數據庫
mysql -u root -p
DROP DATABASE oio;
CREATE DATABASE oio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oio;
SOURCE C:\D\sideproject\oio\init_database.sql;

# 3. 清除前端緩存
cd C:\D\sideproject\oio\frontend
rm -r node_modules package-lock.json dist
npm install

# 4. 清除瀏覽器緩存
# Chrome: Ctrl+Shift+Delete → 清除所有數據

# 5. 重新啟動
# 終端 1: cd backend && php -S localhost:6001
# 終端 2: cd frontend && npm run dev
```

---

## 📊 性能診斷

### 查詢性能分析

```bash
# 1. 啟用 MySQL 慢查詢日誌
mysql -u root -p
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

# 2. 運行操作，然後查看日誌
# Windows: C:\ProgramData\MySQL\MySQL Server 8.0\Data\hostname-slow.log
# Linux: /var/log/mysql/mysql-slow.log

# 3. 分析慢查詢
mysqldumpslow /path/to/slow.log | head -20

# 4. 在查詢上添加索引
# 示例：經常按 user_id 和 created_date 查詢
CREATE INDEX idx_user_date ON tasks(user_id, created_date);
```

### 前端性能分析

```javascript
// 在瀏覽器 Console 執行
console.time('fetchTasks')
// ... 執行操作
console.timeEnd('fetchTasks')

// 查看 Network 標籤
// 檢查 API 響應時間和大小

// 使用 Performance API
performance.measure('navigation')
```

---

## 📞 獲取更多幫助

### 檢查日誌

```bash
# 後端 PHP 日誌
tail -f C:\xampp\php\logs\php_error.log  # Windows (XAMPP)
tail -f /var/log/php-fpm.log             # Linux

# MySQL 日誌
tail -f C:\ProgramData\MySQL\MySQL Server 8.0\Data\error.log  # Windows
tail -f /var/log/mysql/error.log                              # Linux

# Vite 開發服務器日誌
# 直接在終端查看
npm run dev
```

### 導出診斷信息

```bash
# 保存 PHP 版本信息
php -v > diagnostics.txt

# 保存 Node 版本信息
node -v >> diagnostics.txt
npm -v >> diagnostics.txt

# 保存 MySQL 信息
mysql -u root -p -e "SELECT VERSION();" >> diagnostics.txt

# 保存數據庫結構
mysqldump -u root -p oio --no-data > database_schema.sql
```

---

**更新日期**: 2026年3月6日
**最後修改**: 新增週視圖匯出相關故障排除
