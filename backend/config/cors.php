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
            'http://localhost:5173',  // Vite 开发服务器备选
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

// 立即启用 CORS
CORS::enableCORS();
