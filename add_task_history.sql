-- ============================================================
-- 遷移腳本：添加任務狀態變化記錄表
-- ============================================================

-- 創建任務歷史記錄表
CREATE TABLE IF NOT EXISTS task_history (
  id INT AUTO_INCREMENT PRIMARY KEY COMMENT '記錄ID',
  user_id INT NOT NULL COMMENT '用戶ID',
  task_id INT NOT NULL COMMENT '任務ID',
  task_title VARCHAR(255) NOT NULL COMMENT '任務標題',
  old_status VARCHAR(50) COMMENT '舊狀態',
  new_status VARCHAR(50) NOT NULL COMMENT '新狀態',
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '變更時間',

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_task_id (task_id),
  INDEX idx_changed_at (changed_at),
  INDEX idx_user_date (user_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任務狀態變化記錄表';

-- 驗證表創建成功
DESC task_history;
SELECT 'task_history table created successfully!' as message;
