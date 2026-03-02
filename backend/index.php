<?php
// ========================================
// 后端入口文件
// localhost:6001
// ========================================

// 启用错误报告（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置默认时区为台北（Taiwan）
date_default_timezone_set('Asia/Taipei');

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
    // 处理上传文件的直接访问
    if ($action === 'uploads') {
        serveUploadedFile($path_parts);
    } else {
        // 简单的路由分发
        switch ($action) {
            case 'api':
                handleApi($method, $sub_action, $id, $db);
                break;

            default:
                Response::error('路由不存在', 404);
                break;
        }
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

// ========================================
// 提供上传文件的访问
// ========================================
function serveUploadedFile($path_parts) {
    // 移除 'uploads' 部分，构建文件路径
    array_shift($path_parts);

    // 清理路径以防止目录遍历攻击
    $file_subpath = implode('/', $path_parts);
    $file_subpath = str_replace('..', '', $file_subpath); // 移除 .. 防止目录遍历

    $file_path = __DIR__ . '/uploads/' . $file_subpath;

    // 确保文件存在且在上传目录内
    if (!file_exists($file_path) || !is_file($file_path)) {
        header('HTTP/1.1 404 Not Found');
        exit('File not found');
    }

    // 确保文件在 uploads 目录内（防止访问其他目录）
    $real_path = realpath($file_path);
    $upload_dir = realpath(__DIR__ . '/uploads');
    if ($real_path === false || strpos($real_path, $upload_dir) !== 0) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied');
    }

    // 设置正确的 MIME 类型
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = $mime_types[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: public, max-age=3600');

    // 读取并输出文件
    readfile($file_path);
    exit;
}
