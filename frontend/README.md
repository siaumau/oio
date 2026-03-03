# 工作记录工具 - 前端

## 快速开始

### 1. 安装依赖

```bash
cd C:\D\sideproject\oio\frontend
npm install
```

### 2. 启动开发服务器

```bash
npm run dev
```

浏览器自动打开 `http://localhost:6000`

### 3. 构建生产版本

```bash
npm run build
```

---

## 项目结构

```
frontend/
├── src/
│   ├── main.js                 # 应用入口
│   ├── App.vue                 # 根组件
│   ├── styles/
│   │   └── main.css            # 全局样式
│   ├── views/
│   │   ├── LoginView.vue       # 登录/注册页面
│   │   └── DashboardView.vue   # 仪表板（看板视图）
│   ├── stores/
│   │   ├── userStore.js        # 用户状态管理
│   │   └── taskStore.js        # 任务状态管理
│   ├── router/
│   │   └── index.js            # 路由配置
│   └── api/
│       └── (API 调用封装)
├── index.html                  # HTML 入口
├── vite.config.js             # Vite 配置
├── package.json               # 依赖配置
└── README.md
```

---

## 主要功能

✅ **用户认证**
- 注册新用户
- 用户登录
- Session 管理

✅ **任务管理**
- 创建任务
- 删除任务
- 更新任务状态

✅ **看板视图**
- 4 列布局（待做、处理中、完成、暂停）
- 任务卡片显示
- 状态拖拽切换

✅ **日期导航**
- 切换日期查看不同日期的任务
- 上一天、今天、下一天快速导航

---

## 技术栈

- **Vue 3** - 前端框架
- **Vite** - 构建工具
- **Pinia** - 状态管理
- **Axios** - HTTP 客户端
- **CSS3** - 样式

---

## 开发注意

1. **API 地址**: `http://localhost:6001`
2. **开发端口**: `http://localhost:6000`
3. **CORS**: 后端已配置允许跨域请求
4. **Session**: 使用 Cookie 自动携带

---

## 常见问题

### Q: 看板为空？
A: 确保后端服务器正在运行 (`php -S localhost:6001`)，并且数据库中有任务数据。

### Q: 无法登录？
A: 检查：
1. 后端服务器是否运行
2. 数据库是否已初始化（执行 `init_database.sql`）
3. 用户是否存在

### Q: 如何添加测试数据？
A: 在数据库中执行 `init_database.sql` 中的测试数据插入语句。

---

## 下一步优化

- [ ] 添加日历视图
- [ ] 统计图表展示
- [ ] 用户个人设置
- [ ] 深色模式
- [ ] 移动端适配优化
