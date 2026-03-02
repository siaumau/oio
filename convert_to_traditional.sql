-- ============================================================
-- 迁移脚本：将所有中文内容改为繁体中文
-- ============================================================

-- 转换 user_columns 表中的默认列名为繁体
UPDATE user_columns SET name = '待做' WHERE name = '待做';
UPDATE user_columns SET name = '進行中' WHERE name IN ('进行中', '進行中');
UPDATE user_columns SET name = '已完成' WHERE name = '已完成';
UPDATE user_columns SET name = '暫停' WHERE name IN ('暂停', '暫停');

-- 转换 tasks 表中的状态为繁体
UPDATE tasks SET status = '待做' WHERE status = '待做';
UPDATE tasks SET status = '進行中' WHERE status IN ('进行中', '進行中');
UPDATE tasks SET status = '已完成' WHERE status = '已完成';
UPDATE tasks SET status = '暫停' WHERE status IN ('暂停', '暫停');

-- 验证结果
SELECT 'user_columns 中的欄位：' as info;
SELECT DISTINCT name FROM user_columns ORDER BY name;

SELECT 'tasks 中的狀態：' as info;
SELECT DISTINCT status FROM tasks ORDER BY status;
