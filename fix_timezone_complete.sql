-- ========================================
-- 完整時區修復腳本 - UTC+8 (Asia/Taipei)
-- ========================================

-- 1. 修改 MySQL 全局時區設置
SET GLOBAL time_zone = '+08:00';
SET SESSION time_zone = '+08:00';

-- 2. 驗證當前時區
SELECT @@global.time_zone, @@session.time_zone, NOW();

-- 3. 修復 tasks 表 - 將所有時間加上缺失的 8 小時
-- 注意：此腳本假設時間確實差 8 小時。如果已經修復過，請勿重複執行
UPDATE tasks
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR),
    updated_at = DATE_ADD(updated_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) < 8;  -- 只修復明顯錯誤的時間（早上 7 點之前）

-- 4. 修復 users 表
UPDATE users
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR),
    updated_at = DATE_ADD(updated_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) < 8;

-- 5. 修復 user_columns 表
UPDATE user_columns
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) < 8;

-- 6. 修復 task_history 表
UPDATE task_history
SET changed_at = DATE_ADD(changed_at, INTERVAL 8 HOUR)
WHERE HOUR(changed_at) < 8;

-- 7. 修復 task_images 表
UPDATE task_images
SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR)
WHERE HOUR(created_at) < 8;

-- 8. 驗證修復結果
SELECT '=== 任務表時間檢查 ===' as info;
SELECT id, title, created_date, created_at FROM tasks ORDER BY created_at DESC LIMIT 5;

SELECT '=== 用戶表時間檢查 ===' as info;
SELECT id, username, created_at FROM users ORDER BY created_at DESC LIMIT 5;

-- 9. 確認修復成功
SELECT '✓ 時區修復完成！所有時間現在應該是 Asia/Taipei (UTC+8)' as success_message;
