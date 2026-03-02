-- ============================================================
-- 迁移脚本：将默认列名从英文改为中文
-- 执行此脚本以更新现有用户的列名
-- ============================================================

-- 首先更新 user_columns 表中的列名
UPDATE user_columns SET name = '待做' WHERE name = 'todo';
UPDATE user_columns SET name = '进行中' WHERE name = 'inProgress';
UPDATE user_columns SET name = '已完成' WHERE name = 'completed';
UPDATE user_columns SET name = '暂停' WHERE name = 'suspended';

-- 其次更新 tasks 表中的任务状态
UPDATE tasks SET status = '待做' WHERE status = 'todo';
UPDATE tasks SET status = '进行中' WHERE status = 'inProgress';
UPDATE tasks SET status = '已完成' WHERE status = 'completed';
UPDATE tasks SET status = '暂停' WHERE status = 'suspended';

-- 验证迁移结果
SELECT 'user_columns 中的列名：' as info;
SELECT DISTINCT name FROM user_columns ORDER BY name;

SELECT 'tasks 中的状态值：' as info;
SELECT DISTINCT status FROM tasks ORDER BY status;
