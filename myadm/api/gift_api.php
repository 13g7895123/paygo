<?php
// 先啟動會話
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://test.paygo.tw');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../include.php");

// 錯誤處理函數
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

// 成功響應函數
function api_success($data = null, $message = null) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

// 權限檢查函數
function check_server_permission($pdo, $server_id, $user_id = null, $is_admin = false) {
    // 管理員有所有權限
    if ($is_admin) {
        return true;
    }

    // 分享用戶檢查 shareuser_server2 表
    if ($user_id) {
        $query = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM servers s
            INNER JOIN shareuser_server2 sus ON s.id = sus.serverid
            WHERE s.auton = :server_id AND sus.uid = :user_id
        ");
        $query->bindParam(':server_id', $server_id);
        $query->bindParam(':user_id', $user_id);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    return false;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    $pdo = openpdo();
    
    switch ($action) {
        // ===== 伺服器相關 API =====
        case 'get_servers':
            handle_get_servers($pdo);
            break;
            
        case 'test':
            // 簡單測試端點
            $session_info = [
                'adminid' => isset($_SESSION["adminid"]) ? $_SESSION["adminid"] : null,
                'shareid' => isset($_SESSION["shareid"]) ? $_SESSION["shareid"] : null,
                'session_id' => session_id(),
                'session_name' => session_name(),
                'session_status' => session_status(),
                'cookie_params' => session_get_cookie_params(),
                'all_session_vars' => $_SESSION,
                'all_cookies' => $_COOKIE
            ];
            api_success($session_info, 'Test endpoint working');
            break;
            
        case 'get_server_details':
            handle_get_server_details($pdo);
            break;
            
        // ===== 物品管理 API =====
        case 'get_server_items':
            handle_get_server_items($pdo);
            break;
            
        case 'add_server_item':
            handle_add_server_item($pdo);
            break;
            
        case 'delete_server_item':
            handle_delete_server_item($pdo);
            break;
            
        case 'update_server_item':
            handle_update_server_item($pdo);
            break;
            
        // ===== 派獎設定 API =====
        case 'get_gift_settings':
            handle_get_gift_settings($pdo);
            break;
            
        case 'save_gift_settings':
            handle_save_gift_settings($pdo);
            break;
            
        case 'get_gift_fields':
            handle_get_gift_fields($pdo);
            break;
            
        // ===== 派獎操作 API =====
        case 'send_gift':
            handle_send_gift($pdo);
            break;
            
        case 'get_gift_logs':
            handle_get_gift_logs($pdo);
            break;
            
        case 'get_gift_log_detail':
            handle_get_gift_log_detail($pdo);
            break;
            
        case 'get_gift_execution_log':
            handle_get_gift_execution_log($pdo);
            break;
            
        case 'test_game_server_connection':
            handle_test_game_server_connection($pdo);
            break;

        default:
            api_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("Gift API Error: " . $e->getMessage());
    api_error('Internal server error: ' . $e->getMessage(), 500);
}

// ===== 處理函數 =====

/**
 * 取得伺服器列表
 */
function handle_get_servers($pdo) {
    $servers = [];
    
    // 檢查用戶權限
    if (!empty($_SESSION["adminid"])) {
        // 管理員 - 顯示所有啟用的伺服器
        $query = $pdo->prepare("SELECT auton, names, id, stats FROM servers WHERE stats = 1 ORDER BY names");
        $query->execute();
        $servers = $query->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($_SESSION["shareid"])) {
        // 分享用戶 - 只顯示有權限的伺服器
        $user_id = $_SESSION["shareid"];
        $sql_str = "SELECT s.auton, s.names, s.id, s.stats 
                    FROM servers s 
                    INNER JOIN shareuser_server2 sus ON s.id = sus.serverid 
                    WHERE sus.uid = :user_id AND s.stats = 1 
                    ORDER BY s.names";
        $query = $pdo->prepare($sql_str);
        $query->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $query->execute();
        $servers = $query->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 沒有權限 - 回傳空陣列
        api_success([], 'No servers available - please login first');
        return;
    }
    
    $result = [];
    foreach ($servers as $server) {
        $result[] = [
            'id' => $server['auton'],
            'name' => $server['names'],
            'suffix' => $server['id'],
            'active' => $server['stats'] == 1
        ];
    }
    
    api_success($result, 'Servers retrieved successfully');
}

/**
 * 取得伺服器詳細資訊
 */
function handle_get_server_details($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    $query = $pdo->prepare("SELECT * FROM servers WHERE auton = :server_id");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $server = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        api_error('Server not found', 404);
    }
    
    // 取得派獎設定
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
    
    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'server' => [
            'id' => $server['auton'],
            'name' => $server['names'],
            'suffix' => $server['id']
        ],
        'gift_settings' => $settings ? [
            'table_name' => $settings['table_name'],
            'account_field' => $settings['account_field'],
            'item_field' => $settings['item_field'],
            'item_name_field' => $settings['item_name_field'],
            'quantity_field' => $settings['quantity_field']
        ] : null,
        'dynamic_fields' => $fields
    ];
    
    api_success($result, 'Server details retrieved successfully');
}

/**
 * 取得伺服器物品列表
 */
