<?php
// ========================================
// 用户认证路由
// ========================================

function handleAuth($method, $db) {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'register':
            if ($method !== 'POST') {
                Response::error('仅支持 POST 请求', 405);
            }
            registerUser($db);
            break;

        case 'login':
            if ($method !== 'POST') {
                Response::error('仅支持 POST 请求', 405);
            }
            loginUser($db);
            break;

        case 'verify':
            if ($method !== 'GET') {
                Response::error('仅支持 GET 请求', 405);
            }
            verifyToken($db);
            break;

        case 'changePassword':
            if ($method !== 'POST') {
                Response::error('仅支持 POST 请求', 405);
            }
            changePassword($db);
            break;

        default:
            Response::error('认证接口不存在', 404);
            break;
    }
}

// ========================================
// 用户注册
// POST /api/auth?action=register
// ========================================
function registerUser($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    // 验证输入
    if (empty($username) || strlen($username) < 3) {
        Response::error('用户名至少 3 个字符', 400);
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error('邮箱格式不正确', 400);
    }

    if (empty($password) || strlen($password) < 6) {
        Response::error('密码至少 6 个字符', 400);
    }

    // 检查用户名或邮箱是否已存在
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $db->prepare($check_sql);
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        Response::error('用户名或邮箱已被注册', 409);
    }

    // 密码哈希
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // 確保 session 已啟動
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 开始事务
    $db->begin_transaction();

    try {
        // 插入用户
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $password_hash);
        $stmt->execute();

        $user_id = $db->insert_id;

        // 为新用户插入 4 个预设欄位
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

        // 提交事务
        $db->commit();

        // 生成 Token 并自動登錄
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['token'] = $token;

        Response::success(
            [
                'token' => $token,
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email
            ],
            '注册成功',
            201
        );
    } catch (Exception $e) {
        // 回滚事务
        $db->rollback();
        Response::error('注册失败: ' . $e->getMessage(), 500);
    }
}

// ========================================
// 用户登录
// POST /api/auth?action=login
// ========================================
function loginUser($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        Response::error('用户名和密码不能为空', 400);
    }

    // 查询用户
    $sql = "SELECT id, username, email, password_hash FROM users WHERE username = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('用户名或密码错误', 401);
    }

    $user = $result->fetch_assoc();

    // 验证密码
    if (!password_verify($password, $user['password_hash'])) {
        Response::error('用户名或密码错误', 401);
    }

    // 生成 Token（简单实现，生产环境建议使用 JWT）
    $token = bin2hex(random_bytes(32));

    // 確保 session 已啟動
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['token'] = $token;

    Response::success([
        'token' => $token,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ], '登录成功');
}

// ========================================
// 验证 Token
// GET /api/auth?action=verify
// ========================================
function verifyToken($db) {
    // 確保 session 已啟動
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 首先檢查 session
    if (isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
        Response::success([
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ], 'Session 有效');
        return;
    }

    // 如果沒有 session，檢查 GET 參數中的 token
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        Response::error('Token 缺失', 400);
    }

    if (!isset($_SESSION['token']) || $_SESSION['token'] !== $token) {
        Response::error('Token 無效或過期', 401);
    }

    Response::success([
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username']
    ], 'Token 有效');
}

// ========================================
// 更改密碼
// POST /api/auth?action=changePassword
// ========================================
function changePassword($db) {
    // 確保 session 已啟動
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 檢查用戶是否已登入
    if (!isset($_SESSION['user_id'])) {
        Response::error('未授權訪問，請先登入', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('請求體為空', 400);
    }

    $old_password = trim($input['oldPassword'] ?? '');
    $new_password = trim($input['newPassword'] ?? '');
    $confirm_password = trim($input['confirmPassword'] ?? '');

    // 驗證輸入
    if (empty($old_password)) {
        Response::error('舊密碼不能為空', 400);
    }

    if (empty($new_password) || strlen($new_password) < 6) {
        Response::error('新密碼至少 6 個字符', 400);
    }

    if ($new_password !== $confirm_password) {
        Response::error('新密碼和確認密碼不相符', 400);
    }

    if ($old_password === $new_password) {
        Response::error('新密碼不能與舊密碼相同', 400);
    }

    $user_id = $_SESSION['user_id'];

    // 取得用戶目前的密碼
    $sql = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('用戶不存在', 404);
    }

    $user = $result->fetch_assoc();

    // 驗證舊密碼
    if (!password_verify($old_password, $user['password_hash'])) {
        Response::error('舊密碼錯誤', 401);
    }

    // 密碼雜湊
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    // 更新密碼
    $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->bind_param('si', $password_hash, $user_id);

    if ($update_stmt->execute()) {
        Response::success([], '密碼更改成功');
    } else {
        Response::error('密碼更改失敗', 500);
    }
}
