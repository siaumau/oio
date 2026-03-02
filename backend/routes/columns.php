<?php
// ========================================
// 用户栏位管理路由
// ========================================

function handleColumns($method, $id, $db) {
    // 验证用户是否已登录
    if (!isset($_SESSION['user_id'])) {
        Response::error('未授权访问，请先登录', 401);
    }

    $user_id = $_SESSION['user_id'];

    switch ($method) {
        case 'GET':
            getColumnsList($user_id, $db);
            break;

        case 'POST':
            createColumn($user_id, $db);
            break;

        case 'PUT':
            if (empty($id)) {
                Response::error('栏位 ID 缺失', 400);
            }
            updateColumn($user_id, $id, $db);
            break;

        case 'DELETE':
            if (empty($id)) {
                Response::error('栏位 ID 缺失', 400);
            }
            deleteColumn($user_id, $id, $db);
            break;

        default:
            Response::error('不支持的请求方法', 405);
            break;
    }
}

// ========================================
// 获取栏位列表
// GET /api/columns
// ========================================
function getColumnsList($user_id, $db) {
    $sql = "SELECT id, name, col_order, is_enabled FROM user_columns WHERE user_id = ? ORDER BY col_order ASC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }

    Response::success(['columns' => $columns], '获取成功');
}

// ========================================
// 创建栏位
// POST /api/columns
// Body: { "name": "栏位名" }
// ========================================
function createColumn($user_id, $db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    $name = trim($input['name'] ?? '');

    if (empty($name) || strlen($name) < 2) {
        Response::error('栏位名称至少 2 个字符', 400);
    }

    if (strlen($name) > 50) {
        Response::error('栏位名称不超过 50 个字符', 400);
    }

    // 检查栏位名是否已存在（同一用户）
    $check_sql = "SELECT id FROM user_columns WHERE user_id = ? AND name = ?";
    $stmt = $db->prepare($check_sql);
    $stmt->bind_param('is', $user_id, $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        Response::error('该栏位名已存在', 409);
    }

    // 获取最大的 col_order
    $order_sql = "SELECT MAX(col_order) as max_order FROM user_columns WHERE user_id = ?";
    $stmt = $db->prepare($order_sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $col_order = ($row['max_order'] ?? -1) + 1;

    // 插入新栏位
    $is_enabled = 1;
    $sql = "INSERT INTO user_columns (user_id, name, col_order, is_enabled) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('isii', $user_id, $name, $col_order, $is_enabled);

    if ($stmt->execute()) {
        $column_id = $db->insert_id;
        Response::success(
            ['id' => $column_id, 'name' => $name, 'col_order' => $col_order, 'is_enabled' => $is_enabled],
            '栏位创建成功',
            201
        );
    } else {
        Response::error('创建失败: ' . $db->error, 500);
    }
}

// ========================================
// 更新栏位
// PUT /api/columns/:id
// Body: { "name": "新栏位名", "col_order": 2, "is_enabled": 1 }
// ========================================
function updateColumn($user_id, $column_id, $db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        Response::error('请求体为空', 400);
    }

    // 验证栏位是否属于该用户
    $verify_sql = "SELECT id, name FROM user_columns WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($verify_sql);
    $stmt->bind_param('ii', $column_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('栏位不存在或无权限修改', 404);
    }

    $old_column = $result->fetch_assoc();
    $current_name = $old_column['name'];

    // 准备更新字段
    $updates = [];
    $types = '';
    $values = [];

    // 更新名称（如果提供且不同）
    if (isset($input['name'])) {
        $name = trim($input['name']);

        if (empty($name) || strlen($name) < 2) {
            Response::error('栏位名称至少 2 个字符', 400);
        }

        if (strlen($name) > 50) {
            Response::error('栏位名称不超过 50 个字符', 400);
        }

        // 只在名称改变时才检查唯一性
        if ($name !== $current_name) {
            $check_sql = "SELECT id FROM user_columns WHERE user_id = ? AND name = ?";
            $stmt = $db->prepare($check_sql);
            $stmt->bind_param('is', $user_id, $name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                Response::error('该栏位名已存在', 409);
            }
        }

        $updates[] = "name = ?";
        $types .= 's';
        $values[] = $name;
    }

    // 更新显示顺序
    if (isset($input['col_order'])) {
        $col_order = intval($input['col_order']);
        $updates[] = "col_order = ?";
        $types .= 'i';
        $values[] = $col_order;
    }

    // 更新启用状态
    if (isset($input['is_enabled'])) {
        $is_enabled = intval($input['is_enabled']);
        $updates[] = "is_enabled = ?";
        $types .= 'i';
        $values[] = $is_enabled;
    }

    if (empty($updates)) {
        Response::error('没有更新字段', 400);
    }

    // 执行更新
    $types .= 'ii';
    $values[] = $column_id;
    $values[] = $user_id;

    $sql = "UPDATE user_columns SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);

    // 动态绑定参数
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $values));

    if ($stmt->execute()) {
        // 返回更新后的完整数据
        $fetch_sql = "SELECT id, name, col_order, is_enabled FROM user_columns WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($fetch_sql);
        $stmt->bind_param('ii', $column_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $updated_col = $result->fetch_assoc();

        Response::success($updated_col, '更新成功');
    } else {
        Response::error('更新失败: ' . $db->error, 500);
    }
}

// ========================================
// 删除栏位
// DELETE /api/columns/:id
// 删除前会将该栏位中的所有任务状态改为 'todo'
// ========================================
function deleteColumn($user_id, $column_id, $db) {
    // 验证栏位是否属于该用户
    $verify_sql = "SELECT name FROM user_columns WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($verify_sql);
    $stmt->bind_param('ii', $column_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Response::error('栏位不存在或无权限删除', 404);
    }

    $row = $result->fetch_assoc();
    $column_name = $row['name'];

    // 开始事务
    $db->begin_transaction();

    try {
        // 将该栏位中的所有任务移到 '待做' 栏位
        // 支持简体、繁体和英文列名的向后兼容
        $target_status = '待做';

        $update_sql = "UPDATE tasks SET status = ? WHERE user_id = ? AND (status = ? OR status = ? OR status = ?)";
        $stmt = $db->prepare($update_sql);

        // 处理繁体列名映射
        $normalized_column = $column_name;
        if ($column_name === '進行中') {
            $normalized_column = '进行中';
        } elseif ($column_name === '暫停') {
            $normalized_column = '暂停';
        }

        // 查询时同时检查简体和繁体
        $traditional = '';
        if ($normalized_column === '进行中') {
            $traditional = '進行中';
        } elseif ($normalized_column === '暂停') {
            $traditional = '暫停';
        }

        $stmt->bind_param('sisss', $target_status, $user_id, $column_name, $normalized_column, $traditional);
        $stmt->execute();

        // 删除栏位
        $delete_sql = "DELETE FROM user_columns WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($delete_sql);
        $stmt->bind_param('ii', $column_id, $user_id);
        $stmt->execute();

        // 提交事务
        $db->commit();

        Response::success(['id' => $column_id], '删除成功，任务已移到待做栏位');
    } catch (Exception $e) {
        // 回滚事务
        $db->rollback();
        Response::error('删除失败: ' . $e->getMessage(), 500);
    }
}