function handle_get_server_items($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');

    if (empty($server_id)) {
        api_error('Server ID is required');
    }

    // 檢查用戶權限
    $is_admin = !empty($_SESSION["adminid"]);
    $user_id = !empty($_SESSION["shareid"]) ? $_SESSION["shareid"] : null;

    if (!check_server_permission($pdo, $server_id, $user_id, $is_admin)) {
        api_error('Access denied: You do not have permission to manage this server', 403);
    }
    
    $query = $pdo->prepare("
        SELECT id, server_id, item_code, item_name, is_active, created_at, updated_at
        FROM server_items
        WHERE server_id = :server_id AND is_active = 1
        ORDER BY item_code
    ");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $items = $query->fetchAll(PDO::FETCH_ASSOC);

    api_success($items, 'Server items retrieved successfully');
}

/**
 * 新增伺服器物品
 */
function handle_add_server_item($pdo) {
    $server_id = _r('server_id') ? _r('server_id') : '';
    $item_code = _r('item_code') ? _r('item_code') : '';
    $item_name = _r('item_name') ? _r('item_name') : '';

    if (empty($server_id) || empty($item_code)) {
        api_error('Server ID and item code are required');
    }

    // 檢查是否已存在（根據 item_code 檢查）
    $check_query = $pdo->prepare("
        SELECT id FROM server_items
        WHERE server_id = :server_id AND item_code = :item_code
    ");
    $check_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $check_query->bindValue(':item_code', $item_code, PDO::PARAM_STR);
    $check_query->execute();

    if ($check_query->fetch()) {
        api_error('Item with this code already exists');
    }

    // 新增物品
    $insert_query = $pdo->prepare("
        INSERT INTO server_items (server_id, item_code, item_name, is_active)
        VALUES (:server_id, :item_code, :item_name, 1)
    ");
    $insert_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $insert_query->bindValue(':item_code', $item_code, PDO::PARAM_STR);
    $insert_query->bindValue(':item_name', $item_name, PDO::PARAM_STR);
    $insert_query->execute();

    $item_id = $pdo->lastInsertId();

    api_success([
        'id' => $item_id,
        'server_id' => $server_id,
        'item_code' => $item_code,
        'item_name' => $item_name
    ], 'Item added successfully');
}

/**
 * 刪除伺服器物品
 */
function handle_delete_server_item($pdo) {
    $item_id = _r('item_id') ? _r('item_id') : '';

    if (empty($item_id)) {
        api_error('Item ID is required');
    }

    // 先取得item所屬的server_id以檢查權限
    $item_query = $pdo->prepare("SELECT server_id FROM server_items WHERE id = :item_id");
    $item_query->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $item_query->execute();
    $item = $item_query->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        api_error('Item not found', 404);
    }

    // 檢查用戶權限
    $is_admin = !empty($_SESSION["adminid"]);
    $user_id = !empty($_SESSION["shareid"]) ? $_SESSION["shareid"] : null;

    if (!check_server_permission($pdo, $item['server_id'], $user_id, $is_admin)) {
        api_error('Access denied: You do not have permission to manage this server', 403);
    }
    
    // 軟刪除（設置為不活躍）
    $update_query = $pdo->prepare("
        UPDATE server_items 
        SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
        WHERE id = :item_id
    ");
    $update_query->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $update_query->execute();
    
    if ($update_query->rowCount() === 0) {
        api_error('Item not found', 404);
    }
    
    api_success(null, 'Item deleted successfully');
}

/**
 * 更新伺服器物品
 */
function handle_update_server_item($pdo) {
    $item_id = _r('item_id') ? _r('item_id') : '';
    $item_code = _r('item_code') ? _r('item_code') : '';
    $item_name = _r('item_name') ? _r('item_name') : '';

    if (empty($item_id) || empty($item_code)) {
        api_error('Item ID and item code are required');
    }

    $update_query = $pdo->prepare("
        UPDATE server_items
        SET item_code = :item_code, item_name = :item_name, updated_at = CURRENT_TIMESTAMP
        WHERE id = :item_id
    ");
    $update_query->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $update_query->bindValue(':item_code', $item_code, PDO::PARAM_STR);
    $update_query->bindValue(':item_name', $item_name, PDO::PARAM_STR);
    $update_query->execute();

    if ($update_query->rowCount() === 0) {
        api_error('Item not found', 404);
    }

    api_success(null, 'Item updated successfully');
}

/**
 * 取得派獎設定
 */
function handle_get_gift_settings($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    // 取得基本設定
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
    
    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'settings' => $settings,
        'fields' => $fields
    ];
    
    api_success($result, 'Gift settings retrieved successfully');
}

/**
 * 儲存派獎設定
 */
function handle_save_gift_settings($pdo) {
    $server_id = _r('server_id') ? _r('server_id') : '';
    $table_name = _r('table_name') ? _r('table_name') : '';
    $account_field = _r('account_field') ? _r('account_field') : '';
    $item_field = _r('item_field') ? _r('item_field') : '';
    $item_name_field = _r('item_name_field') ? _r('item_name_field') : '';
    $quantity_field = _r('quantity_field') ? _r('quantity_field') : '';
    $field_names = isset($_POST['field_names']) ? $_POST['field_names'] : [];
    $field_values = isset($_POST['field_values']) ? $_POST['field_values'] : [];
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    // 開始交易
    $pdo->beginTransaction();
    
    try {
        // 儲存基本設定
        if (!empty($table_name) || !empty($account_field) || !empty($item_field) || !empty($quantity_field)) {
            // 檢查是否已存在
            $check_query = $pdo->prepare("SELECT id FROM send_gift_settings WHERE server_id = :server_id");
            $check_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
            $check_query->execute();
            
            if ($existing = $check_query->fetch()) {
                // 更新現有記錄
                $update_query = $pdo->prepare("
                    UPDATE send_gift_settings SET 
                        table_name = :table_name,
                        account_field = :account_field,
                        item_field = :item_field,
                        item_name_field = :item_name_field,
                        quantity_field = :quantity_field,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $update_query->bindValue(':id', $existing['id'], PDO::PARAM_INT);
                $update_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
                $update_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
                $update_query->bindValue(':item_field', $item_field, PDO::PARAM_STR);
                $update_query->bindValue(':item_name_field', $item_name_field, PDO::PARAM_STR);
                $update_query->bindValue(':quantity_field', $quantity_field, PDO::PARAM_STR);
                $update_query->execute();
            } else {
                // 插入新記錄
                $insert_query = $pdo->prepare("
                    INSERT INTO send_gift_settings (server_id, table_name, account_field, item_field, item_name_field, quantity_field) 
                    VALUES (:server_id, :table_name, :account_field, :item_field, :item_name_field, :quantity_field)
                ");
                $insert_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
                $insert_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
                $insert_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
                $insert_query->bindValue(':item_field', $item_field, PDO::PARAM_STR);
                $insert_query->bindValue(':item_name_field', $item_name_field, PDO::PARAM_STR);
                $insert_query->bindValue(':quantity_field', $quantity_field, PDO::PARAM_STR);
                $insert_query->execute();
            }
        }
        
        // 處理動態欄位 - 先刪除舊的
        $delete_fields_query = $pdo->prepare("DELETE FROM send_gift_fields WHERE server_id = :server_id");
        $delete_fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $delete_fields_query->execute();
        
        // 插入新的動態欄位
        if (!empty($field_names) && !empty($field_values)) {
            for ($i = 0; $i < count($field_names); $i++) {
                $field_name = isset($field_names[$i]) ? trim($field_names[$i]) : '';
                $field_value = isset($field_values[$i]) ? trim($field_values[$i]) : '';
                
                // 只保存有內容的欄位
                if (!empty($field_name) && !empty($field_value)) {
                    $insert_field_query = $pdo->prepare("
                        INSERT INTO send_gift_fields (server_id, field_name, field_value, sort_order) 
                        VALUES (:server_id, :field_name, :field_value, :sort_order)
                    ");
                    $insert_field_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':field_name', $field_name, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':field_value', $field_value, PDO::PARAM_STR);
                    $insert_field_query->bindValue(':sort_order', $i, PDO::PARAM_INT);
                    $insert_field_query->execute();
                }
            }
        }
        
        $pdo->commit();
        api_success(null, 'Gift settings saved successfully');
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

/**
 * 取得派獎動態欄位
 */
function handle_get_gift_fields($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    
    if (empty($server_id)) {
        api_error('Server ID is required');
    }
    
    $query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $query->execute();
    $fields = $query->fetchAll(PDO::FETCH_ASSOC);
    
    api_success($fields, 'Gift fields retrieved successfully');
}

/**
 * 處理禮物派發
 */
function handle_send_gift($pdo) {
    $server_id = _r('server_id') ? _r('server_id') : '';
    $server_name = _r('server_name') ? _r('server_name') : '';
    $game_account = _r('game_account') ? _r('game_account') : '';
    $game_accounts_json = _r('game_accounts') ? _r('game_accounts') : '';
    $items_json = _r('items') ? _r('items') : '';
    $operator_id = isset($_SESSION['login_id']) ? $_SESSION['login_id'] : null;
    $operator_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'System';

    if (empty($server_id) || empty($game_account) || empty($items_json)) {
        api_error('Server ID, game account and items are required');
    }

    // 檢查用戶權限
    $is_admin = !empty($_SESSION["adminid"]);
    $user_id = !empty($_SESSION["shareid"]) ? $_SESSION["shareid"] : null;

    if (!check_server_permission($pdo, $server_id, $user_id, $is_admin)) {
        api_error('Access denied: You do not have permission to manage this server', 403);
    }

    // 解析物品資料
    $items = json_decode($items_json, true);
    if (!is_array($items) || empty($items)) {
        api_error('Invalid items data');
    }

    // 處理多個帳號
    $accounts_to_process = [];
    if (!empty($game_accounts_json)) {
        $game_accounts = json_decode($game_accounts_json, true);
        if (is_array($game_accounts) && !empty($game_accounts)) {
            $accounts_to_process = $game_accounts;
        } else {
            $accounts_to_process = [$game_account];
        }
    } else {
        $accounts_to_process = [$game_account];
    }

    // 過濾空帳號
    $accounts_to_process = array_filter($accounts_to_process, function($account) {
        return !empty(trim($account));
    });

    if (empty($accounts_to_process)) {
        api_error('No valid accounts to process');
    }

    // 計算總物品數量
    $total_items = array_sum(array_column($items, 'quantity'));

    // 開始交易
    $pdo->beginTransaction();

    try {
        $log_ids = [];
        $success_count = 0;
        $error_messages = [];

        // 為每個帳號分別處理派獎
        foreach ($accounts_to_process as $account) {
            $account = trim($account);
            if (empty($account)) continue;

            // 記錄派獎日誌
            $client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown'));
            if (strpos($client_ip, ',') !== false) {
                $client_ip = explode(',', $client_ip)[0];
            }
            $client_ip = trim($client_ip);

            $log_query = $pdo->prepare("
                INSERT INTO send_gift_logs (
                    server_id, server_name, game_account, items, total_items,
                    status, operator_id, operator_name, operator_ip
                ) VALUES (
                    :server_id, :server_name, :game_account, :items, :total_items,
                    'pending', :operator_id, :operator_name, :operator_ip
                )
            ");
            $log_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
            $log_query->bindValue(':server_name', $server_name, PDO::PARAM_STR);
            $log_query->bindValue(':game_account', $account, PDO::PARAM_STR);
            $log_query->bindValue(':items', $items_json, PDO::PARAM_STR);
            $log_query->bindValue(':total_items', $total_items, PDO::PARAM_INT);
            $log_query->bindValue(':operator_id', $operator_id, PDO::PARAM_STR);
            $log_query->bindValue(':operator_name', $operator_name, PDO::PARAM_STR);
            $log_query->bindValue(':operator_ip', $client_ip, PDO::PARAM_STR);
            $log_query->execute();

            $log_id = $pdo->lastInsertId();
            $log_ids[] = $log_id;

            // 實際派發物品到遊戲伺服器
            $game_result = execute_gift_to_game_server($pdo, $server_id, $account, $items, $log_id);

            // 更新派獎狀態
            $update_query = $pdo->prepare("
                UPDATE send_gift_logs
                SET status = :status, error_message = :error_message, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update_query->bindValue(':id', $log_id, PDO::PARAM_INT);
            $update_query->bindValue(':status', $game_result['success'] ? 'success' : 'failed', PDO::PARAM_STR);
            $update_query->bindValue(':error_message', isset($game_result['error']) ? $game_result['error'] : null, PDO::PARAM_STR);
            $update_query->execute();

            if ($game_result['success']) {
                $success_count++;
            } else {
                $error_messages[] = "帳號 {$account}: " . $game_result['error'];
            }
        }

        // 檢查結果
        if ($success_count === 0) {
            $pdo->rollback();
            api_error('All gift distributions failed: ' . implode('; ', $error_messages));
        } else if ($success_count < count($accounts_to_process)) {
            // 部分成功，提交但返回警告
            $pdo->commit();
            api_success([
                'log_id' => $log_ids[0], // 返回第一個log_id用於查詢
                'log_ids' => $log_ids,
                'success_count' => $success_count,
                'total_count' => count($accounts_to_process),
                'error_messages' => $error_messages
            ], "部分派獎成功：{$success_count}/" . count($accounts_to_process) . " 個帳號派獎成功");
        } else {
            // 全部成功
            $pdo->commit();

            api_success([
                'log_id' => $log_ids[0], // 返回第一個log_id用於查詢
                'log_ids' => $log_ids,
                'server_id' => $server_id,
                'server_name' => $server_name,
                'game_account' => $game_account,
                'success_count' => $success_count,
                'total_count' => count($accounts_to_process),
                'total_items' => $total_items,
                'status' => 'success'
            ], count($accounts_to_process) > 1 ? "全部派獎成功：{$success_count} 個帳號派獎完成" : 'Gift sent successfully');
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        
        // 記錄錯誤到日誌
        if (isset($log_id)) {
            $error_query = $pdo->prepare("
                UPDATE send_gift_logs 
                SET status = 'failed', error_message = :error, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $error_query->bindValue(':id', $log_id, PDO::PARAM_INT);
            $error_query->bindValue(':error', $e->getMessage(), PDO::PARAM_STR);
            $error_query->execute();
        }
        
        throw $e;
    }
}

/**
 * 取得派獎記錄
 */
function handle_get_gift_logs($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // 構建查詢條件
    $where_conditions = [];
    $params = [];
    
    if (!empty($server_id)) {
        $where_conditions[] = "server_id = :server_id";
        $params[':server_id'] = $server_id;
    }
    
    $where_sql = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 取得總數
    $count_query = $pdo->prepare("SELECT COUNT(*) FROM send_gift_logs $where_sql");
    foreach ($params as $key => $value) {
        $count_query->bindValue($key, $value, PDO::PARAM_STR);
    }
    $count_query->execute();
    $total = $count_query->fetchColumn();
    
    // 取得資料
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $query = $pdo->prepare("
        SELECT id, server_id, server_name, game_account, items, total_items,
               status, error_message, operator_name, operator_ip, created_at, updated_at
        FROM send_gift_logs
        $where_sql
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $type = in_array($key, [':limit', ':offset']) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $query->bindValue($key, $value, $type);
    }
    
    $query->execute();
    $logs = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // 解析 items JSON
    foreach ($logs as &$log) {
        $log['items'] = json_decode($log['items'], true);
    }
    
    api_success([
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => intval($total),
            'items_per_page' => $limit
        ]
    ], 'Gift logs retrieved successfully');
}

/**
 * 取得派獎記錄詳情
 */
function handle_get_gift_log_detail($pdo) {
    $log_id = isset($_GET['log_id']) ? $_GET['log_id'] : (isset($_POST['log_id']) ? $_POST['log_id'] : '');
    
    if (empty($log_id)) {
        api_error('Log ID is required');
    }
    
    $query = $pdo->prepare("
        SELECT sgl.*, s.names as server_full_name 
        FROM send_gift_logs sgl 
        LEFT JOIN servers s ON sgl.server_id = s.auton 
        WHERE sgl.id = :log_id
    ");
    $query->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $query->execute();
    $log = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        api_error('Gift log not found', 404);
    }
    
    // 解析 items JSON
    $log['items'] = json_decode($log['items'], true);
    
    api_success($log, 'Gift log detail retrieved successfully');
}

/**
 * 取得派獎執行記錄和SQL資訊
 */
function handle_get_gift_execution_log($pdo) {
    $log_id = isset($_GET['log_id']) ? $_GET['log_id'] : (isset($_POST['log_id']) ? $_POST['log_id'] : '');
    
    if (empty($log_id)) {
        api_error('Log ID is required');
    }
    
    // 取得派獎記錄詳情
    $query = $pdo->prepare("
        SELECT sgl.*, s.names as server_full_name, s.db_ip, s.db_port, s.db_name, s.db_user, s.db_pid
        FROM send_gift_logs sgl 
        LEFT JOIN servers s ON sgl.server_id = s.auton 
        WHERE sgl.id = :log_id
    ");
    $query->bindValue(':log_id', $log_id, PDO::PARAM_INT);
    $query->execute();
    $log = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        api_error('Gift log not found', 404);
    }
    
    // 解析物品資料
    $items = json_decode($log['items'], true);
    
    // 取得派獎設定資訊
    $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $settings_query->bindValue(':server_id', $log['server_id'], PDO::PARAM_STR);
    $settings_query->execute();
    $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
    
    // 取得動態欄位
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $log['server_id'], PDO::PARAM_STR);
    $fields_query->execute();
    $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
    
    // 生成可能的執行SQL
    $execution_sqls = [];
    
    if ($settings && !empty($settings['table_name']) && !empty($settings['account_field'])) {
        $table_name = $settings['table_name'];
        $account_field = $settings['account_field'];
        $item_field = $settings['item_field'] ?: 'item_id'; // 預設值
        $quantity_field = $settings['quantity_field'] ?: 'quantity'; // 預設值
        $game_account = $log['game_account'];
        
        // 為每個物品生成INSERT SQL
        foreach ($items as $item) {
            $item_code = $item['itemCode'];
            $quantity = $item['quantity'];
            
            // 基本SQL模板 - 使用設定的欄位名稱
            $base_sql = "INSERT INTO `{$table_name}` (`{$account_field}`, `{$item_field}`, `{$quantity_field}`) VALUES ('{$game_account}', '{$item_code}', {$quantity})";
            
            // 如果有動態欄位，加入到SQL中
            if (!empty($fields)) {
                $additional_fields = [];
                $additional_values = [];
                
                foreach ($fields as $field) {
                    $additional_fields[] = "`{$field['field_name']}`";
                    $additional_values[] = "'{$field['field_value']}'";
                }
                
                if (!empty($additional_fields)) {
                    $base_sql = "INSERT INTO `{$table_name}` (`{$account_field}`, `{$item_field}`, `{$quantity_field}`, " .
                               implode(', ', $additional_fields) . ") VALUES ('{$game_account}', '{$item_code}', {$quantity}, " .
                               implode(', ', $additional_values) . ")";
                }
            }
            
            $execution_sqls[] = [
                'item' => $item,
                'sql' => $base_sql,
                'description' => "為帳號 {$game_account} 新增物品 {$item['itemName']} x{$quantity}"
            ];
        }
    }
    
    // 整理回傳資料
    $result = [
        'log' => [
            'id' => $log['id'],
            'server_id' => $log['server_id'],
            'server_name' => $log['server_name'],
            'server_full_name' => $log['server_full_name'],
            'game_account' => $log['game_account'],
            'total_items' => $log['total_items'],
            'status' => $log['status'],
            'error_message' => $log['error_message'],
            'operator_name' => $log['operator_name'],
            'created_at' => $log['created_at'],
            'updated_at' => $log['updated_at']
        ],
        'items' => $items,
        'server_info' => [
            'db_ip' => $log['db_ip'],
            'db_port' => $log['db_port'],
            'db_name' => $log['db_name'],
            'db_user' => $log['db_user'],
            'db_pid' => $log['db_pid']
        ],
        'gift_settings' => $settings,
        'dynamic_fields' => $fields,
        'execution_sqls' => $execution_sqls,
        'summary' => [
            'total_sqls' => count($execution_sqls),
            'target_database' => isset($log['db_name']) ? $log['db_name'] : 'Not configured',
            'target_table' => isset($settings['table_name']) ? $settings['table_name'] : 'Not configured',
            'account_field' => isset($settings['account_field']) ? $settings['account_field'] : 'Not configured'
        ]
    ];
    
    api_success($result, 'Gift execution log retrieved successfully');
}

/**
 * 測試遊戲伺服器連線
 */
function handle_test_game_server_connection($pdo) {
    $server_id = isset($_GET['server_id']) ? $_GET['server_id'] : (isset($_POST['server_id']) ? $_POST['server_id'] : '');
    $items = isset($_POST['items']) ? $_POST['items'] : null; // 要送出的道具清單，用來檢查是否需要驗證道具名稱欄位
    $game_account = isset($_POST['game_account']) ? $_POST['game_account'] : null; // 實際的遊戲帳號
    $quick_test = isset($_POST['quick_test']) ? $_POST['quick_test'] : false; // 快速測試模式，跳過部分檢查

    if (empty($server_id)) {
        api_error('Server ID is required');
    }

    // 檢查用戶權限
    $is_admin = !empty($_SESSION["adminid"]);
    $user_id = !empty($_SESSION["shareid"]) ? $_SESSION["shareid"] : null;

    if (!check_server_permission($pdo, $server_id, $user_id, $is_admin)) {
        api_error('Access denied: You do not have permission to manage this server', 403);
    }
    
    try {
        // 1. 取得伺服器資訊
        $server_query = $pdo->prepare("SELECT * FROM servers WHERE auton = :server_id");
        $server_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $server_query->execute();
        $server = $server_query->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            api_error('伺服器資訊不存在', 404);
        }
        
        // 2. 取得派獎設定
        $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
        $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $settings_query->execute();
        $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
        
        // 3. 測試連線
        $connection_test = connect_to_game_server($server);
        
        $result = [
            'server_info' => [
                'id' => $server['auton'],
                'name' => $server['names'],
                'host' => $server['db_ip'],
                'port' => $server['db_port'] ?: 3306,
                'database' => $server['db_name'],
                'user' => $server['db_user']
            ],
            'connection' => [
                'success' => $connection_test['success'],
                'error' => isset($connection_test['error']) ? $connection_test['error'] : null
            ],
            'settings' => [
                'configured' => !empty($settings),
                'table_name' => isset($settings['table_name']) ? $settings['table_name'] : null,
                'account_field' => isset($settings['account_field']) ? $settings['account_field'] : null,
                'item_field' => isset($settings['item_field']) ? $settings['item_field'] : null,
                'item_name_field' => isset($settings['item_name_field']) ? $settings['item_name_field'] : null,
                'quantity_field' => isset($settings['quantity_field']) ? $settings['quantity_field'] : null
            ]
        ];
        
        if ($connection_test['success']) {
            $game_db = $connection_test['pdo'];
            
            // 4. 如果設定完整，檢查資料表和欄位
            if (!empty($settings['table_name']) && !empty($settings['account_field'])) {
                $table_check = check_table_exists($game_db, $settings['table_name']);
                $result['table_check'] = $table_check;
                
                if ($table_check['success']) {
                    $required_fields = [
                        $settings['account_field'],
                        $settings['item_field'] ?: 'item_id',
                        $settings['quantity_field'] ?: 'quantity'
                    ];
                    
                    // 檢查是否需要驗證道具名稱欄位
                    $need_item_name_field = false;
                    if (!empty($items)) {
                        // 如果有傳入道具清單，檢查是否有任何道具包含名稱
                        foreach ($items as $item) {
                            if (!empty($item['name'])) {
                                $need_item_name_field = true;
                                break;
                            }
                        }
                    } else {
                        // 如果沒有傳入道具清單，且有設定道具名稱欄位，就檢測
                        $need_item_name_field = !empty($settings['item_name_field']);
                    }
                    
                    // 如果需要檢測道具名稱欄位且有設定
                    if ($need_item_name_field && !empty($settings['item_name_field'])) {
                        $required_fields[] = $settings['item_name_field'];
                    }
                    
                    $fields_check = check_fields_exist($game_db, $settings['table_name'], $required_fields);
                    $result['fields_check'] = $fields_check;

                    // 6. 生成測試用的 SQL 語句 (無論欄位檢查是否成功)
                    $result['test_sqls'] = generate_test_sqls($pdo, $server_id, $settings, $fields_check['success'], $items, $game_account);
                } else {
                    $result['fields_check'] = ['success' => false, 'error' => '無法檢查欄位，因為資料表不存在'];
                    // 即使表格不存在，也提供基本的 SQL 生成
                    $result['test_sqls'] = generate_test_sqls($pdo, $server_id, $settings, false, $items, $game_account);
                }
            } else {
                $result['table_check'] = ['success' => false, 'error' => '派獎設定不完整'];
                $result['fields_check'] = ['success' => false, 'error' => '派獎設定不完整'];
                // 即使設定不完整，也提供基本的 SQL 範例
                $result['test_sqls'] = generate_test_sqls($pdo, $server_id, $settings, false, $items, $game_account);
            }
            
            // 5. 取得資料庫資訊 (非快速測試模式才執行)
            if (!$quick_test) {
                try {
                    $version_query = $game_db->query("SELECT VERSION() as version");
                    $version = $version_query->fetch(PDO::FETCH_ASSOC);
                    $result['database_info'] = [
                        'version' => $version['version'],
                        'charset' => 'utf8mb4'
                    ];
                } catch (Exception $e) {
                    $result['database_info'] = ['error' => $e->getMessage()];
                }
            }
            // 快速模式下，立即關閉遊戲資料庫連線以節省資源
            if ($quick_test && isset($game_db)) {
                $game_db = null;
            }
        }
        
        api_success($result, 'Game server connection test completed');
        
    } catch (Exception $e) {
        api_error('Connection test failed: ' . $e->getMessage(), 500);
    }
}

/**
 * 執行派獎到遊戲伺服器
 */
function execute_gift_to_game_server($pdo, $server_id, $game_account, $items, $log_id) {
    try {
        // 1. 取得伺服器資訊
        $server_query = $pdo->prepare("SELECT * FROM servers WHERE auton = :server_id");
        $server_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $server_query->execute();
        $server = $server_query->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            return ['success' => false, 'error' => '伺服器資訊不存在'];
        }
        
        // 2. 取得派獎設定
        $settings_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
        $settings_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $settings_query->execute();
        $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings || empty($settings['table_name']) || empty($settings['account_field'])) {
            return ['success' => false, 'error' => '派獎設定不完整，請先設定資料表名稱和帳號欄位'];
        }
        
        // 3. 連接遊戲伺服器資料庫
        $game_pdo = connect_to_game_server($server);
        if (!$game_pdo['success']) {
            return ['success' => false, 'error' => '無法連接遊戲伺服器: ' . $game_pdo['error']];
        }
        
        $game_db = $game_pdo['pdo'];
        
        // 4. 檢查資料表是否存在
        $table_name = $settings['table_name'];
        $table_check = check_table_exists($game_db, $table_name);
        if (!$table_check['success']) {
            return ['success' => false, 'error' => $table_check['error']];
        }
        
        // 5. 檢查必要欄位是否存在
        $required_fields = [
            $settings['account_field'],
            $settings['item_field'] ?: 'item_id',
            $settings['quantity_field'] ?: 'quantity'
        ];
        
        // 如果有設定道具名稱欄位，也要檢測
        if (!empty($settings['item_name_field'])) {
            $required_fields[] = $settings['item_name_field'];
        }
        
        $fields_check = check_fields_exist($game_db, $table_name, $required_fields);
        if (!$fields_check['success']) {
            return ['success' => false, 'error' => $fields_check['error']];
        }
        
        // 6. 取得動態欄位
        $dynamic_fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
        $dynamic_fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $dynamic_fields_query->execute();
        $dynamic_fields = $dynamic_fields_query->fetchAll(PDO::FETCH_ASSOC);
        
        // 7. 執行物品派發
        $game_db->beginTransaction();
        
        try {
            $inserted_count = 0;
            
            foreach ($items as $item) {
                $insert_result = insert_gift_item($game_db, $settings, $dynamic_fields, $game_account, $item);
                if ($insert_result['success']) {
                    $inserted_count++;
                } else {
                    $game_db->rollback();
                    return ['success' => false, 'error' => '物品派發失敗: ' . $insert_result['error']];
                }
            }
            
            $game_db->commit();

            // 8. 驗證資料是否正確寫入
            $verification_result = verify_gift_insertion($game_db, $settings, $game_account, $items);
            if (!$verification_result['success']) {
                return ['success' => false, 'error' => '資料驗證失敗: ' . $verification_result['error']];
            }

            return [
                'success' => true,
                'message' => "成功派發 {$inserted_count} 個物品到遊戲伺服器",
                'inserted_count' => $inserted_count,
                'verification' => $verification_result['data']
            ];
            
        } catch (Exception $e) {
            $game_db->rollback();
            return ['success' => false, 'error' => '執行派獎時發生錯誤: ' . $e->getMessage()];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => '派獎處理錯誤: ' . $e->getMessage()];
    }
}

/**
 * 連接遊戲伺服器資料庫
 */
function connect_to_game_server($server) {
    try {
        $host = $server['db_ip'];
        $port = $server['db_port'] ?: 3306;
        $dbname = $server['db_name'];
        $user = $server['db_user'];
        $password = $server['db_pass'];
        
        if (empty($host) || empty($dbname) || empty($user)) {
            return ['success' => false, 'error' => '遊戲伺服器資料庫設定不完整'];
        }
        
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5, // 5秒連線超時
        ];
        
        $pdo = new PDO($dsn, $user, $password, $options);
        
        // 測試連線
        $pdo->query("SELECT 1");
        
        return ['success' => true, 'pdo' => $pdo];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => '資料庫連線失敗: ' . $e->getMessage()];
    }
}

/**
 * 檢查資料表是否存在
 */
function check_table_exists($pdo, $table_name) {
    try {
        $query = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
        $query->execute();
        
        if ($query->rowCount() === 0) {
            return ['success' => false, 'error' => "資料表 '{$table_name}' 不存在"];
        }
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => '檢查資料表時發生錯誤: ' . $e->getMessage()];
    }
}

/**
 * 檢查欄位是否存在
 */
function check_fields_exist($pdo, $table_name, $required_fields) {
    try {
        $query = $pdo->prepare("DESCRIBE `{$table_name}`");
        $query->execute();
        $existing_fields = $query->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!in_array($field, $existing_fields)) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            return [
                'success' => false, 
                'error' => "資料表 '{$table_name}' 缺少必要欄位: " . implode(', ', $missing_fields)
            ];
        }
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => '檢查欄位時發生錯誤: ' . $e->getMessage()];
    }
}

