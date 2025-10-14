<?php
/**
 * 派獎記錄 JSON 格式遷移腳本
 * 將 send_gift_logs 表中的 JSON 格式道具資料遷移到 send_gift_log_items 表
 *
 * 執行方式：
 * 1. 網頁執行：直接訪問此檔案
 * 2. 命令行執行：php migrate_gift_logs.php
 *
 * 注意：請先確保已執行 send_gift_migration.sql 建立新表格
 */

// 設定執行時間限制
set_time_limit(0);
ini_set('memory_limit', '512M');

// 引入資料庫連線
require_once '../include.php';

// 輸出函數
function output($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $prefix = '';

    switch ($type) {
        case 'error':
            $prefix = '[ERROR]';
            break;
        case 'success':
            $prefix = '[SUCCESS]';
            break;
        case 'warning':
            $prefix = '[WARNING]';
            break;
        default:
            $prefix = '[INFO]';
    }

    $output = "[$timestamp] $prefix $message" . PHP_EOL;

    if (php_sapi_name() === 'cli') {
        echo $output;
    } else {
        echo nl2br(htmlspecialchars($output));
        flush();
    }
}

/**
 * 遷移 JSON 資料到關聯式資料表
 */
function migrate_json_to_relational() {
    try {
        $pdo = openpdo();

        output("開始遷移派獎記錄 JSON 資料...");

        // 1. 檢查是否已完成遷移
        $migration_check = $pdo->prepare("
            SELECT status, records_processed
            FROM migration_status
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $migration_check->execute();
        $migration_status = $migration_check->fetch(PDO::FETCH_ASSOC);

        if ($migration_status && $migration_status['status'] === 'completed') {
            output("遷移已完成，處理了 {$migration_status['records_processed']} 筆記錄", 'success');
            return ['success' => true, 'message' => '遷移已完成'];
        }

        // 2. 更新遷移狀態為執行中
        $update_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'running', started_at = NOW()
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $update_status->execute();

        // 3. 檢查必要的表格是否存在
        $tables_check = [
            "SHOW TABLES LIKE 'send_gift_log_items'",
            "SHOW COLUMNS FROM send_gift_logs LIKE 'items_backup'",
            "SHOW COLUMNS FROM send_gift_logs LIKE 'items_summary'"
        ];

        foreach ($tables_check as $check_sql) {
            $check_result = $pdo->query($check_sql);
            if ($check_result->rowCount() === 0) {
                throw new Exception("必要的表格或欄位不存在，請先執行 send_gift_migration.sql");
            }
        }

        output("資料庫結構檢查通過");

        // 4. 取得需要遷移的記錄
        $count_query = $pdo->query("
            SELECT COUNT(*) as total
            FROM send_gift_logs
            WHERE items_backup IS NOT NULL
            AND JSON_VALID(items_backup) = 1
        ");
        $total_records = $count_query->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total_records === 0) {
            output("沒有需要遷移的記錄", 'warning');

            // 更新遷移狀態
            $complete_status = $pdo->prepare("
                UPDATE migration_status
                SET status = 'completed', completed_at = NOW(), records_processed = 0
                WHERE migration_name = 'json_to_relational_gift_logs'
            ");
            $complete_status->execute();

            return ['success' => true, 'message' => '沒有需要遷移的記錄'];
        }

        output("找到 $total_records 筆需要遷移的記錄");

        // 5. 開始遷移資料
        $pdo->beginTransaction();

        $processed = 0;
        $batch_size = 100;
        $offset = 0;

        while ($offset < $total_records) {
            // 分批處理記錄
            $logs_query = $pdo->prepare("
                SELECT id, items_backup
                FROM send_gift_logs
                WHERE items_backup IS NOT NULL
                AND JSON_VALID(items_backup) = 1
                LIMIT :limit OFFSET :offset
            ");
            $logs_query->bindValue(':limit', $batch_size, PDO::PARAM_INT);
            $logs_query->bindValue(':offset', $offset, PDO::PARAM_INT);
            $logs_query->execute();

            $batch_logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);

            if (empty($batch_logs)) {
                break;
            }

            foreach ($batch_logs as $log) {
                try {
                    $items = json_decode($log['items_backup'], true);

                    if (!is_array($items)) {
                        output("記錄 ID {$log['id']} 的 JSON 資料無效，跳過", 'warning');
                        continue;
                    }

                    $summary_parts = [];
                    $sort_order = 0;

                    foreach ($items as $item) {
                        // 處理可能的欄位名稱差異
                        $item_code = $item['itemCode'] ?? $item['item_code'] ?? '';
                        $item_name = $item['itemName'] ?? $item['item_name'] ?? '';
                        $quantity = intval($item['quantity'] ?? 1);

                        if (empty($item_code)) {
                            output("記錄 ID {$log['id']} 中發現空的道具編號，跳過此道具", 'warning');
                            continue;
                        }

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

                        // 建立摘要文字
                        $display_name = !empty($item_name) ? $item_name : $item_code;
                        $summary_parts[] = $display_name . ' x' . $quantity;
                    }

                    // 更新摘要欄位
                    $summary = implode(', ', $summary_parts);
                    $update_query = $pdo->prepare("
                        UPDATE send_gift_logs
                        SET items_summary = :summary
                        WHERE id = :id
                    ");
                    $update_query->execute([
                        ':summary' => $summary,
                        ':id' => $log['id']
                    ]);

                    $processed++;

                    // 每100筆顯示進度
                    if ($processed % 100 === 0) {
                        output("已處理 $processed / $total_records 筆記錄...");
                    }

                } catch (Exception $e) {
                    output("處理記錄 ID {$log['id']} 時發生錯誤: " . $e->getMessage(), 'error');
                    continue;
                }
            }

            $offset += $batch_size;
        }

        // 6. 驗證遷移結果
        $verification_query = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM send_gift_logs WHERE items_backup IS NOT NULL AND JSON_VALID(items_backup) = 1) as source_count,
                (SELECT COUNT(*) FROM send_gift_logs WHERE items_summary IS NOT NULL) as migrated_count,
                (SELECT COUNT(*) FROM send_gift_log_items) as items_count
        ");
        $verification = $verification_query->fetch(PDO::FETCH_ASSOC);

        output("遷移驗證結果：");
        output("- 原始記錄數：{$verification['source_count']}");
        output("- 已遷移記錄數：{$verification['migrated_count']}");
        output("- 道具明細記錄數：{$verification['items_count']}");

        if ($verification['migrated_count'] != $verification['source_count']) {
            throw new Exception("遷移驗證失敗：記錄數不一致");
        }

        $pdo->commit();

        // 7. 更新遷移狀態為完成
        $complete_status = $pdo->prepare("
            UPDATE migration_status
            SET status = 'completed', completed_at = NOW(), records_processed = :processed
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $complete_status->execute([':processed' => $processed]);

        output("遷移完成！共處理 $processed 筆記錄", 'success');

        return [
            'success' => true,
            'message' => "遷移完成，共處理 $processed 筆記錄",
            'processed' => $processed,
            'verification' => $verification
        ];

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();

            // 更新遷移狀態為失敗
            $error_status = $pdo->prepare("
                UPDATE migration_status
                SET status = 'failed', error_message = :error
                WHERE migration_name = 'json_to_relational_gift_logs'
            ");
            $error_status->execute([':error' => $e->getMessage()]);
        }

        output("遷移失敗: " . $e->getMessage(), 'error');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 清理遷移後的資料（可選）
 */
function cleanup_after_migration() {
    try {
        $pdo = openpdo();

        output("開始清理遷移後的資料...");

        // 檢查遷移是否已完成
        $migration_check = $pdo->prepare("
            SELECT status FROM migration_status
            WHERE migration_name = 'json_to_relational_gift_logs'
        ");
        $migration_check->execute();
        $status = $migration_check->fetch(PDO::FETCH_ASSOC);

        if (!$status || $status['status'] !== 'completed') {
            throw new Exception("遷移尚未完成，無法執行清理");
        }

        // 1. 移除原始的 items 欄位（已改名為 items_backup）
        // 注意：這個操作不可逆，請確保遷移完全成功
        $confirm = readline("是否要移除 items_backup 欄位？這個操作不可逆！(y/N): ");
        if (strtolower($confirm) === 'y') {
            $pdo->exec("ALTER TABLE send_gift_logs DROP COLUMN items_backup");
            output("已移除 items_backup 欄位", 'success');
        }

        // 2. 重新命名 items_summary 為 items（如果需要）
        $rename_confirm = readline("是否要將 items_summary 重新命名為 items？(y/N): ");
        if (strtolower($rename_confirm) === 'y') {
            $pdo->exec("ALTER TABLE send_gift_logs CHANGE COLUMN items_summary items TEXT COMMENT '道具摘要文字'");
            output("已將 items_summary 重新命名為 items", 'success');
        }

        output("清理完成", 'success');

    } catch (Exception $e) {
        output("清理失敗: " . $e->getMessage(), 'error');
    }
}

// 主執行邏輯
if (php_sapi_name() === 'cli') {
    // 命令行執行
    echo "派獎記錄 JSON 格式遷移工具\n";
    echo "==============================\n\n";

    $action = $argv[1] ?? 'migrate';

    if ($action === 'migrate') {
        $result = migrate_json_to_relational();
    } elseif ($action === 'cleanup') {
        cleanup_after_migration();
    } else {
        echo "使用方式：\n";
        echo "php migrate_gift_logs.php migrate   # 執行遷移\n";
        echo "php migrate_gift_logs.php cleanup   # 清理遷移後資料\n";
    }

} else {
    // 網頁執行
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>派獎記錄資料遷移</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .output { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0; font-family: monospace; white-space: pre-wrap; }
            .button { padding: 10px 20px; margin: 10px 5px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
            .button:hover { background: #005a87; }
            .danger { background: #dc3545; }
            .danger:hover { background: #a71e2a; }
        </style>
    </head>
    <body>
        <h1>派獎記錄 JSON 格式遷移工具</h1>
        <p>此工具會將 send_gift_logs 表中的 JSON 格式道具資料遷移到新的關聯式資料表中。</p>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'migrate'): ?>
            <div class="output">
                <?php
                migrate_json_to_relational();
                ?>
            </div>
            <a href="?" class="button">返回</a>
        <?php else: ?>
            <div>
                <a href="?action=migrate" class="button">開始遷移</a>
                <p><strong>注意：</strong>請先確保已執行 send_gift_migration.sql 建立新表格</p>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>