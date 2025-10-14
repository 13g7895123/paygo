<?php
/**
 * Bank Funds Update Server Code API
 * 更新 bank_funds 表的 server_code 欄位，將 servers.id 替換為 servers.auton
 */

// 啟動會話
if (!isset($_SESSION)) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://test.paygo.tw');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../include.php");

// 設定執行時間和記憶體限制
set_time_limit(300); // 5分鐘
ini_set('memory_limit', '512M');

// 錯誤處理函數
function api_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 成功響應函數
function api_success($data = null, $message = null) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 權限檢查函數
function check_permission() {
    // 只有管理員可以執行更新
    if (empty($_SESSION["adminid"])) {
        api_error('Access denied: Only administrators can perform update operations', 403);
    }
    return true;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    $pdo = openpdo();

    // 檢查權限
    check_permission();

    switch ($action) {
        case 'preview':
            handle_preview_update($pdo);
            break;

        case 'execute':
            handle_execute_update($pdo);
            break;

        default:
            api_error('Invalid action. Use action=preview or action=execute', 400);
    }

} catch (Exception $e) {
    error_log("Bank Funds Update Server Code API Error: " . $e->getMessage());
    api_error('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * 預覽更新 - 顯示將要執行的操作
 */
function handle_preview_update($pdo) {
    try {
        // 取得所有 bank_funds 記錄
        $bank_funds_query = $pdo->query("
            SELECT id, server_code, third_party_payment, merchant_id
            FROM bank_funds
            ORDER BY id
        ");

        $bank_funds = $bank_funds_query->fetchAll(PDO::FETCH_ASSOC);

        $will_update = 0;
        $not_found = 0;
        $preview_details = [];

        foreach ($bank_funds as $fund) {
            $current_server_code = $fund['server_code'];

            // 查詢 servers 表取得 auton
            $server_query = $pdo->prepare("
                SELECT auton, names
                FROM servers
                WHERE id = :server_id
            ");
            $server_query->execute([':server_id' => $current_server_code]);
            $server = $server_query->fetch(PDO::FETCH_ASSOC);

            if ($server) {
                $new_server_code = $server['auton'];

                // 只有當 server_code 與 auton 不同時才需要更新
                if ($current_server_code != $new_server_code) {
                    $will_update++;
                    $preview_details[] = [
                        'bank_fund_id' => $fund['id'],
                        'third_party_payment' => $fund['third_party_payment'],
                        'merchant_id' => $fund['merchant_id'],
                        'current_server_code' => $current_server_code,
                        'new_server_code' => $new_server_code,
                        'server_name' => $server['names'],
                        'action' => 'update'
                    ];
                }
            } else {
                $not_found++;
                $preview_details[] = [
                    'bank_fund_id' => $fund['id'],
                    'third_party_payment' => $fund['third_party_payment'],
                    'merchant_id' => $fund['merchant_id'],
                    'current_server_code' => $current_server_code,
                    'new_server_code' => null,
                    'server_name' => null,
                    'action' => 'not_found',
                    'warning' => 'Server not found in servers table'
                ];
            }
        }

        api_success([
            'summary' => [
                'total_records' => count($bank_funds),
                'will_update' => $will_update,
                'not_found' => $not_found,
                'no_change_needed' => count($bank_funds) - $will_update - $not_found
            ],
            'details' => $preview_details
        ], "預覽完成：將更新 {$will_update} 筆記錄，{$not_found} 筆找不到對應的 server");

    } catch (Exception $e) {
        api_error('Preview update failed: ' . $e->getMessage());
    }
}

/**
 * 執行更新
 */
function handle_execute_update($pdo) {
    try {
        // 開始交易
        $pdo->beginTransaction();

        // 取得所有 bank_funds 記錄
        $bank_funds_query = $pdo->query("
            SELECT id, server_code, third_party_payment, merchant_id
            FROM bank_funds
            ORDER BY id
        ");

        $bank_funds = $bank_funds_query->fetchAll(PDO::FETCH_ASSOC);

        $updated_count = 0;
        $not_found_count = 0;
        $no_change_count = 0;
        $details = [];

        foreach ($bank_funds as $fund) {
            $current_server_code = $fund['server_code'];

            // 查詢 servers 表取得 auton
            $server_query = $pdo->prepare("
                SELECT auton, names
                FROM servers
                WHERE id = :server_id
            ");
            $server_query->execute([':server_id' => $current_server_code]);
            $server = $server_query->fetch(PDO::FETCH_ASSOC);

            if ($server) {
                $new_server_code = $server['auton'];

                // 只有當 server_code 與 auton 不同時才需要更新
                if ($current_server_code != $new_server_code) {
                    // 更新 bank_funds 的 server_code
                    $update_query = $pdo->prepare("
                        UPDATE bank_funds
                        SET server_code = :new_server_code,
                            updated_at = NOW()
                        WHERE id = :fund_id
                    ");

                    $update_query->execute([
                        ':new_server_code' => $new_server_code,
                        ':fund_id' => $fund['id']
                    ]);

                    $updated_count++;
                    $details[] = [
                        'bank_fund_id' => $fund['id'],
                        'third_party_payment' => $fund['third_party_payment'],
                        'merchant_id' => $fund['merchant_id'],
                        'old_server_code' => $current_server_code,
                        'new_server_code' => $new_server_code,
                        'server_name' => $server['names'],
                        'status' => 'updated'
                    ];
                } else {
                    $no_change_count++;
                }
            } else {
                $not_found_count++;
                $details[] = [
                    'bank_fund_id' => $fund['id'],
                    'third_party_payment' => $fund['third_party_payment'],
                    'merchant_id' => $fund['merchant_id'],
                    'server_code' => $current_server_code,
                    'status' => 'not_found',
                    'error' => 'Server not found in servers table'
                ];
            }
        }

        // 提交交易
        $pdo->commit();

        api_success([
            'summary' => [
                'total_records' => count($bank_funds),
                'updated' => $updated_count,
                'not_found' => $not_found_count,
                'no_change_needed' => $no_change_count
            ],
            'details' => $details
        ], "更新完成：已更新 {$updated_count} 筆記錄，{$not_found_count} 筆找不到對應的 server，{$no_change_count} 筆無需更新");

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        api_error('Update execution failed: ' . $e->getMessage());
    }
}

?>