/**
 * 插入單個物品到遊戲資料庫
 */
function insert_gift_item($pdo, $settings, $dynamic_fields, $game_account, $item) {
    try {
        $table_name = $settings['table_name'];
        $account_field = $settings['account_field'];
        $item_field = $settings['item_field'] ?: 'item_id';
        $item_name_field = $settings['item_name_field']; // 新增：道具名稱欄位
        $quantity_field = $settings['quantity_field'] ?: 'quantity';

        // 建構 SQL - 與測試連線邏輯一致
        $fields = ["`{$account_field}`", "`{$item_field}`", "`{$quantity_field}`"];
        $values = [':game_account', ':item_code', ':quantity'];
        $params = [
            ':game_account' => $game_account,
            ':item_code' => $item['itemCode'], // 修正：使用正確的參數名稱
            ':quantity' => $item['quantity']
        ];

        // 如果有設定道具名稱欄位，加入道具名稱（與測試SQL一致）
        if (!empty($item_name_field) && !empty($item['itemName'])) {
            $fields[] = "`{$item_name_field}`";
            $values[] = ':item_name';
            $params[':item_name'] = $item['itemName'];
        }

        // 加入動態欄位
        foreach ($dynamic_fields as $field) {
            $fields[] = "`{$field['field_name']}`";
            $values[] = ':' . $field['field_name'];
            $params[':' . $field['field_name']] = $field['field_value'];
        }

        // 加入時間戳記（與測試SQL一致）
        // $fields[] = '`created_at`';
        // $values[] = 'NOW()';

        $sql = "INSERT INTO `{$table_name}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";

        $query = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value, PDO::PARAM_STR);
        }
        $query->execute();

        return ['success' => true, 'insert_id' => $pdo->lastInsertId()];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 驗證物品是否正確寫入
 */
