<?php
// API Configuration for Backend
// 動態生成 API 基礎 URL，適應不同環境（開發、生產等）

function getApiBaseUrl() {
    // 從請求頭自動檢測當前訪問的協議和域名
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $hostname = $_SERVER['HTTP_HOST'];  // 包含域名和端口
    $port = 6001;

    // 如果已包含端口，直接使用；否則添加端口
    if (strpos($hostname, ':') !== false) {
        // 已有端口
        return $protocol . $hostname;
    } else {
        // 需要添加端口
        return $protocol . $hostname . ':' . $port;
    }
}

// 導出常量供其他文件使用
define('API_BASE_URL', getApiBaseUrl());
?>
