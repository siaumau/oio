# 任务编辑功能 - 实现指南

## 📋 功能说明

用户现在可以通过**点击任务卡片**来编辑任务的：
- ✏️ **标题** - 任务名称
- 📝 **描述** - 任务详细描述
- ⏱️ **工时** - 该任务花费的时间（小时）

## 🔧 需要执行的步骤

### 1️⃣ 数据库迁移 - 添加工时字段

执行此脚本来为tasks表添加工时列：

```bash
mysql -h localhost -u root -p oio < C:\D\sideproject\oio\add_duration_field.sql
```

或在 phpMyAdmin 中执行：
```sql
ALTER TABLE tasks ADD COLUMN duration DECIMAL(8, 2) DEFAULT 0 COMMENT '工时（小时）' AFTER description;
```

### 2️⃣ 验证修改

检查数据库结构：
```bash
DESC tasks;
```

应该能看到新增的 `duration` 列。

## 💡 使用方式

### 编辑任务
1. **点击任务卡片** - 单击任何任务卡片以打开编辑框
2. **修改信息**：
   - 编辑标题（必填）
   - 编辑描述（可选）
   - 输入工时，以小时为单位（例：2.5 表示 2.5 小时）
3. **保存** - 点击"保存"按钮

### 拖拽任务
- 您仍然可以像以前一样**拖拽任务**来改变其状态
- 拖拽时不会打开编辑框

### 查看工时
- 任务卡片上会显示 **⏱️ 2h** 这样的格式
- 工时为0时不显示

## 📁 修改的文件

### 后端 (PHP)
- **backend/routes/tasks.php**
  - 添加 `duration` 参数支持
  - 修改SELECT查询包含duration字段
  - 修改INSERT返回duration值
  - 修改UPDATE支持更新duration

### 前端 (Vue)
- **frontend/src/stores/taskStore.js**
  - 添加 `updateTaskInfo()` 方法用于编辑任务信息
  - 任务对象现在包含 `duration` 字段

- **frontend/src/views/DashboardView.vue**
  - 添加编辑任务模态框
  - 添加 `openEditTaskModal()` 和 `handleUpdateTask()` 方法
  - 任务卡片显示工时信息
  - 实现点击编辑和拖拽的冲突避免

### 数据库
- **add_duration_field.sql** - 迁移脚本，添加工时列

## 🎯 数据结构

### tasks 表新增字段
```sql
duration DECIMAL(8, 2) DEFAULT 0
```
- 类型：DECIMAL(8, 2) - 支持最多 999999.99 小时
- 默认值：0
- 位置：description 字段之后

## 🧪 测试检查清单

- [ ] 执行数据库迁移脚本
- [ ] 刷新页面，任务卡片可点击
- [ ] 点击任务卡片打开编辑框
- [ ] 编辑标题、描述、工时
- [ ] 保存后检查任务卡片显示更新
- [ ] 拖拽任务到不同列（应该不打开编辑框）
- [ ] 输入负数工时时出现错误提示

## ⚠️ 注意事项

1. **工时字段**：支持小数（如 0.5, 2.5 等）
2. **拖拽优先**：当拖拽任务时，点击事件被禁用以避免冲突
3. **兼容性**：现有任务自动获得 duration = 0
4. **验证**：工时不能为负数

## 🔄 后向兼容性

- 现有任务默认 duration = 0
- 未编辑过的旧任务不会显示工时
- 所有现有功能保持不变
