-- ============================================================
-- 诊断和修复脚本：检查数据库中的列名编码
-- ============================================================

-- 检查user_columns表中的实际列名（可能是简体或繁体）
SELECT 'user_columns中的列名：' as info;
SELECT id, name, HEX(name) as hex_name FROM user_columns;

-- 检查tasks表中的实际状态值
SELECT 'tasks中的状态值：' as info;
SELECT DISTINCT status, HEX(status) as hex_status FROM tasks;

-- 如果发现列名是繁体，执行以下命令转换为简体
-- 注意：这些是示例命令，请根据实际情况执行

-- 如果user_columns中有繁体字，先转换
-- UPDATE user_columns SET name = '进行中' WHERE name = '進行中';
-- UPDATE user_columns SET name = '暂停' WHERE name = '暫停';

-- 如果tasks中有繁体字，也转换
-- UPDATE tasks SET status = '进行中' WHERE status = '進行中';
-- UPDATE tasks SET status = '暂停' WHERE status = '暫停';

-- 验证所有列名和状态都是简体中文
SELECT 'final check - user_columns:' as info;
SELECT DISTINCT name FROM user_columns ORDER BY name;

SELECT 'final check - tasks statuses:' as info;
SELECT DISTINCT status FROM tasks ORDER BY status;
