# FunPoint 金流串接說明文件

## 目錄
1. [系統架構](#系統架構)
2. [支付流程](#支付流程)
3. [環境設定](#環境設定)
4. [API 介面說明](#api-介面說明)
5. [回調處理](#回調處理)
6. [支付類型](#支付類型)
7. [安全機制](#安全機制)
8. [錯誤處理](#錯誤處理)

---

## 系統架構

### 主要檔案結構

```
paygo/
├── funpoint_next.php        # 支付跳轉頁面
├── funpoint_api.php         # API 處理頁面
├── funpoint_r.php           # 回調接收頁面
├── funpoint_payok.php       # 支付完成展示頁面
└── payment_class.php        # 支付類別定義
```

### 外部跳板服務

- **跳板 API**: `https://gohost.tw/payment_background_funpoint.php`
- **回調接收**: `https://gohost.tw/payment_background_funpoint_receive.php`
- **中間跳轉**: `https://gohost.tw/payment_background_funpoint_receive_mid.php`

---

## 支付流程

### 完整流程圖

```
用戶發起支付
    ↓
funpoint_next.php (生成 token)
    ↓
POST 到跳板 (gohost.tw)
    ↓
跳板調用 funpoint_api.php
    ↓
組合支付參數並生成 CheckMacValue
    ↓
返回支付表單數據
    ↓
跳轉到 FunPoint 支付頁面
    ↓
用戶完成支付
    ↓
FunPoint 回調 funpoint_r.php
    ↓
處理訂單並更新狀態
    ↓
返回 "1|OK" 給 FunPoint
    ↓
跳轉到 funpoint_payok.php
    ↓
顯示繳費資訊（ATM/超商代碼等）
```

---

## 環境設定

### 1. 正式環境與測試環境

FunPoint 提供兩種環境：

#### 正式環境
- **URL**: `https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5`
- **條件**: `gstats = 1`（信用卡）或 `gstats2 = 1`（其他支付方式）或 `gstats_bank = 1`（銀行轉帳）

#### 測試環境
- **URL**: `https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5`
- **測試商店資訊**:
  ```php
  MerchantID: "1000031"
  HashKey: "265flDjIvesceXWM"
  HashIV: "pOOvhGd1V2pJbjfX"
  ```

### 2. 資料庫配置

需要在 `servers` 表中配置以下欄位：

- **信用卡支付** (`paytype = 5`):
  - `MerchantID`: 商店代號
  - `HashKey`: 金鑰
  - `HashIV`: 向量
  - `gstats`: 環境設定 (0=測試, 1=正式)

- **其他支付方式**:
  - `MerchantID2`: 商店代號
  - `HashKey2`: 金鑰
  - `HashIV2`: 向量
  - `gstats2`: 環境設定 (0=測試, 1=正式)

- **銀行轉帳** (`paytype = 2`):
  - 使用 `bank_funds` 資料表取得設定
  - `gstats_bank`: 環境設定 (0=測試, 1=正式)

---

## API 介面說明

### funpoint_next.php

#### 功能
支付跳轉頁面，負責驗證 SESSION 資料並生成安全 token。

#### 輸入參數（來自 SESSION）
```php
$_SESSION["foran"]     // 伺服器 ID
$_SESSION["serverid"]  // 伺服器識別碼
$_SESSION["lastan"]    // 訂單記錄 ID
```

#### 處理流程
1. 檢查 SESSION 資料完整性
2. 生成隨機 token：
   ```php
   $token = strtoupper(hash('sha256', strtolower(generateRandomString(8) . 'myszfutoken')));
   ```
3. 將 token 更新到資料庫 `servers_log.token`
4. 透過表單自動 POST 到跳板

#### 輸出
HTML 表單自動提交到跳板：
```html
<form method="post" action="https://gohost.tw/payment_background_funpoint.php">
    <input name="foran" value="...">
    <input name="serverid" value="...">
    <input name="lastan" value="...">
    <input name="token" value="...">
    <input name="domain" value="paygo">
</form>
```

---

### funpoint_api.php

#### 功能
處理支付請求，組合支付參數並生成驗證碼。

#### 輸入參數（POST）
```php
$_POST["foran"]    // 伺服器 ID
$_POST["lastan"]   // 訂單記錄 ID
$_POST["serverid"] // 伺服器識別碼
```

#### 處理流程

1. **驗證訂單狀態**
   ```php
   // 查詢訂單記錄
   SELECT * FROM servers_log WHERE auton = ?

   // 檢查訂單狀態必須為 0（未處理）
   if ($server_log["stats"] != 0) {
       error: '金流狀態有誤-8000208'
   }
   ```

2. **取得商店設定**
   根據支付類型選擇對應的商店設定：

   - **信用卡 (paytype=5)**:
     ```php
     if ($env == 1) {
         // 正式環境
         MerchantID, HashKey, HashIV 從 servers 表取得
     } else {
         // 測試環境
         使用預設測試帳號
     }
     ```

   - **銀行轉帳 (paytype=2)**:
     ```php
     // 從 bank_funds 取得設定
     $payment_info = getSpecificBankPaymentInfo($pdo, $lastan, 'funpoint');
     ```

   - **其他支付方式**:
     ```php
     if ($env2 == 1) {
         // 正式環境
         MerchantID2, HashKey2, HashIV2
     } else {
         // 測試環境
     }
     ```

3. **組合支付參數**
   ```php
   $CheckMacData = [
       'ChoosePayment'      => $ptt,        // 支付方式
       'ChooseSubPayment'   => $csp,        // 子支付方式
       'ClientRedirectURL'  => $rurl2,      // 用戶跳轉 URL
       'EncryptType'        => 1,           // 加密類型
       'ItemName'           => $ItemName,   // 商品名稱
       'MerchantID'         => $MerchantID, // 商店代號
       'MerchantTradeDate'  => $nowtime,    // 交易時間
       'MerchantTradeNo'    => $tradeno,    // 訂單編號
       'PaymentType'        => 'aio',       // 支付類型
       'ReturnURL'          => $rurl,       // 後端回調 URL
       'TotalAmount'        => $money,      // 金額
       'TradeDesc'          => $TradeDesc   // 交易描述
   ];
   ```

4. **生成檢查碼**
   ```php
   $CheckMacValue = funpoint::generate($CheckMacData, $HashKey, $HashIV);
   ```

5. **更新訂單記錄**
   ```php
   UPDATE servers_log
   SET CheckMacValue = ?, forname = ?
   WHERE auton = ?
   ```

#### 輸出格式（JSON）
```json
{
    "success": true,
    "payment_url": "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5",
    "payment_value": {
        "ChoosePayment": "Credit",
        "ChooseSubPayment": "",
        "EncryptType": "1",
        "ItemName": "維護費",
        "MerchantID": "1000031",
        "MerchantTradeDate": "2024/10/22 15:30:00",
        "MerchantTradeNo": "20241022153000001",
        "ClientRedirectURL": "...",
        "PaymentType": "aio",
        "ReturnURL": "...",
        "TotalAmount": "1000",
        "TradeDesc": "帳單中心",
        "CheckMacValue": "..."
    }
}
```

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

### 商品名稱隨機選擇

```php
function random_products($serverId) {
    // 預設商品清單
    $products = '維護費,主機租借費,資料處理費,線路費';

    // 從伺服器設定取得自訂商品清單
    if ($serverId) {
        $query = "SELECT products FROM servers WHERE id = ?";
        // 若有設定則使用伺服器自訂清單
    }

    // 隨機返回一個商品名稱
    return $parr[rand(0, count($parr) - 1)];
}
```

---

## 回調處理

### funpoint_r.php

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

1. **鎖定訂單記錄**
   ```php
   BEGIN TRANSACTION;
   SELECT * FROM servers_log WHERE orderid = ? FOR UPDATE;
   ```

2. **驗證訂單狀態**
   ```php
   // 訂單狀態必須為 0（未處理）
   if ($datalist["stats"] != 0 && $_POST["mockpay"] != 1) {
       die("0");
   }
   ```

3. **判斷支付結果**
   ```php
   if ($RtnCode == 1) {
       $rstat = ($RtnMsg == '模擬付款成功') ? 3 : 1;  // 3=測試成功, 1=正式成功
   } else {
       $rstat = 2;  // 失敗
   }
   ```

4. **更新訂單狀態**
   ```php
   UPDATE servers_log SET
       stats = ?,                    // 訂單狀態
       hmoney = ?,                   // 手續費
       paytimes = ?,                 // 支付時間
       rmoney = ?,                   // 實收金額
       rCheckMacValue = ?,           // 回傳驗證碼
       RtnCode = ?,                  // 回傳碼
       RtnMsg = ?                    // 回傳訊息
   WHERE orderid = ?
   ```

5. **處理遊戲虛擬貨幣**（僅當支付成功時）

   a. **連接遊戲資料庫**
   ```php
   $gamepdo = opengamepdo($ip, $port, $dbname, $user, $pass);
   ```

   b. **ezpay 特殊處理**（如果 paytable = 'ezpay'）
   ```php
   INSERT INTO ezpay (amount, payname, state)
   VALUES (?, ?, 1)
   ```

   c. **一般贊助幣處理**
   ```php
   INSERT INTO {$paytable} (p_id, p_name, count, account, r_count, card)
   VALUES (?, '贊助幣', ?, ?, ?, ?)

   // card 參數：信用卡支付為 1，其他為 0
   ```

   d. **紅利幣處理**（如果有設定紅利比例）
   ```php
   $bonusmoney = $money * ($bonusrate / 100);

   INSERT INTO {$paytable} (p_id, p_name, count, account, r_count)
   VALUES (?, '紅利幣', ?, ?, ?)
   ```

6. **活動獎勵處理**

   #### a. 滿額贈禮 (types=1)

   **開啟條件**:
   ```php
   SELECT * FROM servers_gift
   WHERE foran = ? AND types = 1 AND pid = 'stat' AND sizes = 1
   ```

   **發放邏輯**:
   ```php
   // 查詢所有滿額贈禮設定
   SELECT * FROM servers_gift
   WHERE foran = ? AND types = 1 AND NOT pid = 'stat'

   // 檢查每個區間
   if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
       INSERT INTO {$paytable} (p_id, p_name, count, account)
       VALUES (?, '滿額贈禮', ?, ?)
   }
   ```

   #### b. 首購禮 (types=2)

   **判斷首購**:
   ```php
   SELECT COUNT(*) FROM {$paytable}
   WHERE account = ? AND p_name = '贊助幣'

   // 如果計數為 1，則為首購
   ```

   **發放邏輯**:
   ```php
   if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
       INSERT INTO {$paytable} (p_id, p_name, count, account)
       VALUES (?, '首購禮', ?, ?)
   }
   ```

   #### c. 活動首購禮 (types=4)

   **時間範圍檢查**:
   ```php
   SELECT * FROM servers_gift
   WHERE foran = ? AND types = 4 AND pid IN ('time1', 'time2')

   // 檢查當前時間是否在活動時間內
   if (time() >= strtotime($time1) && time() <= strtotime($time2))
   ```

   **判斷活動期間首購**:
   ```php
   SELECT COUNT(*) FROM {$paytable}
   WHERE account = ?
     AND p_name = '贊助幣'
     AND create_time BETWEEN '$time1' AND '$time2'
   ```

   #### d. 累積儲值 (types=3)

   **計算累積金額**:
   ```php
   SELECT SUM(r_count) FROM {$paytable}
   WHERE account = ? AND p_name = '贊助幣'
   ```

   **發放邏輯**:
   ```php
   if ($total_pay >= $m1 && $sizes > 0) {
       // 檢查是否已發放過該檔次的累積儲值禮
       SELECT COUNT(*) FROM {$paytable}
       WHERE account = ? AND p_name = '累積儲值' AND r_count = ?

       // 若未發放過，則發放
       if (count == 0) {
           INSERT INTO {$paytable} (p_id, p_name, count, account, r_count)
           VALUES (?, '累積儲值', ?, ?, ?)
       }
   }
   ```

7. **提交事務**
   ```php
   COMMIT;
   ```

8. **錯誤處理**
   ```php
   try {
       // ... 處理邏輯
   } catch (Exception $e) {
       ROLLBACK;
       echo 'Caught exception: ', $e->getMessage();
   }
   ```

#### 回應格式

**成功**:
```
1|OK
```

**失敗**:
```
0
```

#### 錯誤記錄

所有錯誤會記錄到 `servers_log.errmsg` 欄位：
- `找尋伺服器資料庫時發生錯誤`
- `存入贊助幣時發生錯誤`
- `存入紅利幣時發生錯誤`

---

### funpoint_payok.php

#### 功能
顯示支付完成後的繳費資訊頁面（ATM 虛擬帳號、超商代碼、ibon 代碼等）。

#### 輸入參數（POST）
```php
$_POST["MerchantTradeNo"]  // 訂單編號
$_POST["PaymentNo"]        // 繳費代碼/帳號
$_POST["BankCode"]         // 銀行代碼（ATM）
$_POST["vAccount"]         // 虛擬帳號（ATM）
$_POST["ExpireDate"]       // 繳費期限
```

#### 處理流程

1. **查詢訂單資訊**
   ```php
   SELECT * FROM servers_log WHERE orderid = ?
   ```

2. **根據支付類型顯示對應資訊**

   **ATM 虛擬帳號 (paytype=2)**:
   ```php
   if (!$sqd["PaymentNo"]) {
       UPDATE servers_log
       SET PaymentNo = ?, ExpireDate = ?
       WHERE orderid = ?
   }

   // 顯示：銀行代碼、虛擬帳號、繳費期限
   ```

   **超商代碼 (paytype=3)**:
   ```php
   // 顯示：超商繳費代碼、繳費期限
   // 提供複製功能
   ```

   **ibon 代碼 (paytype=4)**:
   ```php
   // 顯示：ibon 繳費代碼、繳費期限
   ```

   **信用卡 (paytype=5)**:
   ```php
   // 提示用戶盡速完成刷卡
   ```

3. **顯示頁面元素**
   - 伺服器名稱
   - 繳費資訊
   - 繳費教學連結
   - 用戶 IP 位置
   - 注意事項

#### 頁面設計

```html
<section id="slider" class="fullheight">
    <div class="main-form">
        <div class="main-title">遊戲伺服器：【{forname}】</div>
        <div>繳費教學連結</div>
        <div>繳費資訊顯示區</div>
        <a href="/" class="btn">回首頁</a>
        <div>注意事項</div>
        <div>IP 位置：{user_IP}</div>
    </div>
</section>
```

---

## 安全機制

### 1. Token 驗證

在 `funpoint_next.php` 中生成並儲存 token：
```php
$token = strtoupper(hash('sha256', strtolower(generateRandomString(8) . 'myszfutoken')));

UPDATE servers_log SET token = ? WHERE auton = ?
```

### 2. CheckMacValue 驗證

使用 funpoint 類別生成驗證碼：
```php
$CheckMacValue = funpoint::generate($CheckMacData, $HashKey, $HashIV);
```

**驗證碼生成規則**（推測與 Opay 類似）:
1. 移除 `CheckMacValue` 參數
2. 依照參數名稱排序（字母順序）
3. 組合字串：`HashKey={HashKey}&參數1=值1&參數2=值2...&HashIV={HashIV}`
4. 進行 URL encode
5. 轉換為小寫
6. 進行 SHA256 hash
7. 轉換為大寫

### 3. 訂單鎖定機制

使用資料庫 `FOR UPDATE` 鎖定防止重複處理：
```php
BEGIN TRANSACTION;
SELECT * FROM servers_log WHERE orderid = ? FOR UPDATE;
// ... 處理邏輯
COMMIT;
```

### 4. 訂單狀態檢查

```php
// 確保訂單未被處理過
if ($datalist["stats"] != 0 && $_POST["mockpay"] != 1) {
    die("0");
}
```

### 5. IP 記錄

在 `funpoint_payok.php` 中記錄用戶 IP：
```php
$user_IP = get_real_ip();
```

---

## 錯誤處理

### 錯誤代碼對照表

| 錯誤代碼 | 錯誤訊息 | 觸發位置 |
|----------|----------|----------|
| 8000201 | 伺服器資料錯誤（foran 為空） | funpoint_next.php |
| 8000202 | 伺服器資料錯誤（serverid 為空） | funpoint_next.php |
| 8000203 | 伺服器資料錯誤（lastan 為空） | funpoint_next.php |
| 8000204 | 不明錯誤（找不到伺服器記錄） | funpoint_api.php |
| 8000206 | 金流錯誤（商店資訊不完整） | funpoint_api.php |
| 8000207 | 不明錯誤（找不到訂單記錄） | funpoint_api.php |
| 8000208 | 金流狀態有誤（訂單已處理） | funpoint_api.php |
| 8000301 | 資料錯誤（MerchantTradeNo 為空） | funpoint_payok.php |
| 8000302 | 不明錯誤（找不到訂單） | funpoint_payok.php |

### 回調錯誤處理

在 `funpoint_r.php` 中的錯誤會記錄到 `servers_log.errmsg`：

```php
// 找不到伺服器資料
$qud = $pdo->prepare("UPDATE servers_log SET errmsg = '找尋伺服器資料庫時發生錯誤' WHERE orderid = ?");

// 存入贊助幣失敗
$qud = $pdo->prepare("UPDATE servers_log SET errmsg = '存入贊助幣時發生錯誤' WHERE orderid = ?");

// 存入紅利幣失敗
$qud = $pdo->prepare("UPDATE servers_log SET errmsg = '存入紅利幣時發生錯誤' WHERE orderid = ?");
```

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
token              VARCHAR      安全 token
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

使用 `getSpecificBankPaymentInfo()` 函數取得 funpoint 銀行轉帳設定：
```php
$payment_info = getSpecificBankPaymentInfo($pdo, $lastan, 'funpoint');

// 返回格式
[
    'payment_config' => [
        'merchant_id' => '...',
        'hashkey'     => '...',
        'hashiv'      => '...'
    ]
]
```

---

## 測試流程

### 1. 測試環境設定

```php
// 在資料庫中設定測試模式
UPDATE servers SET
    gstats = 0,      // 信用卡測試環境
    gstats2 = 0,     // 其他支付測試環境
    gstats_bank = 0  // 銀行轉帳測試環境
WHERE auton = ?;
```

### 2. 模擬支付測試

可以使用 `mockpay` 參數進行模擬測試：
```php
$_POST["mockpay"] = 1;  // 允許重複處理訂單
```

### 3. 檢查回調

查看 `servers_log` 表確認：
- `stats` = 3（測試成功）
- `RtnMsg` = '模擬付款成功'

### 4. 驗證虛擬貨幣

連接遊戲資料庫檢查：
```sql
SELECT * FROM {paytable}
WHERE account = ?
ORDER BY create_time DESC;
```

---

## 注意事項

### 1. 跳板服務依賴

系統依賴外部跳板服務 `gohost.tw`，需確保：
- 跳板服務穩定運行
- 網路連線正常
- 跳板 API 版本相容

### 2. 資料庫連線

需要連接兩個資料庫：
- **本地資料庫**：訂單管理
- **遊戲資料庫**：虛擬貨幣發放

確保遊戲資料庫連線資訊正確且有寫入權限。

### 3. 事務處理

使用事務處理確保資料一致性：
- 訂單狀態更新
- 虛擬貨幣發放
- 活動獎勵發放

如果任何步驟失敗，會自動回滾。

### 4. 檢查碼驗證

雖然程式碼中生成了 `CheckMacValue`，但在回調處理中**未進行驗證**。建議加強安全性：
```php
// 建議在 funpoint_r.php 中加入驗證
$receivedCheckMacValue = $_REQUEST["CheckMacValue"];
$calculatedCheckMacValue = funpoint::generate($params, $HashKey, $HashIV);

if ($receivedCheckMacValue !== $calculatedCheckMacValue) {
    die("0");  // 驗證失敗
}
```

### 5. 自訂背景圖

支持自訂支付頁面背景：
```php
// 從資料庫取得自訂背景
SELECT custombg FROM servers WHERE auton = ?;

// 如果有設定
if (!empty($custombg)) {
    $bg = "assets/images/custombg/" . $custombg;
}
```

### 6. IP 記錄

所有支付都會記錄用戶 IP 位置，用於：
- 安全追蹤
- 詐騙防範
- 爭議處理

---

## 常見問題

### Q1: 訂單重複處理怎麼辦？

A: 系統使用 `FOR UPDATE` 鎖定機制防止重複處理。如果訂單已處理（`stats != 0`），回調會直接返回 "0"。

### Q2: 虛擬貨幣發放失敗如何處理？

A: 錯誤會記錄到 `servers_log.errmsg`，並且整個事務會回滾，訂單狀態不會更新為成功。

### Q3: 如何區分正式環境和測試環境？

A: 通過 `servers` 表中的環境標識：
- `gstats`: 信用卡環境
- `gstats2`: 其他支付環境
- `gstats_bank`: 銀行轉帳環境
- 0 = 測試環境，1 = 正式環境

### Q4: 繳費期限如何設定？

A: 繳費期限由 FunPoint 返回，儲存在 `servers_log.ExpireDate`，顯示在 `funpoint_payok.php` 頁面。

### Q5: 活動獎勵如何設定？

A: 在 `servers_gift` 表中設定：
1. 設定開關記錄（`pid = 'stat'`, `sizes = 1` 表示啟用）
2. 設定獎勵內容（金額區間、物品 ID、數量）
3. 活動首購禮需額外設定時間（`pid = 'time1'`, `pid = 'time2'`）

---

## 技術支援

如有技術問題，請檢查：
1. 錯誤日誌：`servers_log.errmsg`
2. 支付狀態：`servers_log.stats` 和 `servers_log.RtnMsg`
3. 網路連線：跳板服務和 FunPoint 的連接狀態
4. 資料庫權限：遊戲資料庫的寫入權限

---

## 版本記錄

- **v1.0** (2024): 初始版本，支援基本支付流程
- 功能包含：
  - 多種支付方式（信用卡、ATM、超商、ibon）
  - 虛擬貨幣自動發放
  - 紅利幣機制
  - 活動獎勵系統（滿額禮、首購禮、累積儲值、活動首購禮）
  - 自訂背景圖
  - 自訂商品名稱

---

*文件撰寫日期：2024-10-22*
*最後更新：2024-10-22*
