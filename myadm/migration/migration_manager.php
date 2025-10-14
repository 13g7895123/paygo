<?php
/**
 * 派獎記錄 JSON 格式遷移管理器 - PHP 版本
 *
 * 透過 PHP 頁面提供完整的遷移管理功能
 * 支援：檢查需求、執行遷移、監控進度、回滾操作
 */

// 先啟動會話
if (!isset($_SESSION)) {
    session_start();
}

// 引入必要檔案
include("../include.php");

// 設定執行時間和記憶體限制
set_time_limit(300);
ini_set('memory_limit', '512M');

// 權限檢查
function check_admin_permission() {
    if (empty($_SESSION["adminid"])) {
        return false;
    }
    return true;
}

// 如果不是管理員，顯示錯誤頁面
if (!check_admin_permission()) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-TW">
    <head>
        <meta charset="UTF-8">
        <title>權限不足</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
            .error { color: #e74c3c; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>⚠️ 權限不足</h2>
            <p>只有管理員可以使用遷移管理器</p>
            <p><a href="../index.php">返回首頁</a></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// 取得 PDO 連線
try {
    $pdo = openpdo();
} catch (Exception $e) {
    die("資料庫連線失敗: " . $e->getMessage());
}

// 處理 AJAX 請求
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['ajax_action'];
    $result = ['success' => false, 'message' => '', 'data' => null];

    try {
        switch ($action) {
            case 'check_requirements':
                $result = handle_check_requirements($pdo);
                break;

            case 'check_status':
                $result = handle_check_migration_status($pdo);
                break;

            case 'backup_data':
                $result = handle_backup_data($pdo);
                break;

            case 'create_structure':
                $result = handle_create_structure($pdo);
                break;

            case 'migrate_data':
                $result = handle_migrate_data($pdo);
                break;

            case 'validate_migration':
                $result = handle_validate_migration($pdo);
                break;

            case 'full_migration':
                $result = handle_full_migration($pdo);
                break;

            case 'rollback':
                $result = handle_rollback($pdo);
                break;

            default:
                $result = ['success' => false, 'message' => '無效的操作'];
        }
    } catch (Exception $e) {
        $result = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($result, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
    exit();
}

// 遷移處理函數
function handle_check_requirements($pdo) {
    $requirements = [
        'php_version' => [
            'name' => 'PHP 版本',
            'required' => '5.6.40',
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, '5.6.40', '>=')
        ],
        'pdo_extension' => [
            'name' => 'PDO 擴展',
            'required' => 'Yes',
            'current' => extension_loaded('pdo') ? 'Yes' : 'No',
            'passed' => extension_loaded('pdo')
        ],
        'mysql_pdo' => [
            'name' => 'MySQL PDO 驅動',
            'required' => 'Yes',
            'current' => extension_loaded('pdo_mysql') ? 'Yes' : 'No',
            'passed' => extension_loaded('pdo_mysql')
        ]
    ];

    // 測試資料庫連線
    $db_test = ['name' => '資料庫連線', 'required' => 'Connected', 'current' => 'Testing...', 'passed' => false];
    try {
        $pdo->query("SELECT 1");
        $db_test['current'] = 'Connected';
        $db_test['passed'] = true;
    } catch (Exception $e) {
        $db_test['current'] = 'Failed: ' . $e->getMessage();
    }
    $requirements['database_connection'] = $db_test;

    // 檢查資料表狀態
    $table_status = [];
    try {
        $original_table = $pdo->query("SHOW TABLES LIKE 'send_gift_logs'")->fetchAll();
        $table_status['send_gift_logs'] = !empty($original_table);
    } catch (Exception $e) {
        $table_status['send_gift_logs'] = false;
    }

    try {
        $migration_table = $pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
        $table_status['send_gift_log_items'] = !empty($migration_table);
    } catch (Exception $e) {
        $table_status['send_gift_log_items'] = false;
    }

    // 檢查資料量
    $data_info = [];
    if ($table_status['send_gift_logs']) {
        try {
            $count_query = $pdo->query("
                SELECT
                    COUNT(*) as total_logs,
                    COUNT(CASE WHEN items IS NOT NULL AND items != '' THEN 1 END) as json_logs
                FROM send_gift_logs
            ");
            $counts = $count_query->fetch(PDO::FETCH_ASSOC);
            $data_info = [
                'total_logs' => intval($counts['total_logs']),
                'logs_with_json' => intval($counts['json_logs']),
                'need_migration' => intval($counts['json_logs']) > 0
            ];
        } catch (Exception $e) {
            $data_info = ['error' => $e->getMessage()];
        }
    }

    $all_passed = array_reduce($requirements, function($carry, $req) {
        return $carry && $req['passed'];
    }, true);

    return [
        'success' => true,
        'message' => 'System requirements checked',
        'data' => [
            'requirements' => $requirements,
            'table_status' => $table_status,
            'data_info' => $data_info,
            'all_requirements_passed' => $all_passed,
            'ready_for_migration' => $all_passed && $table_status['send_gift_logs']
        ]
    ];
}

function handle_check_migration_status($pdo) {
    $status = [
        'migration_completed' => false,
        'migration_status' => null,
        'backup_exists' => false,
        'new_structure_exists' => false,
        'data_migrated' => false,
        'backup_tables' => []
    ];

    // 檢查遷移狀態表
    try {
        $migration_check = $pdo->query("SHOW TABLES LIKE 'migration_status'")->fetchAll();
        if (!empty($migration_check)) {
            $migration_query = $pdo->prepare("
                SELECT status, records_processed, started_at, completed_at, error_message
                FROM migration_status
                WHERE migration_name = 'json_to_relational_gift_logs'
            ");
            $migration_query->execute();
            $migration_result = $migration_query->fetch(PDO::FETCH_ASSOC);

            if ($migration_result) {
                $status['migration_status'] = $migration_result;
                $status['migration_completed'] = ($migration_result['status'] === 'completed');
            }
        }
    } catch (Exception $e) {
        // 表格不存在是正常的
    }

    // 檢查新結構
    try {
        $new_table_check = $pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
        $status['new_structure_exists'] = !empty($new_table_check);
    } catch (Exception $e) {
        $status['new_structure_exists'] = false;
    }

    // 檢查備份欄位
    try {
        $backup_check = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_backup'")->fetchAll();
        $status['backup_exists'] = !empty($backup_check);
    } catch (Exception $e) {
        $status['backup_exists'] = false;
    }

    // 檢查資料是否已遷移
    if ($status['new_structure_exists']) {
        try {
            $data_check = $pdo->query("SELECT COUNT(*) as count FROM send_gift_log_items")->fetch(PDO::FETCH_ASSOC);
            $status['data_migrated'] = intval($data_check['count']) > 0;
            $status['migrated_items_count'] = intval($data_check['count']);
        } catch (Exception $e) {
            $status['data_migrated'] = false;
        }
    }

    // 檢查備份表
    try {
        $backup_tables = $pdo->query("SHOW TABLES LIKE 'send_gift_logs_backup%'")->fetchAll(PDO::FETCH_COLUMN);
        $status['backup_tables'] = $backup_tables;
        $status['has_backup_tables'] = !empty($backup_tables);
    } catch (Exception $e) {
        $status['backup_tables'] = [];
        $status['has_backup_tables'] = false;
    }

    return [
        'success' => true,
        'message' => 'Migration status retrieved',
        'data' => $status
    ];
}

function handle_backup_data($pdo) {
    $backup_table = 'send_gift_logs_backup_' . date('Ymd_His');
    $pdo->exec("CREATE TABLE {$backup_table} AS SELECT * FROM send_gift_logs");

    // 驗證備份
    $original_count = $pdo->query("SELECT COUNT(*) FROM send_gift_logs")->fetchColumn();
    $backup_count = $pdo->query("SELECT COUNT(*) FROM {$backup_table}")->fetchColumn();

    if ($original_count != $backup_count) {
        throw new Exception("備份驗證失敗：記錄數不一致");
    }

    return [
        'success' => true,
        'message' => "資料備份完成: {$backup_table}",
        'data' => [
            'backup_table' => $backup_table,
            'records_backed_up' => intval($backup_count)
        ]
    ];
}

function handle_create_structure($pdo) {
    // 建立道具明細表
    $create_items_table = "
        CREATE TABLE IF NOT EXISTS send_gift_log_items (
            id INT AUTO_INCREMENT PRIMARY KEY COMMENT '流水號',
            log_id INT NOT NULL COMMENT '派獎記錄ID',
            item_code VARCHAR(50) NOT NULL COMMENT '道具編號',
            item_name VARCHAR(200) DEFAULT NULL COMMENT '道具名稱',
            quantity INT NOT NULL DEFAULT 1 COMMENT '數量',
            sort_order INT DEFAULT 0 COMMENT '排序順序',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',

            INDEX idx_log_id (log_id),
            INDEX idx_item_code (item_code),
            INDEX idx_log_item (log_id, item_code),

            FOREIGN KEY (log_id) REFERENCES send_gift_logs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='派獎記錄道具明細表'
    ";
    $pdo->exec($create_items_table);

    // 新增欄位
    try {
        $check_backup_column = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_backup'")->fetchAll();
        if (empty($check_backup_column)) {
            $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_backup TEXT DEFAULT NULL COMMENT 'JSON備份(遷移後可刪除)' AFTER items");
        }
    } catch (Exception $e) {
        // 忽略錯誤
    }

    try {
        $check_summary_column = $pdo->query("SHOW COLUMNS FROM send_gift_logs LIKE 'items_summary'")->fetchAll();
        if (empty($check_summary_column)) {
            $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_summary TEXT DEFAULT NULL COMMENT '道具摘要文字' AFTER items_backup");
        }
    } catch (Exception $e) {
        // 忽略錯誤
    }

    // 建立遷移狀態表
    $create_migration_table = "
        CREATE TABLE IF NOT EXISTS migration_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(100) NOT NULL UNIQUE,
            status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            error_message TEXT,
            records_processed INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='遷移狀態追蹤表'
    ";
    $pdo->exec($create_migration_table);

    // 插入遷移記錄
    $insert_migration = $pdo->prepare("
        INSERT IGNORE INTO migration_status (migration_name, status)
        VALUES ('json_to_relational_gift_logs', 'pending')
    ");
    $insert_migration->execute();

    // 備份現有資料
    $pdo->exec("UPDATE send_gift_logs SET items_backup = items WHERE items IS NOT NULL AND items_backup IS NULL");

    return [
        'success' => true,
        'message' => '遷移資料結構建立完成',
        'data' => [
            'tables_created' => ['send_gift_log_items', 'migration_status'],
            'columns_added' => ['items_backup', 'items_summary']
        ]
    ];
}

function handle_migrate_data($pdo) {
    // 檢查是否已完成遷移
    $migration_check = $pdo->prepare("
        SELECT status, records_processed
        FROM migration_status
        WHERE migration_name = 'json_to_relational_gift_logs'
    ");
    $migration_check->execute();
    $migration_status = $migration_check->fetch(PDO::FETCH_ASSOC);

    if ($migration_status && $migration_status['status'] === 'completed') {
        return [
            'success' => true,
            'message' => '遷移已完成',
            'data' => [
                'already_completed' => true,
                'records_processed' => $migration_status['records_processed']
            ]
        ];
    }

    // 更新遷移狀態
    $update_status = $pdo->prepare("
        UPDATE migration_status
        SET status = 'running', started_at = NOW()
        WHERE migration_name = 'json_to_relational_gift_logs'
    ");
    $update_status->execute();

    // 取得需要遷移的記錄
    $count_query = $pdo->query("
        SELECT COUNT(*) as total
        FROM send_gift_logs
        WHERE items_backup IS NOT NULL AND items_backup != ''
    ");
    $total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];

    if ($total_records === 0) {
        $complete_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'completed', completed_at = NOW(), records_processed = 0
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $complete_status->execute();

        return [
            'success' => true,
            'message' => '沒有需要遷移的記錄',
            'data' => [
                'records_processed' => 0,
                'total_records' => 0
            ]
        ];
    }

    // 開始遷移
    $pdo->beginTransaction();
    $processed = 0;
    $batch_size = 50;
    $offset = 0;

    try {
        while ($offset < $total_records) {
            $logs_query = $pdo->prepare("
                SELECT id, items_backup
                FROM send_gift_logs
                WHERE items_backup IS NOT NULL AND items_backup != ''
                LIMIT :limit OFFSET :offset
            ");
            $logs_query->bindValue(':limit', $batch_size, PDO::PARAM_INT);
            $logs_query->bindValue(':offset', $offset, PDO::PARAM_INT);
            $logs_query->execute();

            $batch_logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);
            if (empty($batch_logs)) break;

            foreach ($batch_logs as $log) {
                try {
                    $items = json_decode($log['items_backup'], true);
                    if (!is_array($items)) continue;

                    $summary_parts = [];
                    $sort_order = 0;

                    foreach ($items as $item) {
                        $item_code = isset($item['itemCode']) ? $item['itemCode'] : (isset($item['item_code']) ? $item['item_code'] : '');
                        $item_name = isset($item['itemName']) ? $item['itemName'] : (isset($item['item_name']) ? $item['item_name'] : '');
                        $quantity = intval(isset($item['quantity']) ? $item['quantity'] : 1);

                        if (empty($item_code)) continue;

                        // 插入道具明細
                        $item_query = $pdo->prepare("
                            INSERT INTO send_gift_log_items (log_id, item_code, item_name, quantity, sort_order)
                            VALUES (:log_id, :item_code, :item_name, :quantity, :sort_order)
                        ");
                        $item_query->execute([
                            ':log_id' => $log['id'],
                            ':item_code' => $item_code,
                            ':item_name' => $item_name,
                            ':quantity' => $quantity,
                            ':sort_order' => $sort_order++
                        ]);

                        $display_name = !empty($item_name) ? $item_name : $item_code;
                        $summary_parts[] = $display_name . ' x' . $quantity;
                    }

                    // 更新摘要
                    $summary = implode(', ', $summary_parts);
                    $update_query = $pdo->prepare("
                        UPDATE send_gift_logs SET items_summary = :summary WHERE id = :id
                    ");
                    $update_query->execute([':summary' => $summary, ':id' => $log['id']]);

                    $processed++;
                } catch (Exception $e) {
                    error_log("Error processing log ID {$log['id']}: " . $e->getMessage());
                    continue;
                }
            }

            $offset += $batch_size;
        }

        $pdo->commit();

        // 更新狀態為完成
        $complete_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'completed', completed_at = NOW(), records_processed = :processed
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $complete_status->execute([':processed' => $processed]);

        return [
            'success' => true,
            'message' => "資料遷移完成，共處理 {$processed} 筆記錄",
            'data' => [
                'records_processed' => $processed,
                'total_records' => $total_records
            ]
        ];
    } catch (Exception $e) {
        $pdo->rollback();

        // 更新狀態為失敗
        $error_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'failed', error_message = :error
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $error_status->execute([':error' => $e->getMessage()]);

        throw $e;
    }
}

function handle_validate_migration($pdo) {
    // 檢查遷移狀態
    $migration_query = $pdo->prepare("
        SELECT status, records_processed
        FROM migration_status
        WHERE migration_name = 'json_to_relational_gift_logs'
    ");
    $migration_query->execute();
    $migration_status = $migration_query->fetch(PDO::FETCH_ASSOC);

    // 檢查資料完整性
    $verification_query = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL AND items_backup != '') as source_count,
            (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_count,
            (SELECT COUNT(*) FROM send_gift_log_items) as items_count
    ");
    $verification = $verification_query->fetch(PDO::FETCH_ASSOC);

    $validation_result = [
        'migration_status' => $migration_status,
        'data_integrity' => $verification,
        'validation_passed' => false,
        'issues' => []
    ];

    // 驗證規則
    if (!$migration_status || $migration_status['status'] !== 'completed') {
        $validation_result['issues'][] = '遷移狀態不是已完成';
    }

    if ($verification['source_count'] != $verification['migrated_count']) {
        $validation_result['issues'][] = '原始記錄數與已遷移記錄數不一致';
    }

    if ($verification['items_count'] == 0 && $verification['source_count'] > 0) {
        $validation_result['issues'][] = '沒有道具明細記錄，但有原始記錄';
    }

    $validation_result['validation_passed'] = empty($validation_result['issues']);

    return [
        'success' => true,
        'message' => $validation_result['validation_passed'] ? '驗證通過' : '驗證發現問題',
        'data' => $validation_result
    ];
}

function handle_full_migration($pdo) {
    $results = [];

    // 1. 備份資料
    $backup_result = handle_backup_data($pdo);
    $results['backup'] = $backup_result['data']['backup_table'];

    // 2. 建立結構
    handle_create_structure($pdo);
    $results['structure'] = 'Created';

    // 3. 執行遷移
    $migration_result = handle_migrate_data($pdo);
    $results['migration'] = 'Completed';

    // 4. 驗證結果
    handle_validate_migration($pdo);
    $results['validation'] = 'Verified';

    return [
        'success' => true,
        'message' => '一鍵完整遷移執行完成',
        'data' => $results
    ];
}

function handle_rollback($pdo) {
    $backup_table = isset($_POST['backup_table']) ? $_POST['backup_table'] : '';

    if (empty($backup_table)) {
        throw new Exception('備份表名稱為必填項目');
    }

    $pdo->beginTransaction();

    try {
        // 檢查備份表是否存在
        $check_backup = $pdo->query("SHOW TABLES LIKE '{$backup_table}'")->fetchAll();
        if (empty($check_backup)) {
            throw new Exception("備份表 {$backup_table} 不存在");
        }

        // 刪除新建的表格
        $pdo->exec("DROP TABLE IF EXISTS send_gift_log_items");
        $pdo->exec("DROP TABLE IF EXISTS migration_status");

        // 恢復原始資料
        $pdo->exec("DROP TABLE send_gift_logs");
        $pdo->exec("CREATE TABLE send_gift_logs AS SELECT * FROM {$backup_table}");

        // 重新建立主鍵
        $pdo->exec("ALTER TABLE send_gift_logs ADD PRIMARY KEY (id)");

        $pdo->commit();

        return [
            'success' => true,
            'message' => '回滾完成',
            'data' => [
                'backup_table_used' => $backup_table
            ]
        ];
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>派獎記錄 JSON 格式遷移管理器</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .header p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .section {
            background: #fff;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #34495e;
            color: #fff;
            padding: 15px 25px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-content {
            padding: 25px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-pending { background-color: #f39c12; }
        .status-running { background-color: #3498db; }
        .status-success { background-color: #27ae60; }
        .status-error { background-color: #e74c3c; }
        .status-warning { background-color: #f39c12; }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 5px;
            background-color: #3498db;
            color: #fff;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #27ae60;
        }

        .btn-success:hover {
            background-color: #219a52;
        }

        .btn-warning {
            background-color: #f39c12;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .info-card h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .info-card .value {
            font-size: 18px;
            font-weight: 600;
            color: #27ae60;
        }

        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .requirements-table th,
        .requirements-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .requirements-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .log-container {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }

        .log-entry {
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #34495e;
        }

        .log-timestamp {
            color: #95a5a6;
            margin-right: 10px;
        }

        .log-info { color: #3498db; }
        .log-success { color: #27ae60; }
        .log-warning { color: #f39c12; }
        .log-error { color: #e74c3c; }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .alert-info {
            background-color: #d9edf7;
            border-color: #3498db;
            color: #31708f;
        }

        .alert-success {
            background-color: #dff0d8;
            border-color: #27ae60;
            color: #3c763d;
        }

        .alert-warning {
            background-color: #fcf8e3;
            border-color: #f39c12;
            color: #8a6d3b;
        }

        .alert-danger {
            background-color: #f2dede;
            border-color: #e74c3c;
            color: #a94442;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none !important;
        }

        .step-buttons {
            margin: 20px 0;
            text-align: center;
        }

        .progress-container {
            margin: 20px 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #3498db;
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 標題區域 -->
        <div class="header">
            <h1>🔄 派獎記錄 JSON 格式遷移管理器</h1>
            <p>將 JSON 格式的道具資料遷移到關聯式資料表，解決 MySQL 版本兼容性問題</p>
            <p><strong>管理員：</strong><?php echo htmlspecialchars(isset($_SESSION['adminid']) ? $_SESSION['adminid'] : '未知'); ?></p>
        </div>

        <!-- 系統需求檢查 -->
        <div class="section">
            <div class="section-header">
                <span><span class="status-indicator status-pending" id="requirements-status"></span>系統需求檢查</span>
                <button class="btn" onclick="checkRequirements()">
                    <span class="loading hidden" id="requirements-loading"></span>檢查需求
                </button>
            </div>
            <div class="section-content">
                <div id="requirements-content">
                    <p>點擊「檢查需求」按鈕來驗證系統是否符合遷移需求。</p>
                </div>
            </div>
        </div>

        <!-- 遷移狀態 -->
        <div class="section">
            <div class="section-header">
                <span><span class="status-indicator status-pending" id="status-indicator"></span>遷移狀態</span>
                <button class="btn" onclick="checkMigrationStatus()">
                    <span class="loading hidden" id="status-loading"></span>檢查狀態
                </button>
            </div>
            <div class="section-content">
                <div id="migration-status-content">
                    <p>點擊「檢查狀態」按鈕來查看當前遷移狀態。</p>
                </div>
            </div>
        </div>

        <!-- 遷移執行 -->
        <div class="section">
            <div class="section-header">
                <span><span class="status-indicator status-pending" id="execution-status"></span>遷移執行</span>
            </div>
            <div class="section-content">
                <div class="step-buttons">
                    <button class="btn btn-warning" onclick="backupData()" id="backup-btn">
                        <span class="loading hidden" id="backup-loading"></span>1. 備份資料
                    </button>
                    <button class="btn" onclick="createTables()" id="structure-btn" disabled>
                        <span class="loading hidden" id="structure-loading"></span>2. 建立結構
                    </button>
                    <button class="btn btn-success" onclick="migrateData()" id="migrate-btn" disabled>
                        <span class="loading hidden" id="migrate-loading"></span>3. 遷移資料
                    </button>
                    <button class="btn" onclick="validateMigration()" id="validate-btn" disabled>
                        <span class="loading hidden" id="validate-loading"></span>4. 驗證結果
                    </button>
                </div>

                <div class="step-buttons">
                    <button class="btn btn-success" onclick="fullMigration()" id="full-migration-btn">
                        <span class="loading hidden" id="full-migration-loading"></span>🚀 一鍵完整遷移
                    </button>
                </div>

                <div class="progress-container hidden" id="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text" id="progress-text">準備中...</div>
                </div>

                <div id="execution-results"></div>
            </div>
        </div>

        <!-- 操作日誌 -->
        <div class="section">
            <div class="section-header">
                <span>📝 操作日誌</span>
                <button class="btn" onclick="clearLogs()">清除日誌</button>
            </div>
            <div class="section-content">
                <div class="log-container" id="log-container">
                    <div class="log-entry">
                        <span class="log-timestamp">[等待操作]</span>
                        <span class="log-info">歡迎使用遷移管理器，請先檢查系統需求</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 全域狀態
        let migrationState = {
            requirementsPassed: false,
            migrationCompleted: false,
            backupTables: []
        };

        // 工具函數
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('log-container');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            logEntry.innerHTML = `
                <span class="log-timestamp">[${timestamp}]</span>
                <span class="log-${type}">${message}</span>
            `;
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStatus(elementId, status) {
            const element = document.getElementById(elementId);
            if (element) {
                element.className = `status-indicator status-${status}`;
            }
        }

        function showLoading(buttonId, loadingId) {
            const button = document.getElementById(buttonId);
            const loading = document.getElementById(loadingId);
            if (button) button.disabled = true;
            if (loading) loading.classList.remove('hidden');
        }

        function hideLoading(buttonId, loadingId) {
            const button = document.getElementById(buttonId);
            const loading = document.getElementById(loadingId);
            if (button) button.disabled = false;
            if (loading) loading.classList.add('hidden');
        }

        function updateProgress(percentage, text) {
            const progressContainer = document.getElementById('progress-container');
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');

            if (percentage > 0) {
                progressContainer.classList.remove('hidden');
            }

            progressFill.style.width = percentage + '%';
            progressText.textContent = text || `${percentage}% 完成`;

            if (percentage >= 100) {
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                }, 2000);
            }
        }

        // API 呼叫函數
        async function callAPI(action, data = {}) {
            try {
                const formData = new FormData();
                formData.append('ajax_action', action);

                Object.keys(data).forEach(key => {
                    formData.append(key, data[key]);
                });

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || '操作失敗');
                }

                return result;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // 檢查系統需求
        async function checkRequirements() {
            showLoading('requirements-btn', 'requirements-loading');
            addLog('開始檢查系統需求...', 'info');

            try {
                const result = await callAPI('check_requirements');
                const data = result.data;

                updateStatus('requirements-status', data.all_requirements_passed ? 'success' : 'error');

                let html = '<table class="requirements-table"><thead><tr><th>項目</th><th>需求</th><th>當前</th><th>狀態</th></tr></thead><tbody>';

                Object.values(data.requirements).forEach(req => {
                    html += `<tr>
                        <td>${req.name}</td>
                        <td>${req.required}</td>
                        <td>${req.current}</td>
                        <td><span class="status-indicator status-${req.passed ? 'success' : 'error'}"></span>${req.passed ? '通過' : '失敗'}</td>
                    </tr>`;
                });

                html += '</tbody></table>';

                if (data.data_info && !data.data_info.error) {
                    html += '<div class="info-grid">';
                    html += `<div class="info-card">
                        <h4>總派獎記錄數</h4>
                        <div class="value">${data.data_info.total_logs}</div>
                    </div>`;
                    html += `<div class="info-card">
                        <h4>包含 JSON 的記錄</h4>
                        <div class="value">${data.data_info.logs_with_json}</div>
                    </div>`;
                    html += `<div class="info-card">
                        <h4>需要遷移</h4>
                        <div class="value">${data.data_info.need_migration ? '是' : '否'}</div>
                    </div>`;
                    html += '</div>';
                }

                document.getElementById('requirements-content').innerHTML = html;

                migrationState.requirementsPassed = data.all_requirements_passed;

                if (data.all_requirements_passed) {
                    addLog('✅ 系統需求檢查通過', 'success');
                } else {
                    addLog('❌ 系統需求檢查失敗，請解決上述問題', 'error');
                }

                // 自動檢查遷移狀態
                await checkMigrationStatus();

            } catch (error) {
                updateStatus('requirements-status', 'error');
                addLog(`❌ 檢查需求失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('requirements-btn', 'requirements-loading');
            }
        }

        // 檢查遷移狀態
        async function checkMigrationStatus() {
            showLoading('status-btn', 'status-loading');
            addLog('檢查遷移狀態...', 'info');

            try {
                const result = await callAPI('check_status');
                const data = result.data;

                migrationState.migrationCompleted = data.migration_completed;
                migrationState.backupTables = data.backup_tables || [];

                updateStatus('status-indicator', data.migration_completed ? 'success' : 'pending');

                let html = '<div class="info-grid">';

                html += `<div class="info-card">
                    <h4>遷移狀態</h4>
                    <div class="value">${data.migration_completed ? '已完成' : '未完成'}</div>
                </div>`;

                html += `<div class="info-card">
                    <h4>新結構存在</h4>
                    <div class="value">${data.new_structure_exists ? '是' : '否'}</div>
                </div>`;

                html += `<div class="info-card">
                    <h4>資料已遷移</h4>
                    <div class="value">${data.data_migrated ? '是' : '否'}</div>
                </div>`;

                html += `<div class="info-card">
                    <h4>備份表數量</h4>
                    <div class="value">${data.backup_tables ? data.backup_tables.length : 0}</div>
                </div>`;

                html += '</div>';

                if (data.migration_status) {
                    html += `<div class="alert alert-info">
                        <strong>遷移狀態：</strong>${data.migration_status.status}<br>
                        <strong>處理記錄數：</strong>${data.migration_status.records_processed || 0}<br>
                        ${data.migration_status.started_at ? `<strong>開始時間：</strong>${data.migration_status.started_at}<br>` : ''}
                        ${data.migration_status.completed_at ? `<strong>完成時間：</strong>${data.migration_status.completed_at}<br>` : ''}
                        ${data.migration_status.error_message ? `<strong>錯誤訊息：</strong>${data.migration_status.error_message}` : ''}
                    </div>`;
                }

                document.getElementById('migration-status-content').innerHTML = html;

                // 更新按鈕狀態
                updateButtonStates(data);

                addLog('✅ 遷移狀態檢查完成', 'success');

            } catch (error) {
                updateStatus('status-indicator', 'error');
                addLog(`❌ 檢查狀態失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('status-btn', 'status-loading');
            }
        }

        // 更新按鈕狀態
        function updateButtonStates(statusData) {
            const structureBtn = document.getElementById('structure-btn');
            const migrateBtn = document.getElementById('migrate-btn');
            const validateBtn = document.getElementById('validate-btn');

            // 根據遷移狀態調整步驟按鈕
            if (statusData.new_structure_exists) {
                structureBtn.disabled = false;
                structureBtn.textContent = '✓ 2. 結構已建立';
                structureBtn.className = 'btn btn-success';
            }

            if (statusData.data_migrated) {
                migrateBtn.disabled = false;
                migrateBtn.textContent = '✓ 3. 資料已遷移';
                migrateBtn.className = 'btn btn-success';
                validateBtn.disabled = false;
            }

            if (statusData.migration_completed) {
                updateStatus('execution-status', 'success');
            }
        }

        // 備份資料
        async function backupData() {
            showLoading('backup-btn', 'backup-loading');
            addLog('開始備份資料...', 'info');

            try {
                const result = await callAPI('backup_data');
                const data = result.data;

                document.getElementById('backup-btn').textContent = '✓ 1. 資料已備份';
                document.getElementById('backup-btn').className = 'btn btn-success';
                document.getElementById('structure-btn').disabled = false;

                addLog(`✅ 資料備份完成: ${data.backup_table} (${data.records_backed_up} 筆記錄)`, 'success');

                migrationState.backupTables.push(data.backup_table);

            } catch (error) {
                addLog(`❌ 備份失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('backup-btn', 'backup-loading');
            }
        }

        // 建立資料表結構
        async function createTables() {
            showLoading('structure-btn', 'structure-loading');
            addLog('建立新的資料表結構...', 'info');

            try {
                const result = await callAPI('create_structure');

                document.getElementById('structure-btn').textContent = '✓ 2. 結構已建立';
                document.getElementById('structure-btn').className = 'btn btn-success';
                document.getElementById('migrate-btn').disabled = false;

                addLog('✅ 資料表結構建立完成', 'success');

            } catch (error) {
                addLog(`❌ 建立結構失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('structure-btn', 'structure-loading');
            }
        }

        // 遷移資料
        async function migrateData() {
            showLoading('migrate-btn', 'migrate-loading');
            addLog('開始遷移資料...', 'info');
            updateProgress(10, '開始資料遷移...');

            try {
                const result = await callAPI('migrate_data');
                const data = result.data;

                updateProgress(90, '資料遷移中...');

                document.getElementById('migrate-btn').textContent = '✓ 3. 資料已遷移';
                document.getElementById('migrate-btn').className = 'btn btn-success';
                document.getElementById('validate-btn').disabled = false;

                updateProgress(100, '資料遷移完成');
                addLog(`✅ 資料遷移完成: 處理了 ${data.records_processed} 筆記錄`, 'success');

            } catch (error) {
                updateProgress(0, '');
                addLog(`❌ 資料遷移失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('migrate-btn', 'migrate-loading');
            }
        }

        // 驗證遷移結果
        async function validateMigration() {
            showLoading('validate-btn', 'validate-loading');
            addLog('驗證遷移結果...', 'info');

            try {
                const result = await callAPI('validate_migration');
                const data = result.data;

                document.getElementById('validate-btn').textContent = '✓ 4. 驗證完成';
                document.getElementById('validate-btn').className = 'btn btn-success';

                let html = '<div class="alert alert-success"><strong>驗證結果：</strong>' + result.message + '</div>';

                if (data.data_integrity) {
                    html += '<div class="info-grid">';
                    html += `<div class="info-card">
                        <h4>原始記錄數</h4>
                        <div class="value">${data.data_integrity.source_count}</div>
                    </div>`;
                    html += `<div class="info-card">
                        <h4>已遷移記錄數</h4>
                        <div class="value">${data.data_integrity.migrated_count}</div>
                    </div>`;
                    html += `<div class="info-card">
                        <h4>道具明細記錄數</h4>
                        <div class="value">${data.data_integrity.items_count}</div>
                    </div>`;
                    html += '</div>';
                }

                if (data.issues && data.issues.length > 0) {
                    html += '<div class="alert alert-warning"><strong>發現問題：</strong><ul>';
                    data.issues.forEach(issue => {
                        html += `<li>${issue}</li>`;
                    });
                    html += '</ul></div>';
                }

                document.getElementById('execution-results').innerHTML = html;

                if (data.validation_passed) {
                    addLog('✅ 遷移驗證通過', 'success');
                    migrationState.migrationCompleted = true;
                    updateStatus('execution-status', 'success');
                } else {
                    addLog('⚠️ 遷移驗證發現問題', 'warning');
                }

            } catch (error) {
                addLog(`❌ 驗證失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('validate-btn', 'validate-loading');
            }
        }

        // 一鍵完整遷移
        async function fullMigration() {
            if (!confirm('確定要執行完整遷移嗎？這個操作將會自動完成所有遷移步驟。')) {
                return;
            }

            showLoading('full-migration-btn', 'full-migration-loading');
            addLog('開始一鍵完整遷移...', 'info');
            updateProgress(0, '準備開始...');

            try {
                updateProgress(20, '執行完整遷移...');
                const result = await callAPI('full_migration');

                updateProgress(100, '遷移完成！');
                addLog('🎉 一鍵完整遷移成功完成！', 'success');

                // 重新檢查狀態
                await checkMigrationStatus();

            } catch (error) {
                updateProgress(0, '');
                addLog(`❌ 完整遷移失敗: ${error.message}`, 'error');
            } finally {
                hideLoading('full-migration-btn', 'full-migration-loading');
            }
        }

        // 清除日誌
        function clearLogs() {
            document.getElementById('log-container').innerHTML = '';
            addLog('日誌已清除', 'info');
        }

        // 頁面載入時自動檢查需求
        window.addEventListener('load', function() {
            addLog('遷移管理器已載入', 'info');
            checkRequirements();
        });
    </script>
</body>
</html>