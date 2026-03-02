<?php
// ========================================
// 任务管理路由
// ========================================

function handleTasks($method, $id, $db) {
    // 验证用户是否已登录
    if (!isset($_SESSION['user_id'])) {
        Response::error('未授权访问，请先登录', 401);
    }

    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if ($action === 'history') {
                getTaskHistory($user_id, $db);
            } else {
                getTasksList($user_id, $db);
            }
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
// 獲取任務狀態變化歷史記錄
// GET /api/tasks?action=history&limit=50&offset=0
// ========================================
function getTaskHistory($user_id, $db) {
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $task_id = intval($_GET['task_id'] ?? 0);

    // 限制limit最大值
    $limit = min($limit, 100);

    if ($task_id > 0) {
        // 獲取特定任務的歷史記錄
        $sql = "SELECT id, task_id, task_title, old_status, new_status, changed_at
                FROM task_history
                WHERE user_id = ? AND task_id = ?
                ORDER BY changed_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iiii', $user_id, $task_id, $limit, $offset);
    } else {
        // 獲取所有任務的歷史記錄
        $sql = "SELECT id, task_id, task_title, old_status, new_status, changed_at
                FROM task_history
                WHERE user_id = ?
                ORDER BY changed_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iii', $user_id, $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    // 獲取總數量用於分頁
    if ($task_id > 0) {
        $count_sql = "SELECT COUNT(*) as total FROM task_history WHERE user_id = ? AND task_id = ?";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->bind_param('ii', $user_id, $task_id);
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM task_history WHERE user_id = ?";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->bind_param('i', $user_id);
    }

    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total = intval($count_row['total']);

    Response::success([
        'records' => $records,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ], '獲取成功');
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

    $sql = "SELECT id, title, description, status, duration, created_date, task_order, created_at, updated_at
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

    // 获取该状态下最大的 task_order（支持英文和中文状态名）
    $order_sql = "SELECT MAX(task_order) as max_order FROM tasks WHERE user_id = ? AND created_date = ? AND (status = '待做' OR status = 'todo')";
    $stmt = $db->prepare($order_sql);
    $stmt->bind_param('is', $user_id, $created_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $task_order = ($row['max_order'] ?? 0) + 1;

    // 新建工作使用繁体中文状态
    $status = '待做';
    $sql = "INSERT INTO tasks (user_id, title, description, status, created_date, task_order)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('issssi', $user_id, $title, $description, $status, $created_date, $task_order);

    if ($stmt->execute()) {
        $task_id = $db->insert_id;
        Response::success(
            ['id' => $task_id, 'title' => $title, 'description' => $description, 'status' => $status, 'duration' => 0, 'created_date' => $created_date],
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
    $duration = $input['duration'] ?? null;

    if (!$status && !$title && !$description && $duration === null) {
        Response::error('至少提供一个要更新的字段', 400);
    }

    // 验证任务是否属于该用户，并获取现有信息
    $verify_sql = "SELECT id, title, status FROM tasks WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($verify_sql);
    $stmt->bind_param('ii', $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('任务不存在或无权限修改', 404);
    }

    $task_row = $result->fetch_assoc();
    $old_status = $task_row['status'];
    $task_title = $task_row['title'];

    // 构建 UPDATE 语句
    $update_fields = [];
    $update_values = [];

    if ($status) {
        // 验证该状态值是否存在于用户的自定义列中
        $verify_status_sql = "SELECT id FROM user_columns WHERE user_id = ? AND name = ?";
        $verify_stmt = $db->prepare($verify_status_sql);
        $verify_stmt->bind_param('is', $user_id, $status);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows === 0) {
            // 如果直接查询找不到，尝试繁体/简体转换
            $status_mapping = [
                'todo' => '待做',
                'inProgress' => '進行中',
                'completed' => '已完成',
                'suspended' => '暫停',
                '进行中' => '進行中',
                '暂停' => '暫停'
            ];

            $normalized_status = $status_mapping[$status] ?? null;

            if ($normalized_status) {
                // 再次验证转换后的状态
                $verify_stmt->bind_param('is', $user_id, $normalized_status);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 0) {
                    Response::error('该状态值不存在：' . $status, 400);
                }
            } else {
                Response::error('该状态值不存在：' . $status, 400);
            }
        } else {
            $normalized_status = $status;
        }

        $update_fields[] = "status = ?";
        $update_values[] = $normalized_status;
    }

    if ($title) {
        $update_fields[] = "title = ?";
        $update_values[] = $title;
    }

    if ($description) {
        $update_fields[] = "description = ?";
        $update_values[] = $description;
    }

    if ($duration !== null) {
        // 确保duration是数字
        $duration = floatval($duration);
        if ($duration < 0) {
            Response::error('工时不能为负数', 400);
        }
        $update_fields[] = "duration = ?";
        $update_values[] = $duration;
    }

    $update_values[] = $task_id;
    $update_values[] = $user_id;

    $sql = "UPDATE tasks SET " . implode(', ', $update_fields) . " WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);

    // 动态绑定参数 - 根据字段类型确定类型字符
    $types = '';
    foreach ($update_fields as $field) {
        if (strpos($field, 'duration') !== false) {
            $types .= 'd'; // double/float
        } else {
            $types .= 's'; // string
        }
    }
    $types .= 'ii'; // task_id 和 user_id 都是整数
    $stmt->bind_param($types, ...$update_values);

    if ($stmt->execute()) {
        // 如果更新了状态，记录到任务历史记录表
        if ($status && $status !== $old_status) {
            $history_sql = "INSERT INTO task_history (user_id, task_id, task_title, old_status, new_status) VALUES (?, ?, ?, ?, ?)";
            $history_stmt = $db->prepare($history_sql);
            if ($history_stmt) {
                $history_stmt->bind_param('iisss', $user_id, $task_id, $task_title, $old_status, $normalized_status);
                $history_stmt->execute();
            }
        }
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
