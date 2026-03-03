<?php
// ========================================
// 創建管理員帳號腳本
// ========================================

// 連接數據庫
$db = new mysqli('localhost', 'root', 'password', 'oio');

if ($db->connect_error) {
    die('數據庫連接失敗: ' . $db->connect_error);
}

echo "開始創建管理員帳號...\n";

// 管理員信息
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// 檢查帳號是否已存在
$check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
$stmt = $db->prepare($check_sql);
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✗ 帳號已存在\n";
    $db->close();
    exit(1);
}

// 創建管理員帳號
$insert_sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
$role = 'admin';
$stmt = $db->prepare($insert_sql);
$stmt->bind_param('ssss', $username, $email, $password_hash, $role);

if ($stmt->execute()) {
    $user_id = $db->insert_id;
    echo "✓ 成功創建管理員帳號\n";
    echo "  用戶名: admin\n";
    echo "  密碼: admin123\n";
    echo "  用戶ID: $user_id\n";

    // 為管理員創建默認欄位（可選，管理員不需要自己的欄位，因為只看別人的）
    // 但為了保持一致性，也可以創建
    $default_columns = [
        ['name' => '待做', 'order' => 0],
        ['name' => '進行中', 'order' => 1],
        ['name' => '已完成', 'order' => 2],
        ['name' => '暫停', 'order' => 3]
    ];

    $col_sql = "INSERT INTO user_columns (user_id, name, col_order) VALUES (?, ?, ?)";
    $col_stmt = $db->prepare($col_sql);

    foreach ($default_columns as $col) {
        $col_stmt->bind_param('isi', $user_id, $col['name'], $col['order']);
        $col_stmt->execute();
    }

    echo "✓ 已為管理員創建默認欄位\n";
} else {
    echo "✗ 創建帳號失敗: " . $db->error . "\n";
    $db->close();
    exit(1);
}

// 顯示所有用戶
echo "\n--- 所有用戶 ---\n";
$users_result = $db->query("SELECT id, username, email, role FROM users");
while ($row = $users_result->fetch_assoc()) {
    printf("ID: %-3d | 用戶名: %-15s | 郵箱: %-25s | 角色: %s\n",
        $row['id'],
        $row['username'],
        $row['email'],
        $row['role']
    );
}

echo "\n✓ 完成!\n";
$db->close();
?>
