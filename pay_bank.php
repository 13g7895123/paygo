<?php

/**
 * 查詢銀行支付資訊
 * 
 * 根據 servers_log 的 auton 查詢相關的銀行支付資訊
 * 
 * @param PDO $pdo 資料庫連線物件
 * @param int $servers_log_auton servers_log 的 auton
 * @return array|false 銀行支付資訊陣列，錯誤時回傳 false
 */
function getBankPaymentInfo($pdo, $servers_log_auton) {
    try {
        // 查詢 servers_log 取得 serverid 和 pay_cp 等資訊
        $log_query = $pdo->prepare("
            SELECT serverid, pay_cp, is_bank, money, paytype, orderid, userip, gameid, foran, forname
            FROM servers_log 
            WHERE auton = :auton
        ");
        $log_query->bindValue(':auton', $servers_log_auton, PDO::PARAM_INT);
        // $log_query->bindValue(':auton', $servers_log_auton);
        $log_query->execute();
        
        $log_data = $log_query->fetch(PDO::FETCH_ASSOC);

        if (!$log_data) {
            return false; // 找不到 servers_log 記錄
        }
        
        // 查詢 servers 資料
        $server_query = $pdo->prepare("
            SELECT auton, names, id as server_suffix, pay_bank, gstats_bank
            FROM servers 
            WHERE id = :serverid
        ");
        $server_query->bindValue(':serverid', $log_data['serverid'], PDO::PARAM_INT);
        $server_query->execute();
        
        $server_data = $server_query->fetch(PDO::FETCH_ASSOC);
        if (!$server_data) {
            return false; // 找不到 servers 記錄
        }
        
        // 查詢 bank_funds 取得銀行支付設定
        $bank_query = $pdo->prepare("
            SELECT third_party_payment, merchant_id, username, hashkey, hashiv, verify_key
            FROM bank_funds 
            WHERE server_code = :server_code
        ");
        $bank_query->bindValue(':server_code', $log_data['foran'], PDO::PARAM_INT);
        $bank_query->execute();
        
        $bank_funds = $bank_query->fetchAll(PDO::FETCH_ASSOC);
        
        // 組織回傳資料
        $result = [
            'servers_log_info' => [
                'auton' => $servers_log_auton,
                'serverid' => $log_data['serverid'],
                'pay_cp' => $log_data['pay_cp'],
                'is_bank' => $log_data['is_bank'],
                'money' => $log_data['money'],
                'paytype' => $log_data['paytype'],
                'orderid' => $log_data['orderid'],
                'userip' => $log_data['userip'],
                'gameid' => $log_data['gameid'],
                'foran' => $log_data['foran'],
                'forname' => $log_data['forname']
            ],
            'server_info' => [
                'auton' => $server_data['auton'],
                'names' => $server_data['names'],
                'server_suffix' => $server_data['server_suffix'],
                'pay_bank' => $server_data['pay_bank'],
                'gstats_bank' => $server_data['gstats_bank']
            ],
            'bank_funds' => []
        ];
        
        // 處理銀行支付設定
        foreach ($bank_funds as $fund) {
            $result['bank_funds'][$fund['third_party_payment']] = [
                'payment_type' => $fund['third_party_payment'],
                'merchant_id' => $fund['merchant_id'],
                'username' => $fund['username'],
                'hashkey' => $fund['hashkey'],
                'hashiv' => $fund['hashiv'],
                'verify_key' => $fund['verify_key']
            ];
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("getBankPaymentInfo Error: " . $e->getMessage());
        return false;
    }
}

/**
 * 取得特定支付方式的銀行支付資訊
 * 
 * @param PDO $pdo 資料庫連線物件
 * @param int $servers_log_auton servers_log 的 auton
 * @param string $payment_type 支付方式類型 (ecpay, ebpay, smilepay 等)
 * @return array|false 特定支付方式資訊，錯誤時回傳 false
 */
function getSpecificBankPaymentInfo($pdo, $servers_log_auton, $payment_type) {
    $payment_info = getBankPaymentInfo($pdo, $servers_log_auton);
    
    if (!$payment_info) {
        return false;
    }
    
    if (!isset($payment_info['bank_funds'][$payment_type])) {
        return false; // 找不到指定的支付方式設定
    }
    
    return [
        'servers_log_info' => $payment_info['servers_log_info'],
        'server_info' => $payment_info['server_info'],
        'payment_config' => $payment_info['bank_funds'][$payment_type]
    ];
}

/**
 * 檢查是否為銀行轉帳交易
 * 
 * @param PDO $pdo 資料庫連線物件
 * @param int $servers_log_auton servers_log 的 auton
 * @return bool 是否為銀行轉帳交易
 */
function isBankTransfer($pdo, $servers_log_auton) {
    $query = $pdo->prepare("SELECT is_bank FROM servers_log WHERE auton = :auton");
    $query->bindValue(':auton', $servers_log_auton, PDO::PARAM_INT);
    $query->execute();
    
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result ? (bool)$result['is_bank'] : false;
}

/**
 * 取得 servers_log 記錄的完整支付資訊
 * 
 * 包含交易資訊、支付設定、銀行轉帳設定等完整資料
 * 
 * @param PDO $pdo 資料庫連線物件
 * @param int $servers_log_auton servers_log 的 auton
 * @return array|false 完整的支付資訊，錯誤時回傳 false
 */
function getCompletePaymentInfo($pdo, $servers_log_auton) {
    $payment_info = getBankPaymentInfo($pdo, $servers_log_auton);
    
    if (!$payment_info) {
        return false;
    }
    
    $is_bank_transfer = (bool)$payment_info['servers_log_info']['is_bank'];
    $pay_cp = $payment_info['servers_log_info']['pay_cp'];
    
    $result = $payment_info;
    $result['transaction_info'] = [
        'is_bank_transfer' => $is_bank_transfer,
        'payment_method' => $pay_cp,
        'transaction_type' => $is_bank_transfer ? 'bank_transfer' : 'credit_card_or_store'
    ];
    
    // 如果是銀行轉帳，提供對應的支付設定
    if ($is_bank_transfer && isset($payment_info['bank_funds'][$pay_cp])) {
        $result['active_payment_config'] = $payment_info['bank_funds'][$pay_cp];
    }
    
    return $result;
}

/**
 * 取得 ANT 回調記錄
 * 
 * @param PDO $pdo 資料庫連線
 * @param string $order_id 訂單編號 (可選)
 * @param int $servers_log_id servers_log 的 auton (可選)
 * @param int $limit 限制筆數 (預設 10)
 * @return array 回調記錄陣列
 */
function getANTCallbackLogs($pdo, $order_id = null, $servers_log_id = null, $limit = 10) {
    $where_conditions = [];
    $params = [];
    
    if ($order_id) {
        $where_conditions[] = "acl.order_id = ?";
        $params[] = $order_id;
    }
    
    if ($servers_log_id) {
        $where_conditions[] = "acl.servers_log_id = ?";
        $params[] = $servers_log_id;
    }
    
    $where_sql = "";
    if (!empty($where_conditions)) {
        $where_sql = "WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql = "
        SELECT acl.*, sl.orderid, sl.gameid, sl.money 
        FROM ant_callback_logs acl 
        LEFT JOIN servers_log sl ON acl.servers_log_id = sl.auton 
        {$where_sql}
        ORDER BY acl.created_at DESC 
        LIMIT ?
    ";
    
    $params[] = $limit;
    
    try {
        $query = $pdo->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    } catch (Exception $e) {
        error_log("getANTCallbackLogs Error: " . $e->getMessage());
        return [];
    }
}

/**
 * 取得最新的 ANT 回調記錄
 * 
 * @param PDO $pdo 資料庫連線
 * @param string $order_id 訂單編號
 * @return array|null 最新回調記錄或 null
 */
function getLatestANTCallback($pdo, $order_id) {
    $logs = getANTCallbackLogs($pdo, $order_id, null, 1);
    return !empty($logs) ? $logs[0] : null;
}

/**
 * 檢查 ANT 訂單是否有回調記錄
 * 
 * @param PDO $pdo 資料庫連線
 * @param string $order_id 訂單編號
 * @return bool 是否有回調記錄
 */
function hasANTCallbackLog($pdo, $order_id) {
    $callback = getLatestANTCallback($pdo, $order_id);
    return $callback !== null;
}

?>