function verify_gift_insertion($pdo, $settings, $game_account, $items) {
    try {
        $table_name = $settings['table_name'];
        $account_field = $settings['account_field'];
        $item_field = $settings['item_field'] ?: 'item_id';
        $quantity_field = $settings['quantity_field'] ?: 'quantity';

        $verification_data = [];

        foreach ($items as $item) {
            $check_query = $pdo->prepare("
                SELECT COUNT(*) as count, SUM(`{$quantity_field}`) as total_quantity
                FROM `{$table_name}`
                WHERE `{$account_field}` = :game_account
                AND `{$item_field}` = :item_name
            ");
            $check_query->bindValue(':game_account', $game_account, PDO::PARAM_STR);
            $check_query->bindValue(':item_name', $item['itemCode'], PDO::PARAM_STR);
            $check_query->execute();

            $result = $check_query->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] == 0) {
                return [
                    'success' => false,
                    'error' => "物品 '{$item['itemCode']}' 沒有找到對應的資料記錄"
                ];
            }

            $verification_data[] = [
                'item_name' => $item['itemCode'],
                'expected_quantity' => $item['quantity'],
                'actual_records' => $result['count'],
                'total_quantity' => $result['total_quantity']
            ];
        }

        return ['success' => true, 'data' => $verification_data];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => '驗證資料時發生錯誤: ' . $e->getMessage()];
    }
}

