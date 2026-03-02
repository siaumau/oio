<?php
// ========================================
// 后端入口文件
// localhost:6001
// ========================================

// 启用错误报告（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

// 引入配置文件
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/config/database.php';

// 初始化数据库连接
$db = Database::getInstance();

// 获取请求信息
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_uri, '/'));

// 路由处理
$action = $path_parts[0] ?? '';
$sub_action = $path_parts[1] ?? '';
$id = $path_parts[2] ?? '';

try {
    // 简单的路由分发
    switch ($action) {
        case 'api':
            handleApi($method, $sub_action, $id, $db);
            break;

        default:
            Response::error('路由不存在', 404);
            break;
    }
} catch (Exception $e) {
    Response::error('服务器错误: ' . $e->getMessage(), 500);
}

// ========================================
// 路由处理函数
// ========================================
function handleApi($method, $resource, $id, $db) {
    switch ($resource) {
        case 'auth':
            require_once __DIR__ . '/routes/auth.php';
            handleAuth($method, $db);
            break;

        case 'tasks':
            require_once __DIR__ . '/routes/tasks.php';
            handleTasks($method, $id, $db);
            break;

        case 'columns':
            require_once __DIR__ . '/routes/columns.php';
            handleColumns($method, $id, $db);
            break;

        case 'health':
            Response::success(['status' => 'ok'], '服务器正常');
            break;

        default:
            Response::error('资源不存在', 404);
            break;
    }
}
