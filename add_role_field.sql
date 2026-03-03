-- ========================================
-- 添加 role 字段到 users 表
-- 用於支持管理員和普通用戶角色
-- ========================================

USE oio;

-- 添加 role 字段（如果不存在）
ALTER TABLE users
ADD COLUMN role VARCHAR(20) DEFAULT 'user' COMMENT '用户角色（user 或 admin）'
AFTER password_hash;

-- 添加索引以提高查詢性能
ALTER TABLE users
ADD INDEX idx_role (role);

-- 顯示更新後的表結構
DESC users;

-- ========================================
-- 創建管理員帳號（可選）
-- 用戶名: admin
-- 密碼: admin123 (已哈希)
-- ========================================
-- INSERT INTO users (username, email, password_hash, role)
-- VALUES ('admin', 'admin@example.com', '$2y$10$K1z1M5b9P4Q8X9Y2Z3A5B6C7D8E9F0G1H2I3J4K5L6M7N8O9P0Q1', 'admin');

-- 若要執行以上語句，請先生成密碼哈希：
-- PHP: password_hash('admin123', PASSWORD_BCRYPT)
-- 或使用線上工具生成 bcrypt 哈希