/**
 * 生成測試用的 SQL 語句
 */
function generate_test_sqls($pdo, $server_id, $settings, $fields_valid = true, $actual_items = null, $actual_game_account = null) {
    try {
        // 檢查基本設定是否存在
        if (empty($settings) || empty($settings['table_name']) || empty($settings['account_field'])) {
            return [
                'error' => '派獎設定不完整，無法生成測試 SQL',
                'basic_example' => [
                    'description' => '基本 SQL 範例 (需要先設定派獎資訊)',
                    'example_sql' => [
                        'INSERT INTO `your_table` (`account`, `item_code`, `quantity`, `created_at`) VALUES (\'test_user\', \'ITEM_001\', 10, NOW());',
                        'INSERT INTO `your_table` (`account`, `item_code`, `quantity`, `created_at`) VALUES (\'test_user\', \'GOLD\', 1000, NOW());'
                    ]
                ],
                'steps' => [
                    '1. 請先在伺服器設定頁面填寫「資料表名稱」和「帳號欄位」',
                    '2. 填寫「道具編號欄位」和「數量欄位」',
                    '3. 如需要，可填寫「道具名稱欄位」',
                    '4. 設定完成後重新測試連線即可獲得完整的測試 SQL'
                ],
                'test_cases' => [],
                'verification_sqls' => []
            ];
        }

        $table_name = $settings['table_name'];
        $account_field = $settings['account_field'];
        $item_field = $settings['item_field'] ?: 'item_id';
        $item_name_field = $settings['item_name_field'];
        $quantity_field = $settings['quantity_field'] ?: 'quantity';

        // 取得動態欄位
        $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
        $fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $fields_query->execute();
        $dynamic_fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);

        $test_cases = [];

        // 優先使用實際要送出的道具數據
        if (!empty($actual_items) && is_array($actual_items)) {
            // 使用實際的道具數據生成SQL
            $actual_items_formatted = [];
            foreach ($actual_items as $item) {
                $actual_items_formatted[] = [
                    'item_code' => isset($item['itemCode']) ? $item['itemCode'] : (isset($item['item_code']) ? $item['item_code'] : ''),
                    'item_name' => isset($item['itemName']) ? $item['itemName'] : (isset($item['item_name']) ? $item['item_name'] : ''),
                    'quantity' => isset($item['quantity']) ? $item['quantity'] : 1
                ];
            }

            // 決定使用的帳號
            $display_account = !empty($actual_game_account) ? $actual_game_account : '[請輸入遊戲帳號]';

            $test_cases[] = [
                'description' => '即將送出的道具 SQL',
                'test_account' => $display_account,
                'items' => $actual_items_formatted,
                'is_actual_data' => true
            ];
        } else {
            // 沒有實際道具時，提供基本範例
            $test_cases = [
                [
                    'description' => '範例: 基本道具派發',
                    'test_account' => 'test_user_001',
                    'items' => [
                        ['item_code' => 'ITEM_001', 'item_name' => '測試道具A', 'quantity' => 10],
                        ['item_code' => 'ITEM_002', 'item_name' => '測試道具B', 'quantity' => 5]
                    ],
                    'is_actual_data' => false
                ]
            ];
        }

        $sql_results = [];

        foreach ($test_cases as $case) {
            $case_sqls = [];

            foreach ($case['items'] as $item) {
                // 建構基本欄位
                $fields = ["`{$account_field}`", "`{$item_field}`", "`{$quantity_field}`"];
                $values = ["'{$case['test_account']}'", "'{$item['item_code']}'", $item['quantity']];

                // 如果有設定道具名稱欄位，加入道具名稱
                if (!empty($item_name_field) && !empty($item['item_name'])) {
                    $fields[] = "`{$item_name_field}`";
                    $values[] = "'{$item['item_name']}'";
                }

                // 加入動態欄位
                foreach ($dynamic_fields as $field) {
                    $fields[] = "`{$field['field_name']}`";
                    $values[] = "'{$field['field_value']}'";
                }

                // 加入時間戳記
                // $fields[] = "`created_at`";
                // $values[] = "NOW()";

                // 生成完整的 INSERT SQL
                $sql = "INSERT INTO `{$table_name}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");";

                $case_sqls[] = [
                    'item_info' => $item,
                    'sql' => $sql,
                    'description' => "為帳號 {$case['test_account']} 新增 {$item['item_name']} x{$item['quantity']}"
                ];
            }

            $sql_results[] = [
                'case_description' => $case['description'],
                'test_account' => $case['test_account'],
                'sqls' => $case_sqls,
                'total_sqls' => count($case_sqls)
            ];
        }

        // 生成檢查 SQL（根據是否有實際道具調整）
        $verification_sqls = [];

        if (!empty($actual_items) && is_array($actual_items)) {
            // 決定驗證SQL中使用的帳號
            $check_account = !empty($actual_game_account) ? $actual_game_account : '[您的遊戲帳號]';

            // 有實際道具時，生成相關的檢查SQL
            $verification_sqls = [
                "-- 檢查指定帳號的道具記錄",
                "SELECT `{$account_field}`, `{$item_field}`" .
                (!empty($item_name_field) ? ", `{$item_name_field}`" : "") .
                ", `{$quantity_field}` FROM `{$table_name}` WHERE `{$account_field}` = '{$check_account}' LIMIT 20;",
                "",
                "-- 檢查最近派發的道具記錄",
                "SELECT `{$account_field}`, `{$item_field}`" .
                (!empty($item_name_field) ? ", `{$item_name_field}`" : "") .
                ", `{$quantity_field}` FROM `{$table_name}` LIMIT 50;"
            ];
        } else {
            // 沒有實際道具時，生成通用檢查SQL
            $verification_sqls = [
                "-- 檢查特定帳號的所有道具",
                "SELECT * FROM `{$table_name}` WHERE `{$account_field}` = '[測試帳號]' LIMIT 10;",
                "",
                "-- 檢查最近的派發記錄",
                "SELECT `{$account_field}`, `{$item_field}`" .
                (!empty($item_name_field) ? ", `{$item_name_field}`" : "") .
                ", `{$quantity_field}` FROM `{$table_name}` LIMIT 20;"
            ];
        }

        $result = [
            'test_cases' => $sql_results,
            'verification_sqls' => $verification_sqls,
            'table_info' => [
                'table_name' => $table_name,
                'account_field' => $account_field,
                'item_field' => $item_field,
                'item_name_field' => $item_name_field,
                'quantity_field' => $quantity_field,
                'has_dynamic_fields' => !empty($dynamic_fields),
                'dynamic_fields_count' => count($dynamic_fields)
            ],
            'dynamic_fields' => $dynamic_fields,
            'summary' => [
                'total_test_cases' => count($sql_results),
                'total_test_sqls' => array_sum(array_column($sql_results, 'total_sqls')),
                'verification_sqls_count' => count(array_filter($verification_sqls, function($sql) {
                    return !empty(trim($sql)) && strpos(trim($sql), '--') !== 0;
                }))
            ]
        ];

        // 如果欄位檢查失敗，加入警告訊息
        if (!$fields_valid) {
            $result['warning'] = [
                'message' => '⚠️ 欄位檢查失敗，以下 SQL 可能無法正常執行',
                'suggestions' => [
                    '請檢查資料表是否存在',
                    '請確認欄位名稱是否正確',
                    '執行 SQL 前請先確認資料表結構',
                    '建議先手動檢查目標資料庫的表格結構'
                ]
            ];
        }

        return $result;

    } catch (Exception $e) {
        return [
            'error' => 'Failed to generate test SQLs: ' . $e->getMessage(),
            'test_cases' => [],
            'verification_sqls' => []
        ];
    }
}

?>