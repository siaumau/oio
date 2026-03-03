<?php
// ========================================
// CORS 跨域配置
// ========================================

class CORS {
    public static function enableCORS() {
        // 允许的来源（本地开发环境）
        $allowed_origins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://192.168.0.167:5173',
            'http://192.168.0.167:5174',
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // 检查 origin 是否在允许列表中
        if (in_array($origin, $allowed_origins)) {
            // 必须返回具体的 origin，不能用 *（因为前端用了 credentials: 'include'）
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // 默认允许 localhost（开发环境方便）
            header('Access-Control-Allow-Origin: http://localhost:5173');
        }

        // 当使用 credentials 时，必须明确设置为 true
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 3600');

        // 处理 OPTIONS 预检请求
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// 設置 Session Cookie 參數（在 session_start() 前）
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,  // 7 天
        'path' => '/',
        'domain' => '',
        'secure' => false,        // 本地開發環境設為 false
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    // PHP < 7.3 備用方案
    session_set_cookie_params(
        86400 * 7,  // lifetime
        '/',        // path
        '',         // domain
        false,      // secure
        true        // httponly
    );
}

// 立即啟用 CORS
CORS::enableCORS();

// 啟動 Session
session_start();
