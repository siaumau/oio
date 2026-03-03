-- ========================================
-- 工作记录工具 - MySQL 初始化脚本
-- 数据库: oio
-- ========================================

-- 使用数据库
USE oio;

-- ========================================
-- 表 1: users (用户表)
-- ========================================
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
  username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
  email VARCHAR(100) NOT NULL UNIQUE COMMENT '邮箱',
  password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希值',
  role VARCHAR(20) DEFAULT 'user' COMMENT '用户角色（user 或 admin）',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ========================================
-- 表 2: tasks (任务表)
-- ========================================
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY COMMENT '任务ID',
  user_id INT NOT NULL COMMENT '用户ID',
  title VARCHAR(255) NOT NULL COMMENT '任务标题',
  description TEXT COMMENT '任务描述',
  status VARCHAR(50) DEFAULT 'todo' COMMENT '任务状态（动态，支持自定义）',
  created_date DATE NOT NULL COMMENT '任务日期（工作日期）',
  task_order INT DEFAULT 0 COMMENT '同状态下的排序顺序',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_created_date (created_date),
  INDEX idx_status (status),
  INDEX idx_user_date (user_id, created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务表';

-- ========================================
-- 表 3: user_columns (用户自定义栏位表)
-- ========================================
CREATE TABLE user_columns (
  id INT AUTO_INCREMENT PRIMARY KEY COMMENT '栏位ID',
  user_id INT NOT NULL COMMENT '用户ID',
  name VARCHAR(50) NOT NULL COMMENT '栏位名称',
  col_order INT DEFAULT 0 COMMENT '栏位顺序',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  UNIQUE KEY unique_user_column (user_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户自定义栏位表';

-- ========================================
-- 测试数据（可选）
-- ========================================
-- 创建测试用户
INSERT INTO users (username, email, password_hash) VALUES
('testuser', 'test@example.com', '$2y$10$abcdefghijklmnopqrstuvwxyz123456'); -- 密码哈希示例

-- 为测试用户插入默认栏位
INSERT INTO user_columns (user_id, name, col_order) VALUES
(1, 'todo', 0),
(1, 'inProgress', 1),
(1, 'completed', 2),
(1, 'suspended', 3);

-- 创建测试任务
INSERT INTO tasks (user_id, title, description, status, created_date) VALUES
(1, '完成项目文档', '完成MVP项目的技术文档', 'inProgress', CURDATE()),
(1, '代码审查', '审查前端代码质量', 'todo', CURDATE()),
(1, '修复登录bug', '用户登录页面加载慢', 'completed', CURDATE()),
(1, '数据库优化', '添加索引优化查询性能', 'suspended', CURDATE());

-- ========================================
-- 显示表结构验证
-- ========================================
SHOW TABLES;
DESC users;
DESC tasks;
DESC user_columns;
