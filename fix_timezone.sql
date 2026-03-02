-- ========================================
-- 修復時區問題 - 將 UTC 時間轉換為 UTC+8
-- 執行此腳本修復已存在的錯誤時間戳
-- ========================================

-- 修復 tasks 表中的時間戳（加 8 小時）
UPDATE tasks
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR),
    updated_at = DATE_ADD(updated_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) != HOUR(NOW());

-- 修復 users 表中的時間戳（加 8 小時）
UPDATE users
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR),
    updated_at = DATE_ADD(updated_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) != HOUR(NOW());

-- 修復 task_history 表中的時間戳（加 8 小時）
UPDATE task_history
SET changed_at = DATE_ADD(changed_at, INTERVAL 8 HOUR)
WHERE HOUR(changed_at) != HOUR(NOW());

-- 修復 task_images 表中的時間戳（加 8 小時）
UPDATE task_images
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) != HOUR(NOW());

-- 驗證修復結果
SELECT '已修復 tasks 表' as info;
SELECT id, title, created_at, updated_at FROM tasks ORDER BY created_at DESC LIMIT 5;

SELECT '已修復 users 表' as info;
SELECT id, username, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 5;

SELECT '已修復 task_history 表' as info;
SELECT id, task_id, changed_at FROM task_history ORDER BY changed_at DESC LIMIT 5;
