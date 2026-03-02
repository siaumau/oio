-- ============================================================
-- 迁移脚本：为tasks表添加工时字段
-- ============================================================

-- 检查列是否已存在，如果不存在则添加
ALTER TABLE tasks ADD COLUMN duration DECIMAL(8, 2) DEFAULT 0 COMMENT '工时（小时）' AFTER description;

-- 验证添加成功
DESC tasks;
