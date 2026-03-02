# 工作记录工具 - 后端 API 文档

## 启动服务器

### 使用 PHP 内置服务器（开发环境）

```bash
cd C:\D\sideproject\oio\backend
php -S localhost:6001
```

访问: http://localhost:6001/api/health

### 使用 Apache/Nginx

确保配置正确的虚拟主机指向 `backend` 目录。

---

## API 端点

### 1. 用户注册
**POST** `/api/auth?action=register`

```json
{
  "username": "testuser",
  "email": "test@example.com",
  "password": "password123"
}
```

### 2. 用户登录
**POST** `/api/auth?action=login`

```json
{
  "username": "testuser",
  "password": "password123"
}
```

响应:
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "user_id": 1,
    "username": "testuser",
    "email": "test@example.com"
  }
}
```

### 3. 验证 Token
**GET** `/api/auth?action=verify&token=abc123...`

### 4. 获取任务列表
**GET** `/api/tasks?date=2024-02-26`

需要登录（Session）

### 5. 创建任务
**POST** `/api/tasks`

```json
{
  "title": "完成项目文档",
  "description": "编写 API 文档",
  "created_date": "2024-02-26"
}
```

### 6. 更新任务
**PUT** `/api/tasks/1`

```json
{
  "status": "inProgress",
  "title": "更新的标题（可选）"
}
```

### 7. 删除任务
**DELETE** `/api/tasks/1`

---

## 目录结构

```
backend/
├── index.php           # 入口文件
├── config/
│   ├── database.php    # 数据库连接
│   ├── cors.php        # CORS 配置
│   └── response.php    # 响应格式
├── routes/
│   ├── auth.php        # 认证路由
│   └── tasks.php       # 任务路由
├── .htaccess           # Apache 重写规则
└── README.md
```

---

## 调试

### 启用错误日志

编辑 `index.php`，检查错误报告设置：

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 测试 API

使用 Postman 或 cURL：

```bash
# 测试服务器健康状态
curl http://localhost:6001/api/health

# 注册用户
curl -X POST http://localhost:6001/api/auth?action=register \
  -H "Content-Type: application/json" \
  -d '{"username":"test","email":"test@example.com","password":"123456"}'
```

---

## 注意事项

1. **数据库连接**: 确保 MySQL 正在运行，用户为 `root`，密码为 `password`
2. **Session**: 当前使用 PHP Session，可根据需要升级为 JWT
3. **CORS**: 已配置允许 `localhost:6000` 跨域请求
4. **时区**: 设置为 `Asia/Shanghai`，可根据需要修改

