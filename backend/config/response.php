<?php
// ========================================
// 统一响应格式
// ========================================

class Response {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function error($message = 'Error', $code = 400, $data = null) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
