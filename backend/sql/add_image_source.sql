-- 添加 source 字段到 task_images 表
-- source: 'description' - 从编辑器上传, 'attachment' - 从附件区域上传

ALTER TABLE task_images ADD COLUMN source VARCHAR(20) DEFAULT 'attachment' AFTER file_size;

-- 更新现有记录（默认为 attachment）
UPDATE task_images SET source = 'attachment' WHERE source IS NULL;

-- 添加索引
CREATE INDEX idx_source ON task_images(source);
