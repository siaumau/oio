<?php
// ========================================
// CORS 跨域配置
// ========================================

class CORS {
    public static function enableCORS() {
        // 允许的来源
        $allowed_origins = [
            'http://localhost:6000',
            'http://127.0.0.1:6000',
            'http://localhost:5173',  // Vite 开发服务器
            'http://localhost:5174',  // Vite 开发服务器（当前）
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

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
