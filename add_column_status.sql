-- ========================================
-- 为 user_columns 表添加 is_enabled 字段
-- ========================================

ALTER TABLE user_columns
ADD COLUMN is_enabled TINYINT(1) DEFAULT 1 COMMENT '列是否启用（1=启用，0=禁用）' AFTER col_order;

-- 验证修改
DESC user_columns;
