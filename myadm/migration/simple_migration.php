<?php
/**
 * 簡化版遷移執行器
 *
 * 提供最基本的遷移功能，適合快速執行
 */

// 先啟動會話
if (!isset($_SESSION)) {
    session_start();
}

// 引入必要檔案
include("../include.php");

// 權限檢查
if (empty($_SESSION["adminid"])) {
    die('<h2 style="color:red;">⚠️ 權限不足：只有管理員可以執行遷移</h2>');
}

// 取得 PDO 連線
try {
    $pdo = openpdo();
} catch (Exception $e) {
    die("資料庫連線失敗: " . $e->getMessage());
}

// 處理操作
$action = $_GET['action'] ?? 'show';
$message = '';
$error = '';

if ($action === 'execute') {
    try {
        // 執行完整遷移
        $pdo->beginTransaction();

        // 1. 備份資料
        $backup_table = 'send_gift_logs_backup_' . date('Ymd_His');
        $pdo->exec("CREATE TABLE {$backup_table} AS SELECT * FROM send_gift_logs");

        // 2. 建立新結構
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

                FOREIGN KEY (log_id) REFERENCES send_gift_logs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='派獎記錄道具明細表'
        ";
        $pdo->exec($create_items_table);

        // 新增欄位
        try {
            $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_backup TEXT DEFAULT NULL COMMENT 'JSON備份' AFTER items");
        } catch (Exception $e) {
            // 欄位可能已存在
        }

        try {
            $pdo->exec("ALTER TABLE send_gift_logs ADD COLUMN items_summary TEXT DEFAULT NULL COMMENT '道具摘要' AFTER items_backup");
        } catch (Exception $e) {
            // 欄位可能已存在
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

        // 備份現有資料
        $pdo->exec("UPDATE send_gift_logs SET items_backup = items WHERE items IS NOT NULL AND items_backup IS NULL");

        // 3. 遷移資料
        $count_query = $pdo->query("
            SELECT COUNT(*) as total
            FROM send_gift_logs
            WHERE items IS NOT NULL AND items != ''
        ");
        $total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total_records > 0) {
            // 插入遷移狀態
            $pdo->exec("INSERT IGNORE INTO migration_status (migration_name, status, started_at) VALUES ('json_to_relational_gift_logs', 'running', NOW())");

            $processed = 0;
            $batch_size = 100;
            $offset = 0;

            while ($offset < $total_records) {
                $logs_query = $pdo->prepare("
                    SELECT id, items
                    FROM send_gift_logs
                    WHERE items IS NOT NULL AND items != ''
                    LIMIT :limit OFFSET :offset
                ");
                $logs_query->bindValue(':limit', $batch_size, PDO::PARAM_INT);
                $logs_query->bindValue(':offset', $offset, PDO::PARAM_INT);
                $logs_query->execute();

                $batch_logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);
                if (empty($batch_logs)) break;

                foreach ($batch_logs as $log) {
                    try {
                        $items = json_decode($log['items'], true);
                        if (!is_array($items)) continue;

                        $summary_parts = [];
                        $sort_order = 0;

                        foreach ($items as $item) {
                            $item_code = $item['itemCode'] ?? $item['item_code'] ?? '';
                            $item_name = $item['itemName'] ?? $item['item_name'] ?? '';
                            $quantity = intval($item['quantity'] ?? 1);

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

            // 更新狀態為完成
            $pdo->exec("UPDATE migration_status SET status = 'completed', completed_at = NOW(), records_processed = {$processed} WHERE migration_name = 'json_to_relational_gift_logs'");

            $message = "✅ 遷移完成！共處理 {$processed} 筆記錄，備份表：{$backup_table}";
        } else {
            $pdo->exec("INSERT IGNORE INTO migration_status (migration_name, status, completed_at, records_processed) VALUES ('json_to_relational_gift_logs', 'completed', NOW(), 0)");
            $message = "✅ 結構建立完成！沒有需要遷移的記錄，備份表：{$backup_table}";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollback();
        $error = "❌ 遷移失敗: " . $e->getMessage();
    }
}

// 檢查當前狀態
$status_info = [];
try {
    // 檢查資料量
    $count_query = $pdo->query("
        SELECT
            COUNT(*) as total_logs,
            COUNT(CASE WHEN items IS NOT NULL AND items != '' THEN 1 END) as json_logs
        FROM send_gift_logs
    ");
    $counts = $count_query->fetch(PDO::FETCH_ASSOC);
    $status_info['data'] = $counts;

    // 檢查是否已遷移
    try {
        $migration_check = $pdo->query("SHOW TABLES LIKE 'send_gift_log_items'")->fetchAll();
        $status_info['migrated'] = !empty($migration_check);

        if ($status_info['migrated']) {
            $items_count = $pdo->query("SELECT COUNT(*) FROM send_gift_log_items")->fetchColumn();
            $status_info['items_count'] = $items_count;
        }
    } catch (Exception $e) {
        $status_info['migrated'] = false;
    }

    // 檢查遷移狀態
    try {
        $migration_status = $pdo->query("SELECT * FROM migration_status WHERE migration_name = 'json_to_relational_gift_logs'")->fetch(PDO::FETCH_ASSOC);
        $status_info['migration_status'] = $migration_status;
    } catch (Exception $e) {
        $status_info['migration_status'] = null;
    }

} catch (Exception $e) {
    $error = "檢查狀態失敗: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡化版遷移執行器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }

        .status-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .status-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .status-value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
        }

        .status-label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 5px;
            transition: background-color 0.3s;
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

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .text-center {
            text-align: center;
        }

        .completed {
            color: #27ae60;
            font-weight: bold;
        }

        .pending {
            color: #f39c12;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 簡化版遷移執行器</h1>

        <div class="status-card">
            <h3>📊 當前狀態</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-value"><?php echo $status_info['data']['total_logs'] ?? 0; ?></div>
                    <div class="status-label">總派獎記錄數</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?php echo $status_info['data']['json_logs'] ?? 0; ?></div>
                    <div class="status-label">包含 JSON 的記錄</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?php echo isset($status_info['items_count']) ? $status_info['items_count'] : 0; ?></div>
                    <div class="status-label">道具明細記錄數</div>
                </div>
                <div class="status-item">
                    <div class="status-value <?php echo $status_info['migrated'] ? 'completed' : 'pending'; ?>">
                        <?php echo $status_info['migrated'] ? '已完成' : '未完成'; ?>
                    </div>
                    <div class="status-label">遷移狀態</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($status_info['migration_status']): ?>
            <div class="alert alert-info">
                <strong>遷移記錄：</strong><br>
                狀態：<?php echo $status_info['migration_status']['status']; ?><br>
                處理記錄數：<?php echo $status_info['migration_status']['records_processed']; ?><br>
                <?php if ($status_info['migration_status']['started_at']): ?>
                    開始時間：<?php echo $status_info['migration_status']['started_at']; ?><br>
                <?php endif; ?>
                <?php if ($status_info['migration_status']['completed_at']): ?>
                    完成時間：<?php echo $status_info['migration_status']['completed_at']; ?><br>
                <?php endif; ?>
                <?php if ($status_info['migration_status']['error_message']): ?>
                    錯誤訊息：<?php echo $status_info['migration_status']['error_message']; ?><br>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <?php if (!$status_info['migrated']): ?>
                <a href="?action=execute" class="btn btn-success"
                   onclick="return confirm('確定要執行遷移嗎？這個操作會：\n\n1. 自動備份現有資料\n2. 建立新的資料表結構\n3. 將 JSON 資料遷移到關聯式表格\n\n點擊確定開始執行遷移。')">
                    🚀 執行遷移
                </a>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✅ 遷移已完成！</strong><br>
                    您的系統現在已經使用關聯式資料表儲存道具資訊，不再依賴 JSON 格式。
                </div>
            <?php endif; ?>

            <a href="../" class="btn">返回管理頁面</a>
            <a href="migration_manager.php" class="btn">完整版遷移管理器</a>
        </div>

        <?php if ($status_info['data']['json_logs'] > 0 && !$status_info['migrated']): ?>
            <div class="alert alert-info">
                <strong>📝 遷移說明：</strong><br>
                • 系統檢測到 <?php echo $status_info['data']['json_logs']; ?> 筆使用 JSON 格式的派獎記錄<br>
                • 執行遷移將會：建立新的關聯式資料表、遷移現有資料、保持功能不變<br>
                • 遷移前會自動備份原始資料，安全可靠<br>
                • 遷移後將完全兼容所有 MySQL 版本
            </div>
        <?php endif; ?>
    </div>
</body>
</html>