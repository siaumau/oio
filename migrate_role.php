<?php
// ========================================
// 數據庫遷移腳本 - 添加 role 字段
// ========================================

// 連接數據庫
$db = new mysqli('localhost', 'root', 'password', 'oio');

if ($db->connect_error) {
    die('數據庫連接失敗: ' . $db->connect_error);
}

echo "開始遷移...\n";

// 1. 檢查 role 字段是否存在
$check_sql = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $db->query($check_sql);

if ($result && $result->num_rows > 0) {
    echo "✓ role 字段已存在\n";
} else {
    // 添加 role 字段
    $alter_sql = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' COMMENT '用户角色（user 或 admin）'";
    if ($db->query($alter_sql)) {
        echo "✓ 成功添加 role 字段\n";
    } else {
        echo "✗ 添加 role 字段失敗: " . $db->error . "\n";
        exit(1);
    }

    // 添加索引
    $index_sql = "ALTER TABLE users ADD INDEX idx_role (role)";
    if ($db->query($index_sql)) {
        echo "✓ 成功添加 idx_role 索引\n";
    } else {
        echo "✗ 添加索引失敗: " . $db->error . "\n";
        // 繼續，這個失敗不是致命的
    }
}

// 2. 顯示更新後的表結構
echo "\n--- 當前表結構 ---\n";
$desc_result = $db->query("DESC users");
while ($row = $desc_result->fetch_assoc()) {
    printf("%-15s %-25s %-10s %-10s\n",
        $row['Field'],
        $row['Type'],
        $row['Null'],
        $row['Key']
    );
}

echo "\n✓ 遷移完成!\n";
echo "\n下一步：\n";
echo "1. 在 MySQL 中執行以下語句創建管理員帳號：\n";
echo "   INSERT INTO users (username, email, password_hash, role)\n";
echo "   VALUES ('admin', 'admin@example.com', '\$2y\$10\$...', 'admin');\n";
echo "\n   密碼 'admin123' 的 bcrypt 哈希值:\n";
echo "   " . password_hash('admin123', PASSWORD_BCRYPT) . "\n";

$db->close();
?>
