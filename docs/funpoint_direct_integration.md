# Funpoint 金流直接串接指南（無跳板版本）

## 目錄
1. [文件說明](#文件說明)
2. [系統架構比較](#系統架構比較)
3. [檔案結構](#檔案結構)
4. [支付流程](#支付流程)
5. [實作步驟](#實作步驟)
6. [環境設定](#環境設定)
7. [API 介面說明](#api-介面說明)
8. [回調處理](#回調處理)
9. [支付類型](#支付類型)
10. [資料表結構](#資料表結構)
11. [安全機制](#安全機制)
12. [錯誤處理](#錯誤處理)
13. [測試流程](#測試流程)
14. [需確認事項](#需確認事項)

---

## 文件說明

本文件基於以下兩份文件分析產出：
- `funpoint_integration.md` - 現有專案的 funpoint 金流串接文件（使用跳板）
- `funpoint_payment_system.md` - 跳板機 (gohost.tw) 的系統文件

本文件說明如何在新專案中直接導入 funpoint 匯款功能，**移除對外部跳板服務的依賴**。

---

## 系統架構比較

### 原架構（使用跳板）

```
用戶發起支付
    ↓
funpoint_next.php (生成 token)
    ↓
POST 到跳板 (gohost.tw/payment_background_funpoint.php)
    ↓
跳板調用 funpoint_api.php (主網站)
    ↓
跳板根據支付類型處理並轉發到 FunPoint
    ↓
FunPoint 處理支付
    ↓
[伺服器端] FunPoint -> gohost.tw/payment_background_funpoint_receive.php -> funpoint_r.php
[前端] FunPoint -> gohost.tw/payment_background_funpoint_receive_mid.php -> funpoint_payok.php
```

**跳板的功能**：
1. 接收主網站的支付請求
2. 調用主網站 API 取得支付資料
3. 根據支付類型（CVS/ATM/Credit）進行不同處理
4. 接收 FunPoint 回傳並轉發到正確的網域

### 新架構（無跳板）

```
用戶發起支付
    ↓
funpoint_next.php (整合原 API 邏輯)
    ↓
直接根據支付類型處理
    ↓
直接轉發到 FunPoint 支付頁面
    ↓
FunPoint 處理支付
    ↓
[伺服器端] FunPoint -> funpoint_r.php (直接)
[前端] FunPoint -> funpoint_payok.php (直接)
```

**變更重點**：
1. 移除對 gohost.tw 的依賴
2. 將跳板的邏輯整合到主網站
3. FunPoint 回調 URL 直接指向主網站
4. 移除訂單編號尾碼判斷邏輯（不需要多網域轉發）

---

## 檔案結構

### 必要檔案

```
project/
├── funpoint_next.php         # 支付處理頁面（整合 API 邏輯）
├── funpoint_r.php            # 回調接收頁面（伺服器端通知）
├── funpoint_payok.php        # 支付完成展示頁面（前端返回）
└── payment_class.php         # 支付類別定義（CheckMacValue 生成）
```

### 不需要的檔案

以下檔案在原架構中存在，但在無跳板版本中**不需要**：
- ~~`funpoint_api.php`~~ - 邏輯已整合到 `funpoint_next.php`

---

## 支付流程

### 完整流程圖

```
用戶發起支付
    ↓
funpoint_next.php
    ├─ 1. 檢查 SESSION 資料
    ├─ 2. 驗證訂單狀態
    ├─ 3. 取得商店設定（MerchantID, HashKey, HashIV）
    ├─ 4. 組合支付參數
    ├─ 5. 生成 CheckMacValue
    ├─ 6. 更新訂單記錄
    └─ 7. 根據支付類型處理
        │
        ├─ CVS（便利商店代碼）
        │   └─ 直接 POST 到 FunPoint API，取得繳費代碼
        │
        └─ ATM / Credit（ATM 轉帳 / 信用卡）
            └─ 產生 HTML 表單，自動提交到 FunPoint 支付頁面
    ↓
FunPoint 處理支付
    ↓
支付完成
    ├─ [伺服器端通知] FunPoint POST -> funpoint_r.php
    │   └─ 更新訂單狀態、發放虛擬貨幣、處理活動獎勵
    │
    └─ [前端返回] FunPoint Redirect -> funpoint_payok.php
        └─ 顯示繳費資訊（ATM 帳號、超商代碼等）
```

### 流程說明

#### 階段 1：支付發起
1. 用戶在網站選擇商品並進入結帳
2. 系統將訂單資訊存入 SESSION
3. 跳轉到 `funpoint_next.php`

#### 階段 2：支付處理
1. `funpoint_next.php` 驗證 SESSION 資料
2. 查詢訂單資訊（從 `servers_log` 表）
3. 查詢商店設定（從 `servers` 或 `bank_funds` 表）
4. 組合支付參數並生成 CheckMacValue
5. 根據支付類型：
   - **CVS**：直接調用 FunPoint API
   - **ATM/Credit**：產生表單並自動提交

#### 階段 3：FunPoint 處理
1. 用戶在 FunPoint 頁面完成支付操作
2. FunPoint 處理支付

#### 階段 4：結果通知
1. **伺服器端**：FunPoint POST 通知到 `funpoint_r.php`
   - 更新訂單狀態
   - 發放遊戲虛擬貨幣
   - 處理活動獎勵
2. **前端**：用戶瀏覽器跳轉到 `funpoint_payok.php`
   - 顯示繳費資訊

---

## 實作步驟

### 步驟 1：準備 payment_class.php

確保有 `funpoint` 類別提供 `generate()` 方法來生成 CheckMacValue：

```php
class funpoint {
    public static function generate($params, $HashKey, $HashIV) {
        // 移除 CheckMacValue 參數
        unset($params['CheckMacValue']);

        // 依照參數名稱排序（字母順序）
        ksort($params);

        // 組合字串：HashKey={HashKey}&參數1=值1&參數2=值2...&HashIV={HashIV}
        $checkStr = "HashKey={$HashKey}&";
        foreach ($params as $key => $value) {
            $checkStr .= "{$key}={$value}&";
        }
        $checkStr .= "HashIV={$HashIV}";

        // URL encode
        $checkStr = urlencode($checkStr);

        // 轉換為小寫
        $checkStr = strtolower($checkStr);

        // SHA256 hash
        $checkMacValue = hash('sha256', $checkStr);

        // 轉換為大寫
        return strtoupper($checkMacValue);
    }
}
```

### 步驟 2：修改 funpoint_next.php

將原本的跳板邏輯整合進來：

```php
<?php
session_start();
require_once('db_connection.php');  // 資料庫連線
require_once('payment_class.php');  // 支付類別

// 1. 檢查 SESSION 資料
$foran = $_SESSION["foran"] ?? null;
$serverid = $_SESSION["serverid"] ?? null;
$lastan = $_SESSION["lastan"] ?? null;

if (!$foran) {
    die("伺服器資料錯誤-8000201");
}
if (!$serverid) {
    die("伺服器資料錯誤-8000202");
}
if (!$lastan) {
    die("伺服器資料錯誤-8000203");
}

// 2. 查詢訂單記錄
$stmt = $pdo->prepare("SELECT * FROM servers_log WHERE auton = ?");
$stmt->execute([$lastan]);
$server_log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server_log) {
    die("不明錯誤-8000207");
}

// 檢查訂單狀態
if ($server_log["stats"] != 0) {
    die("金流狀態有誤-8000208");
}

// 3. 取得支付類型和金額
$paytype = $server_log["paytype"];
$money = $server_log["money"];
$gameid = $server_log["gameid"];
$orderid = $server_log["orderid"];

// 4. 取得伺服器設定
$stmt = $pdo->prepare("SELECT * FROM servers WHERE auton = ?");
$stmt->execute([$foran]);
$server_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server_info) {
    die("不明錯誤-8000204");
}

// 5. 根據支付類型取得商店設定
if ($paytype == 5) {
    // 信用卡
    $env = $server_info["gstats"];
    if ($env == 1) {
        $MerchantID = $server_info["MerchantID"];
        $HashKey = $server_info["HashKey"];
        $HashIV = $server_info["HashIV"];
    } else {
        // 測試環境
        $MerchantID = "1000031";
        $HashKey = "265flDjIvesceXWM";
        $HashIV = "pOOvhGd1V2pJbjfX";
    }
    $payment_url = ($env == 1)
        ? "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5"
        : "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
} else if ($paytype == 2) {
    // ATM 轉帳（從 bank_funds 取得）
    $payment_info = getSpecificBankPaymentInfo($pdo, $lastan, 'funpoint');
    $MerchantID = $payment_info['payment_config']['merchant_id'];
    $HashKey = $payment_info['payment_config']['hashkey'];
    $HashIV = $payment_info['payment_config']['hashiv'];
    $env = $server_info["gstats_bank"];
    $payment_url = ($env == 1)
        ? "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5"
        : "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
} else {
    // 其他支付方式
    $env = $server_info["gstats2"];
    if ($env == 1) {
        $MerchantID = $server_info["MerchantID2"];
        $HashKey = $server_info["HashKey2"];
        $HashIV = $server_info["HashIV2"];
    } else {
        $MerchantID = "1000031";
        $HashKey = "265flDjIvesceXWM";
        $HashIV = "pOOvhGd1V2pJbjfX";
    }
    $payment_url = ($env == 1)
        ? "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5"
        : "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
}

// 檢查商店資訊
if (!$MerchantID || !$HashKey || !$HashIV) {
    die("金流錯誤-8000206");
}

// 6. 設定支付方式
$paytype_mapping = [
    1 => ['ChoosePayment' => 'BARCODE', 'ChooseSubPayment' => 'BARCODE'],
    2 => ['ChoosePayment' => 'ATM', 'ChooseSubPayment' => 'ESUN'],
    3 => ['ChoosePayment' => 'CVS', 'ChooseSubPayment' => 'CVS'],
    4 => ['ChoosePayment' => 'CVS', 'ChooseSubPayment' => 'IBON'],
    5 => ['ChoosePayment' => 'Credit', 'ChooseSubPayment' => ''],
    6 => ['ChoosePayment' => 'WebATM', 'ChooseSubPayment' => '']
];

$ptt = $paytype_mapping[$paytype]['ChoosePayment'];
$csp = $paytype_mapping[$paytype]['ChooseSubPayment'];

// 7. 組合支付參數
$nowtime = date("Y/m/d H:i:s");
$ItemName = random_products($foran);  // 隨機商品名稱
$TradeDesc = "帳單中心";
$tradeno = $orderid;

// 設定 URL（改為直接指向自己的網站）
$base_url = "https://your-domain.com";  // ⚠️ 需修改為實際網域
$rurl = $base_url . "/funpoint_r.php";           // 伺服器端通知
$rurl2 = $base_url . "/funpoint_payok.php";      // 前端返回

$CheckMacData = [
    'ChoosePayment'      => $ptt,
    'ChooseSubPayment'   => $csp,
    'ClientRedirectURL'  => $rurl2,
    'EncryptType'        => 1,
    'ItemName'           => $ItemName,
    'MerchantID'         => $MerchantID,
    'MerchantTradeDate'  => $nowtime,
    'MerchantTradeNo'    => $tradeno,
    'PaymentType'        => 'aio',
    'ReturnURL'          => $rurl,
    'TotalAmount'        => $money,
    'TradeDesc'          => $TradeDesc
];

// 8. 生成 CheckMacValue
$CheckMacValue = funpoint::generate($CheckMacData, $HashKey, $HashIV);
$CheckMacData['CheckMacValue'] = $CheckMacValue;

// 9. 更新訂單記錄
$stmt = $pdo->prepare("UPDATE servers_log SET CheckMacValue = ?, forname = ? WHERE auton = ?");
$stmt->execute([$CheckMacValue, $server_info["names"], $lastan]);

// 10. 根據支付類型處理
if ($ptt == 'CVS' || $ptt == 'BARCODE') {
    // 便利商店：直接 POST 到 FunPoint API，輸出結果
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $payment_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($CheckMacData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
} else {
    // ATM / Credit：產生表單並自動提交
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>處理中...</title>
    </head>
    <body>
        <form id="payment_form" method="post" action="<?= $payment_url ?>">
            <?php foreach ($CheckMacData as $key => $value): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= $value ?>">
            <?php endforeach; ?>
        </form>
        <script>
            document.getElementById('payment_form').submit();
        </script>
    </body>
    </html>
    <?php
}

// 輔助函數：隨機商品名稱
function random_products($serverId) {
    global $pdo;
    $products = '維護費,主機租借費,資料處理費,線路費';

    $stmt = $pdo->prepare("SELECT products FROM servers WHERE auton = ?");
    $stmt->execute([$serverId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['products']) {
        $products = $result['products'];
    }

    $parr = explode(',', $products);
    return $parr[rand(0, count($parr) - 1)];
}

// 輔助函數：取得銀行支付資訊
function getSpecificBankPaymentInfo($pdo, $lastan, $payment_type) {
    // 實作從 bank_funds 表取得支付資訊
    // 回傳格式參考原文件
    // 此處需根據實際資料表結構實作
    return [
        'payment_config' => [
            'merchant_id' => '...',
            'hashkey' => '...',
            'hashiv' => '...'
        ]
    ];
}
?>
```

### 步驟 3：funpoint_r.php 保持不變

此檔案在原架構中已經是直接接收 FunPoint 的回調，**無需修改**。只需確保：
- 可以被外部訪問（FunPoint 伺服器）
- 正確處理回調資料並更新訂單狀態

### 步驟 4：funpoint_payok.php 保持不變

此檔案在原架構中已經是直接顯示支付結果，**無需修改**。

---

## 環境設定

### 1. 資料庫配置

需要在 `servers` 表中配置以下欄位：

**信用卡支付** (`paytype = 5`):
```sql
MerchantID   VARCHAR   商店代號
HashKey      VARCHAR   金鑰
HashIV       VARCHAR   向量
gstats       INT       環境設定 (0=測試, 1=正式)
```

**其他支付方式**:
```sql
MerchantID2  VARCHAR   商店代號
HashKey2     VARCHAR   金鑰
HashIV2      VARCHAR   向量
gstats2      INT       環境設定 (0=測試, 1=正式)
```

**銀行轉帳** (`paytype = 2`):
```sql
使用 bank_funds 資料表取得設定
gstats_bank  INT       環境設定 (0=測試, 1=正式)
```

### 2. FunPoint 環境

**測試環境**:
```
URL: https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5
測試商店資訊:
  MerchantID: "1000031"
  HashKey: "265flDjIvesceXWM"
  HashIV: "pOOvhGd1V2pJbjfX"
```

**正式環境**:
```
URL: https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5
商店資訊: 從資料庫 servers 表取得
```

### 3. 回調 URL 設定

在 `funpoint_next.php` 中設定正確的回調 URL：

```php
$base_url = "https://your-domain.com";  // ⚠️ 修改為實際網域
$rurl = $base_url . "/funpoint_r.php";           // 伺服器端通知
$rurl2 = $base_url . "/funpoint_payok.php";      // 前端返回
```

**重要**：
- 確保這兩個 URL 可以被 FunPoint 伺服器訪問
- 使用 HTTPS 協議
- 不要使用 localhost 或內部 IP

---

## API 介面說明

### funpoint_next.php

#### 功能
支付處理頁面，整合原 API 邏輯，負責完整的支付流程。

#### 輸入參數（來自 SESSION）
```php
$_SESSION["foran"]     // 伺服器 ID
$_SESSION["serverid"]  // 伺服器識別碼
$_SESSION["lastan"]    // 訂單記錄 ID
```

#### 處理流程
1. 檢查 SESSION 資料完整性
2. 查詢訂單記錄並驗證狀態
3. 取得商店設定（根據支付類型）
4. 組合支付參數
5. 生成 CheckMacValue
6. 更新訂單記錄
7. 根據支付類型：
   - **CVS/BARCODE**：直接 POST 到 FunPoint API
   - **ATM/Credit**：產生表單並自動提交

#### 輸出
- **CVS/BARCODE**：直接輸出 FunPoint API 回應（繳費代碼等）
- **ATM/Credit**：HTML 表單自動提交到 FunPoint

---

## 回調處理

### funpoint_r.php

此檔案**無需修改**，但需確保以下功能正常：

#### 功能
接收 FunPoint 的支付結果通知，處理訂單並發放遊戲虛擬貨幣。

#### 輸入參數（REQUEST）
```php
$_REQUEST["MerchantID"]            // 商店代號
$_REQUEST["MerchantTradeNo"]       // 訂單編號
$_REQUEST["RtnCode"]               // 回傳碼 (1=成功)
$_REQUEST["RtnMsg"]                // 回傳訊息
$_REQUEST["CheckMacValue"]         // 驗證碼
$_REQUEST["TradeAmt"]              // 交易金額
$_REQUEST["PaymentDate"]           // 支付時間
$_REQUEST["PaymentTypeChargeFee"]  // 手續費
```

#### 處理流程
1. 鎖定訂單記錄（FOR UPDATE）
2. 驗證訂單狀態
3. 判斷支付結果
4. 更新訂單狀態
5. 處理遊戲虛擬貨幣（成功時）
   - 連接遊戲資料庫
   - 發放贊助幣
   - 發放紅利幣（如有設定）
6. 處理活動獎勵
   - 滿額贈禮
   - 首購禮
   - 活動首購禮
   - 累積儲值
7. 提交事務

#### 回應格式
**成功**: `1|OK`
**失敗**: `0`

### funpoint_payok.php

此檔案**無需修改**，功能為顯示支付完成後的繳費資訊。

#### 輸入參數（POST）
```php
$_POST["MerchantTradeNo"]  // 訂單編號
$_POST["PaymentNo"]        // 繳費代碼/帳號
$_POST["BankCode"]         // 銀行代碼（ATM）
$_POST["vAccount"]         // 虛擬帳號（ATM）
$_POST["ExpireDate"]       // 繳費期限
```

#### 顯示內容
- ATM：銀行代碼、虛擬帳號、繳費期限
- 超商代碼：繳費代碼、繳費期限
- ibon：ibon 代碼、繳費期限
- 信用卡：完成提示

---

## 支付類型

### 支付方式對應表

| paytype | 支付方式 | ChoosePayment | ChooseSubPayment |
|---------|----------|---------------|------------------|
| 1 | 超商條碼 | BARCODE | BARCODE |
| 2 | ATM 轉帳 | ATM | ESUN |
| 3 | 超商代碼 | CVS | CVS |
| 4 | 7-11 ibon | CVS | IBON |
| 5 | 信用卡 | Credit | (空) |
| 6 | WebATM | WebATM | (空) |

### 支付流程差異

#### CVS / BARCODE（便利商店）
1. 直接 POST 到 FunPoint API
2. 取得繳費代碼
3. 用戶前往便利商店繳費
4. FunPoint 通知支付完成

#### ATM / Credit / WebATM
1. 產生 HTML 表單
2. 自動提交到 FunPoint 支付頁面
3. 用戶在 FunPoint 頁面完成操作
4. FunPoint 通知支付完成

---

## 資料表結構

### servers_log（訂單記錄表）

主要欄位：
```sql
auton              INT          訂單記錄 ID
orderid            VARCHAR      訂單編號（MerchantTradeNo）
foran              INT          伺服器 ID
gameid             VARCHAR      遊戲帳號
money              INT          訂單金額
bmoney             INT          實際發放金額
paytype            INT          支付類型
stats              INT          訂單狀態（0=未處理, 1=成功, 2=失敗, 3=測試成功）
CheckMacValue      VARCHAR      請求驗證碼
rCheckMacValue     VARCHAR      回傳驗證碼
RtnCode            INT          回傳碼
RtnMsg             VARCHAR      回傳訊息
hmoney             DECIMAL      手續費
rmoney             INT          實收金額
paytimes           DATETIME     支付時間
PaymentNo          VARCHAR      繳費代碼/帳號
ExpireDate         VARCHAR      繳費期限
forname            VARCHAR      伺服器名稱
errmsg             TEXT         錯誤訊息
```

### servers（伺服器設定表）

主要欄位：
```sql
auton              INT          伺服器 ID
id                 VARCHAR      伺服器識別碼
names              VARCHAR      伺服器名稱
MerchantID         VARCHAR      商店代號（信用卡）
HashKey            VARCHAR      金鑰（信用卡）
HashIV             VARCHAR      向量（信用卡）
MerchantID2        VARCHAR      商店代號（其他）
HashKey2           VARCHAR      金鑰（其他）
HashIV2            VARCHAR      向量（其他）
gstats             INT          信用卡環境（0=測試, 1=正式）
gstats2            INT          其他環境（0=測試, 1=正式）
gstats_bank        INT          銀行環境（0=測試, 1=正式）
db_ip              VARCHAR      遊戲資料庫 IP
db_port            INT          遊戲資料庫 Port
db_name            VARCHAR      遊戲資料庫名稱
db_user            VARCHAR      遊戲資料庫帳號
db_pass            VARCHAR      遊戲資料庫密碼
db_pid             VARCHAR      贊助幣物品 ID
db_bonusid         VARCHAR      紅利幣物品 ID
db_bonusrate       DECIMAL      紅利幣比例
paytable           VARCHAR      支付記錄表名稱
paytable_custom    VARCHAR      自訂表名稱
products           TEXT         商品名稱清單
custombg           VARCHAR      自訂背景圖片
```

### servers_gift（活動獎勵設定表）

主要欄位：
```sql
foran              INT          伺服器 ID
types              INT          獎勵類型（1=滿額, 2=首購, 3=累積, 4=活動首購）
pid                VARCHAR      物品 ID 或設定標識（'stat'=開關）
m1                 INT          金額下限（或累積儲值門檻）
m2                 INT          金額上限
sizes              INT          數量（或開關狀態）
dd                 DATETIME     時間設定（活動首購用）
```

### bank_funds（銀行轉帳設定表）

用於 paytype = 2（ATM 轉帳）時取得商店設定。

---

## 安全機制

### 1. CheckMacValue 驗證

**生成規則**：
1. 移除 `CheckMacValue` 參數
2. 依照參數名稱排序（字母順序）
3. 組合字串：`HashKey={HashKey}&參數1=值1&參數2=值2...&HashIV={HashIV}`
4. 進行 URL encode
5. 轉換為小寫
6. 進行 SHA256 hash
7. 轉換為大寫

**實作範例**（已包含在步驟 1）

**建議加強**：
在 `funpoint_r.php` 中應驗證回傳的 CheckMacValue：
```php
$receivedCheckMacValue = $_REQUEST["CheckMacValue"];
$calculatedCheckMacValue = funpoint::generate($params, $HashKey, $HashIV);

if ($receivedCheckMacValue !== $calculatedCheckMacValue) {
    die("0");  // 驗證失敗
}
```

### 2. 訂單鎖定機制

使用資料庫 `FOR UPDATE` 鎖定防止重複處理：
```php
BEGIN TRANSACTION;
SELECT * FROM servers_log WHERE orderid = ? FOR UPDATE;
// ... 處理邏輯
COMMIT;
```

### 3. 訂單狀態檢查

```php
// 確保訂單未被處理過
if ($datalist["stats"] != 0 && $_POST["mockpay"] != 1) {
    die("0");
}
```

### 4. HTTPS 連線

所有與 FunPoint 的通訊必須使用 HTTPS：
- 支付頁面 URL
- 回調 URL (ReturnURL, ClientRedirectURL)

### 5. 參數驗證

在 `funpoint_next.php` 中驗證所有必要參數：
- SESSION 資料完整性
- 訂單狀態
- 商店設定完整性

---

## 錯誤處理

### 錯誤代碼對照表

| 錯誤代碼 | 錯誤訊息 | 觸發位置 |
|----------|----------|----------|
| 8000201 | 伺服器資料錯誤（foran 為空） | funpoint_next.php |
| 8000202 | 伺服器資料錯誤（serverid 為空） | funpoint_next.php |
| 8000203 | 伺服器資料錯誤（lastan 為空） | funpoint_next.php |
| 8000204 | 不明錯誤（找不到伺服器記錄） | funpoint_next.php |
| 8000206 | 金流錯誤（商店資訊不完整） | funpoint_next.php |
| 8000207 | 不明錯誤（找不到訂單記錄） | funpoint_next.php |
| 8000208 | 金流狀態有誤（訂單已處理） | funpoint_next.php |
| 8000301 | 資料錯誤（MerchantTradeNo 為空） | funpoint_payok.php |
| 8000302 | 不明錯誤（找不到訂單） | funpoint_payok.php |

### 回調錯誤處理

在 `funpoint_r.php` 中的錯誤會記錄到 `servers_log.errmsg`：
- `找尋伺服器資料庫時發生錯誤`
- `存入贊助幣時發生錯誤`
- `存入紅利幣時發生錯誤`

### 例外處理

```php
try {
    $pdo->beginTransaction();
    // ... 處理邏輯
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Caught exception: ', $e->getMessage();
}
```

---

## 測試流程

### 1. 測試環境設定

```sql
-- 設定測試模式
UPDATE servers SET
    gstats = 0,      -- 信用卡測試環境
    gstats2 = 0,     -- 其他支付測試環境
    gstats_bank = 0  -- 銀行轉帳測試環境
WHERE auton = ?;
```

### 2. 測試步驟

#### 步驟 1：準備測試訂單
1. 在系統中建立測試訂單
2. 確保 SESSION 資料正確設定
3. 記錄訂單編號

#### 步驟 2：測試支付流程
1. 訪問 `funpoint_next.php`
2. 確認自動跳轉到 FunPoint 測試環境
3. 在 FunPoint 測試頁面完成支付操作

#### 步驟 3：檢查回調
1. 查看 `servers_log` 表確認：
   - `stats` = 3（測試成功）
   - `RtnMsg` = '模擬付款成功'
2. 檢查是否收到伺服器端通知（funpoint_r.php）
3. 檢查前端是否正確跳轉（funpoint_payok.php）

#### 步驟 4：驗證虛擬貨幣
連接遊戲資料庫檢查：
```sql
SELECT * FROM {paytable}
WHERE account = ?
ORDER BY create_time DESC;
```

### 3. 測試各種支付類型

分別測試以下支付方式：
- [ ] 超商條碼（paytype=1）
- [ ] ATM 轉帳（paytype=2）
- [ ] 超商代碼（paytype=3）
- [ ] 7-11 ibon（paytype=4）
- [ ] 信用卡（paytype=5）
- [ ] WebATM（paytype=6）

### 4. 正式環境上線前檢查

- [ ] 將 gstats/gstats2/gstats_bank 設定為 1（正式環境）
- [ ] 更新正式環境的商店設定（MerchantID, HashKey, HashIV）
- [ ] 確認回調 URL 使用正式網域
- [ ] 確認 HTTPS 憑證有效
- [ ] 執行小額真實交易測試

---

## 需確認事項

以下事項在原文件中未明確說明，需要在實作時確認：

### 1. CheckMacValue 生成演算法

**原文件描述**：
> **驗證碼生成規則**（推測與 Opay 類似）

**需確認**：
- 實際的 CheckMacValue 生成演算法是否與描述一致
- URL encode 的具體規則
- 是否有特殊字元需要額外處理

### 2. bank_funds 資料表結構

**原文件描述**：
```php
$payment_info = getSpecificBankPaymentInfo($pdo, $lastan, 'funpoint');
```

**需確認**：
- `bank_funds` 資料表的完整結構
- `getSpecificBankPaymentInfo()` 函數的實際實作
- 回傳資料的確切格式

### 3. 遊戲資料庫連線函數

**原文件描述**：
```php
$gamepdo = opengamepdo($ip, $port, $dbname, $user, $pass);
```

**需確認**：
- `opengamepdo()` 函數的實作細節
- 遊戲資料庫的連線方式
- 錯誤處理機制

### 4. 訂單編號生成規則

**原文件未明確說明**：
- 訂單編號（MerchantTradeNo）的生成格式
- 是否有長度限制
- 是否需要特定前綴或後綴
- 在無跳板版本中，是否仍需要訂單編號尾碼

**建議**：
- 訂單編號應唯一且可追溯
- 格式範例：`YYYYMMDDHHMMSS` + 流水號（如：`20240101120000001`）
- 無跳板版本可移除訂單編號尾碼邏輯

### 5. CVS/BARCODE 支付的回傳處理

**原文件描述**：
在 `funpoint_next.php` 中，CVS/BARCODE 支付會直接輸出 FunPoint API 的回應。

**需確認**：
- FunPoint API 對 CVS/BARCODE 的回應格式
- 回應中是否包含繳費代碼
- 是否需要額外處理或轉換格式
- 如何將繳費代碼展示給用戶

**建議實作**：
```php
if ($ptt == 'CVS' || $ptt == 'BARCODE') {
    // 呼叫 FunPoint API
    $response = curl_exec($ch);

    // 解析回應（需確認實際格式）
    $response_data = json_decode($response, true);

    // 更新訂單記錄（儲存繳費代碼）
    if (isset($response_data['PaymentNo'])) {
        $stmt = $pdo->prepare("UPDATE servers_log SET PaymentNo = ?, ExpireDate = ? WHERE auton = ?");
        $stmt->execute([
            $response_data['PaymentNo'],
            $response_data['ExpireDate'],
            $lastan
        ]);
    }

    // 跳轉到展示頁面
    header("Location: funpoint_payok.php?orderid=" . $orderid);
}
```

### 6. 活動獎勵的物品發放

**原文件描述**：
活動獎勵會插入記錄到遊戲資料庫的 paytable 表。

**需確認**：
- 遊戲資料庫 paytable 表的完整結構
- 物品 ID (pid) 的格式和範圍
- 是否需要額外觸發遊戲內的通知機制

### 7. 錯誤日誌記錄

**原文件未說明**：
- 是否有統一的錯誤日誌記錄機制
- 日誌儲存位置和格式
- 是否需要通知管理員

**建議**：
實作錯誤日誌記錄函數：
```php
function log_error($error_code, $error_msg, $context = []) {
    $log_file = __DIR__ . '/logs/funpoint_error.log';
    $log_entry = sprintf(
        "[%s] Code: %s, Message: %s, Context: %s\n",
        date('Y-m-d H:i:s'),
        $error_code,
        $error_msg,
        json_encode($context)
    );
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
```

### 8. IP 記錄功能

**原文件描述**：
```php
$user_IP = get_real_ip();
```

**需確認**：
- `get_real_ip()` 函數的實作
- 是否考慮 Proxy 和 CDN 的情況
- IP 記錄儲存在哪個欄位

**建議實作**：
```php
function get_real_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
```

### 9. 自訂背景圖功能

**原文件描述**：
```php
SELECT custombg FROM servers WHERE auton = ?;
```

**需確認**：
- 自訂背景圖的儲存路徑
- 圖片格式和大小限制
- 在哪些頁面使用自訂背景

### 10. 測試帳號資訊

**原文件描述**：
測試環境使用固定的測試帳號。

**需確認**：
- 測試帳號是否對所有商家通用
- 測試環境的限制（金額、次數等）
- 如何申請正式環境的商店帳號

### 11. CheckMacValue 在回調時的驗證

**原文件提到**：
> 雖然程式碼中生成了 `CheckMacValue`，但在回調處理中**未進行驗證**。

**需確認**：
- 是否必須在 `funpoint_r.php` 中實作驗證
- 驗證失敗的處理方式
- FunPoint 回傳的 CheckMacValue 演算法是否與發送時相同

### 12. 資料庫事務處理的範圍

**需確認**：
- 在 `funpoint_r.php` 中，事務處理是否包含所有資料庫操作
- 遊戲資料庫的操作是否也在事務中
- 如果遊戲資料庫操作失敗，主資料庫是否應該回滾

### 13. 回調重試機制

**需確認**：
- FunPoint 是否會重試回調（如果回應非 "1|OK"）
- 重試的頻率和次數
- 如何處理重複的回調通知

### 14. 網域設定

**必須修改**：
在 `funpoint_next.php` 中：
```php
$base_url = "https://your-domain.com";  // ⚠️ 需修改為實際網域
```

**需確認**：
- 正式環境的網域名稱
- 是否使用 HTTPS（必須）
- 是否有 CDN 或 Load Balancer

### 15. 支付方式的啟用狀態

**需確認**：
- 不同支付方式是否需要單獨申請
- 如何控制哪些支付方式可用
- 是否需要在介面上提供開關

---

## 總結

### 主要變更重點

1. **移除跳板依賴**：不再透過 gohost.tw 轉發
2. **整合 API 邏輯**：將原本的 `funpoint_api.php` 邏輯整合到 `funpoint_next.php`
3. **直接回調**：FunPoint 直接回調到主網站的 `funpoint_r.php` 和 `funpoint_payok.php`
4. **簡化架構**：減少中間層，提升效能和可維護性

### 優勢

1. **獨立性**：不依賴外部跳板服務
2. **效能**：減少網路請求次數
3. **安全性**：減少資料傳輸環節
4. **可控性**：完全掌控支付流程
5. **簡化**：不需要訂單編號尾碼判斷邏輯

### 注意事項

1. 確保所有回調 URL 可被 FunPoint 訪問
2. 使用 HTTPS 協議
3. 實作 CheckMacValue 驗證增強安全性
4. 完善錯誤處理和日誌記錄
5. 充分測試各種支付類型
6. 確認所有「需確認事項」

---

*文件撰寫日期：2025-11-07*
*基於文件：funpoint_integration.md, funpoint_payment_system.md*
*版本：1.0*
