<?php
// ========================================
// 任务管理路由
// ========================================

function handleTasks($method, $id, $db) {
    // 验证用户是否已登录
    session_start();
    if (!isset($_SESSION['user_id'])) {
        Response::error('未授权访问，请先登录', 401);
    }

    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            getTasksList($user_id, $db);
            break;

        case 'POST':
            createTask($user_id, $db);
            break;

        case 'PUT':
            if (empty($id)) {
                Response::error('任务 ID 缺失', 400);
            }
            updateTask($user_id, $id, $db);
            break;

        case 'DELETE':
            if (empty($id)) {
                Response::error('任务 ID 缺失', 400);
            }
            deleteTask($user_id, $id, $db);
            break;

        default:
            Response::error('不支持的请求方法', 405);
            break;
    }
}

// ========================================
// 获取任务列表
// GET /api/tasks?date=2024-02-26
// ========================================
function getTasksList($user_id, $db) {
    $date = $_GET['date'] ?? date('Y-m-d');

    // 验证日期格式
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::error('日期格式不正确，应为 YYYY-MM-DD', 400);
    }

    $sql = "SELECT id, title, description, status, created_date, task_order, created_at, updated_at
            FROM tasks
            WHERE user_id = ? AND created_date = ?
            ORDER BY task_order ASC, created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('is', $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    Response::success(['date' => $date, 'tasks' => $tasks], '获取成功');
}

// ========================================
// 创建任务
// POST /api/tasks
// ========================================
function createTask($user_id, $db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $created_date = trim($input['created_date'] ?? date('Y-m-d'));

    if (empty($title)) {
        Response::error('任务标题不能为空', 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $created_date)) {
        Response::error('日期格式不正确', 400);
    }

    // 获取该状态下最大的 task_order
    $order_sql = "SELECT MAX(task_order) as max_order FROM tasks WHERE user_id = ? AND created_date = ? AND status = 'todo'";
    $stmt = $db->prepare($order_sql);
    $stmt->bind_param('is', $user_id, $created_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $task_order = ($row['max_order'] ?? 0) + 1;

    $status = 'todo';
    $sql = "INSERT INTO tasks (user_id, title, description, status, created_date, task_order)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('issssi', $user_id, $title, $description, $status, $created_date, $task_order);

    if ($stmt->execute()) {
        $task_id = $db->insert_id;
        Response::success(
            ['id' => $task_id, 'title' => $title, 'description' => $description, 'status' => $status, 'created_date' => $created_date],
            '任务创建成功',
            201
        );
    } else {
        Response::error('创建失败: ' . $db->error, 500);
    }
}

// ========================================
// 更新任务状态
// PUT /api/tasks/:id
// ========================================
function updateTask($user_id, $task_id, $db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    $status = $input['status'] ?? null;
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;

    if (!$status && !$title && !$description) {
        Response::error('至少提供一个要更新的字段', 400);
    }

    // 验证任务是否属于该用户
    $verify_sql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($verify_sql);
    $stmt->bind_param('ii', $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('任务不存在或无权限修改', 404);
    }

    // 构建 UPDATE 语句
    $update_fields = [];
    $update_values = [];

    if ($status) {
        $valid_statuses = ['todo', 'inProgress', 'completed', 'suspended'];
        if (!in_array($status, $valid_statuses)) {
            Response::error('无效的状态值', 400);
        }
        $update_fields[] = "status = ?";
        $update_values[] = $status;
    }

    if ($title) {
        $update_fields[] = "title = ?";
        $update_values[] = $title;
    }

    if ($description) {
        $update_fields[] = "description = ?";
        $update_values[] = $description;
    }

    $update_values[] = $task_id;
    $update_values[] = $user_id;

    $sql = "UPDATE tasks SET " . implode(', ', $update_fields) . " WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);

    // 动态绑定参数
    $types = str_repeat('s', count($update_fields)) . 'ii';
    $stmt->bind_param($types, ...$update_values);

    if ($stmt->execute()) {
        Response::success(['id' => $task_id], '更新成功');
    } else {
        Response::error('更新失败: ' . $db->error, 500);
    }
}

// ========================================
// 删除任务
// DELETE /api/tasks/:id
// ========================================
function deleteTask($user_id, $task_id, $db) {
    // 验证任务是否属于该用户
    $verify_sql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($verify_sql);
    $stmt->bind_param('ii', $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('任务不存在或无权限删除', 404);
    }

    $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $task_id, $user_id);

    if ($stmt->execute()) {
        Response::success(['id' => $task_id], '删除成功');
    } else {
        Response::error('删除失败: ' . $db->error, 500);
    }
}
