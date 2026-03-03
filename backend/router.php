<?php
/**
 * PHP 内置服务器路由脚本
 * 作用：
 * 1. 如果请求的文件或目录存在（如 CSS、JS、图片等），直接返回
 * 2. 如果请求的文件不存在，都转发到 index.php 处理（API 请求、路由等）
 *
 * 启动方式：
 * php -S 192.168.0.167:6001 router.php
 */

// 获取请求的 URI（例如 /api/auth?action=login）
$requested_file = __DIR__ . $_SERVER["REQUEST_URI"];

// 移除 URL 中的查询字符串（? 后面的部分）
$requested_file_without_query = strtok($requested_file, '?');

// 检查请求的文件或目录是否真实存在
// 例如：uploads 目录、静态文件等
if (file_exists($requested_file_without_query) && is_file($requested_file_without_query)) {
    // 文件存在，让 PHP 内置服务器处理（返回文件内容）
    return false;
}

if (is_dir($requested_file_without_query)) {
    // 目录存在，让 PHP 内置服务器处理
    return false;
}

// 文件不存在，转发所有请求到 index.php
// index.php 会根据 REQUEST_URI 进行路由分发
require __DIR__ . '/index.php';
