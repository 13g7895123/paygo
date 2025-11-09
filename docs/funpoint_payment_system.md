# Funpoint 金流支付系統說明文件

## 目錄
1. [系統概述](#系統概述)
2. [檔案結構](#檔案結構)
3. [詳細功能說明](#詳細功能說明)
4. [支付流程](#支付流程)
5. [支付類型](#支付類型)
6. [Domain 映射規則](#domain-映射規則)
7. [API 端點](#api-端點)
8. [資料結構](#資料結構)
9. [錯誤處理](#錯誤處理)
10. [安全性考量](#安全性考量)

---

## 系統概述

Funpoint 金流支付系統是一個用於處理多種支付方式的後端服務系統，主要負責：
- 接收來自 A 網站（主網站）的支付請求
- 與金流服務商 API 進行整合
- 處理支付回傳資料
- 支援多個網域的支付服務

### 支援的支付方式
- **CVS**：便利商店代碼繳費
- **ATM**：ATM 轉帳
- **Credit**：信用卡支付

### 支援的網域
- paygo.tw
- ezpay.mobi
- ro.paygo.tw
- tt.paygo.tw
- test.paygo.tw

---

## 檔案結構

```
.
├── payment_background_funpoint.php
├── payment_background_funpoint_payment.php
├── payment_background_funpoint_receive.php
├── payment_background_funpoint_receive_mid.php
└── web_class.php (外部引用)
```

### 檔案說明

| 檔案名稱 | 用途 | 主要功能 |
|---------|------|---------|
| `payment_background_funpoint.php` | 主要支付處理程式 | 接收支付請求、呼叫金流 API、產生支付表單 |
| `payment_background_funpoint_payment.php` | 支付資料輸出程式 | 輸出支付資料的 JSON 格式 |
| `payment_background_funpoint_receive.php` | 金流回傳接收程式 | 接收金流商的付款結果通知 |
| `payment_background_funpoint_receive_mid.php` | 中間接收頁面 | 處理金流回傳數值並轉發到主網站 |

---

## 詳細功能說明

### 1. payment_background_funpoint.php

**位置**：`payment_background_funpoint.php:1-131`

**主要功能**：
這是整個 Funpoint 支付系統的核心檔案，負責處理支付請求的完整流程。

#### 存取控制
```php
header("Access-Control-Allow-Origin: https://paygo.tw/");
header("Access-Control-Allow-Origin: https://ezpay.tw/");
// ... 其他允許的網域
```
- 限制只接受特定網域的訪問
- 包含 paygo.tw、ezpay.tw、ro.paygo.tw 等

#### 資料接收與驗證
**位置**：`payment_background_funpoint.php:15-39`

接收的 POST 參數：
- `foran`：前端參數
- `serverid`：伺服器 ID
- `lastan`：後端參數
- `token`：驗證令牌
- `domain`：目標網域

若任何必要參數缺失，系統會回傳對應的錯誤訊息：
- 8000201：foran 參數缺失
- 8000202：serverid 參數缺失
- 8000203：lastan 參數缺失
- 8000204：token 參數缺失
- 8000205：domain 參數缺失

#### Domain 映射
**位置**：`payment_background_funpoint.php:53-63`

將簡短的 domain 代碼轉換為完整的網域 URL：
```php
if ($domain == 'paygo'){
    $domain_url = 'paygo.tw';
}else if ($domain == 'tt.paygo'){
    $domain_url = 'tt.paygo.tw';
}
// ... 其他映射
```

#### API 呼叫
**位置**：`payment_background_funpoint.php:66-67`

```php
$api_url = 'https://'.$domain_url.'/funpoint_api.php?action=funpoint';
$api_data = json_decode(web::curl_api($api_url, $data), true);
```
- 向主網站的 `funpoint_api.php` 發送請求
- 取得支付所需的完整資料

#### 支付類型處理
**位置**：`payment_background_funpoint.php:75-103`

##### CVS（便利商店）
```php
if ($pay_type == 'CVS'){
    echo web::curl_api($api_data['payment_url'], $api_data['payment_value']);
}
```
- 直接將資料傳送到金流商 API
- 回傳繳費代碼資訊

##### ATM / Credit（ATM 轉帳 / 信用卡）
```php
elseif ($pay_type == 'ATM' || $pay_type == 'Credit'){
    // 建立 HTML 表單
    // 自動提交到金流商的支付頁面
}
```
- 動態產生 HTML 表單
- 包含所有必要的支付參數
- 使用 JavaScript 自動提交表單

---

### 2. payment_background_funpoint_payment.php

**位置**：`payment_background_funpoint_payment.php:1-7`

**主要功能**：
這是一個簡單的輔助程式，用於輸出支付資料的 JSON 格式。

```php
include_once('./payment_background_funpoint.php');
echo json_encode($api_data['payment_value']);
```

**用途**：
- 用於除錯或前端需要取得支付資料時
- 以 JSON 格式回傳完整的支付參數

---

### 3. payment_background_funpoint_receive.php

**位置**：`payment_background_funpoint_receive.php:1-32`

**主要功能**：
接收金流商的支付結果通知（伺服器對伺服器的回呼）。

#### 資料接收
```php
foreach ($_POST as $key => $value){
    $data[$key] = $value;
}
```
- 接收所有 POST 資料

#### 訂單編號尾碼判斷
**位置**：`payment_background_funpoint_receive.php:10-24`

```php
$order_no = $_REQUEST['MerchantTradeNo'];
$order_no_tail = substr($order_no, -2);
```

| 尾碼 | 對應網域 |
|-----|---------|
| 01 | paygo.tw |
| 02 | ezpay.mobi |
| 03 | ro.paygo.tw |
| 04 | tt.paygo.tw |
| 05 | test.paygo.tw |

#### 資料轉發
```php
echo web::curl_api('https://'.$domain_url.'/funpoint_r.php', $data);
```
- 將金流商的回傳資料轉發到對應網域的 `funpoint_r.php`
- 由主網站進行後續的訂單狀態更新

---

### 4. payment_background_funpoint_receive_mid.php

**位置**：`payment_background_funpoint_receive_mid.php:1-61`

**主要功能**：
這是一個中間接收頁面，用於接收金流商的回傳結果並轉發到主網站的前端頁面。

#### RtnCode 處理
**位置**：`payment_background_funpoint_receive_mid.php:10-26`

##### 便利商店代碼繳費（RtnCode: 10100073）
```php
if ($rtn_code == 10100073){
    $payment_reply['MerchantTradeNo'] = $_POST['MerchantTradeNo'];  // 訂單編號
    $payment_reply['RtnCode'] = $rtn_code;                          // 回傳代碼
    $payment_reply['ExpireDate'] = $_POST['ExpireDate'];            // 繳費期限
    $payment_reply['PaymentNo'] = $_POST['PaymentNo'];              // 繳費代碼
    $payment_reply['TradeAmt'] = $_POST['TradeAmt'];                // 交易金額
}
```

##### ATM 轉帳（RtnCode: 2）
```php
elseif ($rtn_code == 2){
    $payment_reply['MerchantTradeNo'] = $_POST['MerchantTradeNo'];  // 訂單編號
    $payment_reply['RtnCode'] = $rtn_code;                          // 回傳代碼
    $payment_reply['ExpireDate'] = $_POST['ExpireDate'];            // 繳費期限
    $payment_reply['BankCode'] = $_POST['BankCode'];                // 銀行代碼
    $payment_reply['vAccount'] = $_POST['vAccount'];                // 虛擬帳號
    $payment_reply['TradeAmt'] = $_POST['TradeAmt'];                // 交易金額
}
```

#### 自動表單提交
**位置**：`payment_background_funpoint_receive_mid.php:47-60`

```php
<form id="payment_data" method="post" action="https://<?=$domain_url;?>/funpoint_payok.php">
    <!-- 隱藏欄位 -->
</form>
<script type="text/javascript">
    document.getElementById('payment_data').submit();
</script>
```
- 建立 HTML 表單並自動提交
- 將支付結果傳送到主網站的 `funpoint_payok.php`
- 使用者會被導向到支付結果頁面

---

## 支付流程

### 完整流程圖

```
[使用者在主網站選擇商品並結帳]
         |
         v
[主網站 (paygo.tw 等) 發送支付請求]
         |
         | POST: foran, serverid, lastan, token, domain
         v
[payment_background_funpoint.php]
         |
         | 1. 驗證參數
         | 2. 映射 domain
         v
[呼叫主網站 API: funpoint_api.php]
         |
         | 回傳: payment_url, payment_value, 支付類型
         v
[根據支付類型處理]
         |
         +-------+-------+
         |               |
    [CVS 便利商店]   [ATM / Credit]
         |               |
         v               v
    [直接 API 呼叫]  [產生並提交表單]
         |               |
         v               v
    [金流商處理]      [導向金流商頁面]
         |               |
         +-------+-------+
                 |
                 v
        [金流商回傳結果]
                 |
         +-------+-------+
         |               |
    [伺服器端通知]    [前端重導向]
         |               |
         v               v
[receive.php]    [receive_mid.php]
         |               |
         v               v
    [funpoint_r.php]  [funpoint_payok.php]
         |               |
         v               v
[更新訂單狀態]      [顯示支付結果]
```

### 詳細步驟說明

#### 步驟 1：使用者發起支付請求
- 使用者在主網站選擇商品並進入結帳流程
- 主網站收集訂單資訊並準備支付參數

#### 步驟 2：主網站發送請求到 Go Host
- 主網站透過 POST 方式將資料傳送到 `payment_background_funpoint.php`
- 傳送的資料包含：foran, serverid, lastan, token, domain

#### 步驟 3：Go Host 處理請求
- 驗證所有必要參數
- 根據 domain 參數映射到正確的網域 URL
- 呼叫主網站的 API 取得完整的支付資料

#### 步驟 4：呼叫金流商 API
根據支付類型：
- **CVS**：直接呼叫金流商 API，取得繳費代碼
- **ATM / Credit**：產生 HTML 表單並自動提交到金流商頁面

#### 步驟 5：金流商處理支付
- 使用者在金流商頁面完成支付動作
- 金流商進行支付處理

#### 步驟 6：金流商回傳結果
- **伺服器端通知**（Server-to-Server）：金流商主動呼叫 `payment_background_funpoint_receive.php`
- **前端重導向**（Client Redirect）：使用者瀏覽器被導向到 `payment_background_funpoint_receive_mid.php`

#### 步驟 7：更新訂單狀態與顯示結果
- 伺服器端通知更新資料庫的訂單狀態
- 前端重導向讓使用者看到支付結果

---

## 支付類型

### 1. CVS（便利商店代碼繳費）

**特點**：
- 使用者取得繳費代碼
- 可在便利商店臨櫃繳費
- 有繳費期限

**回傳資料**：
- `MerchantTradeNo`：訂單編號
- `PaymentNo`：繳費代碼
- `ExpireDate`：繳費期限
- `TradeAmt`：交易金額
- `RtnCode`：10100073

**使用流程**：
1. 使用者選擇便利商店繳費
2. 系統產生繳費代碼
3. 使用者前往便利商店繳費
4. 金流商通知支付完成

### 2. ATM（虛擬帳號轉帳）

**特點**：
- 提供虛擬帳號
- 使用者透過 ATM 或網路銀行轉帳
- 有繳費期限

**回傳資料**：
- `MerchantTradeNo`：訂單編號
- `BankCode`：銀行代碼
- `vAccount`：虛擬帳號
- `ExpireDate`：繳費期限
- `TradeAmt`：交易金額
- `RtnCode`：2

**使用流程**：
1. 使用者選擇 ATM 轉帳
2. 系統產生虛擬帳號
3. 使用者透過 ATM 轉帳到虛擬帳號
4. 金流商通知支付完成

### 3. Credit（信用卡）

**特點**：
- 即時支付
- 需要輸入信用卡資訊
- 立即得知支付結果

**使用流程**：
1. 使用者選擇信用卡支付
2. 導向到金流商的信用卡輸入頁面
3. 使用者輸入信用卡資訊
4. 立即回傳支付結果

---

## Domain 映射規則

### Domain 代碼對應表

| Domain 代碼 | 完整網域 | 用途 | 訂單尾碼 |
|-----------|---------|-----|---------|
| paygo | paygo.tw | 主要網站 | 01 |
| ezpay | ezpay.mobi | EZPay 網站 | 02 |
| ro | ro.paygo.tw | RO 網站 | 03 |
| tt.paygo | tt.paygo.tw | TT 網站 | 04 |
| test.paygo | test.paygo.tw | 測試網站 | 05 |

### 訂單編號尾碼規則

**位置**：`payment_background_funpoint_receive.php:10-24`

系統透過訂單編號的最後兩碼來判斷該訂單屬於哪個網域：

```php
$order_no_tail = substr($order_no, -2);
```

**範例**：
- 訂單編號：`2024010112345601`，尾碼 `01` → paygo.tw
- 訂單編號：`2024010112345602`，尾碼 `02` → ezpay.mobi
- 訂單編號：`2024010112345605`，尾碼 `05` → test.paygo.tw

這個設計允許系統在接收金流回傳時，不需要額外參數就能判斷應該將資料傳送到哪個網域。

---

## API 端點

### 1. funpoint_api.php（主網站 API）

**URL**：`https://{domain_url}/funpoint_api.php?action=funpoint`

**請求方式**：POST

**請求參數**：
```php
[
    'foran' => $foran,
    'serverid' => $serverid,
    'lastan' => $lastan,
    'token' => $token,
    'domain' => $domain
]
```

**回傳資料結構**：
```json
{
    "payment_url": "https://金流商.com/api",
    "payment_value": {
        "ChoosePayment": "CVS|ATM|Credit",
        "ChooseSubPayment": "",
        "EncryptType": "1",
        "ItemName": "商品名稱",
        "MerchantID": "商店代號",
        "MerchantTradeDate": "2024/01/01 12:00:00",
        "MerchantTradeNo": "訂單編號",
        "ClientRedirectURL": "返回網址",
        "PaymentType": "aio",
        "ReturnURL": "回傳網址",
        "TotalAmount": "100",
        "TradeDesc": "交易描述",
        "CheckMacValue": "檢查碼"
    }
}
```

### 2. funpoint_r.php（訂單狀態更新）

**URL**：`https://{domain_url}/funpoint_r.php`

**請求方式**：POST

**請求參數**：金流商回傳的所有參數

**用途**：
- 接收金流商的伺服器端通知
- 更新資料庫中的訂單狀態
- 回傳 `1|OK` 給金流商確認收到通知

### 3. funpoint_payok.php（支付結果頁面）

**URL**：`https://{domain_url}/funpoint_payok.php`

**請求方式**：POST

**請求參數**：
```php
[
    'MerchantTradeNo' => '訂單編號',
    'RtnCode' => '回傳代碼',
    'PaymentNo' => '繳費代碼',       // CVS 使用
    'BankCode' => '銀行代碼',        // ATM 使用
    'vAccount' => '虛擬帳號',        // ATM 使用
    'ExpireDate' => '繳費期限',
    'TradeAmt' => '交易金額'
]
```

**用途**：
- 顯示支付結果給使用者
- 顯示繳費資訊（代碼或虛擬帳號）

---

## 資料結構

### 支付請求資料

```php
[
    'foran' => '前端參數',
    'serverid' => '伺服器 ID',
    'lastan' => '後端參數',
    'token' => '驗證令牌',
    'domain' => 'paygo|ezpay|ro|tt.paygo|test.paygo'
]
```

### 金流商支付參數

#### 共通參數
```php
[
    'ChoosePayment' => 'CVS|ATM|Credit',              // 支付方式
    'ChooseSubPayment' => '',                         // 子支付方式
    'EncryptType' => '1',                             // 加密類型
    'ItemName' => '商品名稱',                          // 商品名稱
    'MerchantID' => '商店代號',                        // 商店代號
    'MerchantTradeDate' => '2024/01/01 12:00:00',    // 交易時間
    'MerchantTradeNo' => '訂單編號',                   // 訂單編號
    'PaymentType' => 'aio',                           // 支付類型
    'ReturnURL' => '回傳網址',                         // 伺服器端通知網址
    'ClientRedirectURL' => '返回網址',                 // 前端返回網址
    'TotalAmount' => '100',                           // 交易金額
    'TradeDesc' => '交易描述',                         // 交易描述
    'CheckMacValue' => '檢查碼'                        // 檢查碼（安全驗證）
]
```

### 金流商回傳資料

#### CVS 便利商店
```php
[
    'MerchantTradeNo' => '訂單編號',
    'RtnCode' => '10100073',
    'PaymentNo' => '繳費代碼',
    'ExpireDate' => '2024/01/10 23:59:59',
    'TradeAmt' => '100'
]
```

#### ATM 轉帳
```php
[
    'MerchantTradeNo' => '訂單編號',
    'RtnCode' => '2',
    'BankCode' => '012',              // 銀行代碼
    'vAccount' => '98765432101234',   // 虛擬帳號
    'ExpireDate' => '2024/01/10',
    'TradeAmt' => '100'
]
```

---

## 錯誤處理

### 錯誤代碼表

| 錯誤代碼 | 說明 | 位置 |
|---------|-----|-----|
| 8000201 | foran 參數缺失 | `payment_background_funpoint.php:18` |
| 8000202 | serverid 參數缺失 | `payment_background_funpoint.php:23` |
| 8000203 | lastan 參數缺失 | `payment_background_funpoint.php:28` |
| 8000204 | token 參數缺失 | `payment_background_funpoint.php:33` |
| 8000205 | domain 參數缺失 | `payment_background_funpoint.php:38` |

### 錯誤處理方式

```php
web::err_responce('伺服器資料錯誤-8000201。');
```

這個方法會：
1. 記錄錯誤日誌
2. 回傳錯誤訊息給呼叫端
3. 終止程式執行

---

## 安全性考量

### 1. 存取控制
**位置**：`payment_background_funpoint.php:4-9`

```php
header("Access-Control-Allow-Origin: https://paygo.tw/");
```
- 使用 CORS 頭限制只允許特定網域存取
- 防止未授權的網站呼叫支付 API

### 2. 參數驗證
**位置**：`payment_background_funpoint.php:15-39`

- 驗證所有必要參數是否存在
- 缺少參數時立即回傳錯誤並終止執行
- 防止不完整的資料進入處理流程

### 3. Token 驗證
```php
'token' => $token
```
- 使用 token 驗證請求的合法性
- 防止偽造的支付請求

### 4. CheckMacValue 驗證
```php
'CheckMacValue' => '檢查碼'
```
- 金流商提供的檢查碼
- 用於驗證資料在傳輸過程中未被竄改
- 確保資料完整性

### 5. 訂單編號尾碼設計
- 透過訂單編號尾碼判斷網域
- 避免在回傳時傳遞敏感的網域資訊
- 降低資料被攔截時的風險

### 6. HTTPS 連線
```php
$api_url = 'https://'.$domain_url.'/funpoint_api.php';
```
- 所有 API 呼叫都使用 HTTPS
- 確保傳輸過程中的資料加密

---

## 維護與除錯

### Debug 模式

程式碼中保留了多處 DEBUG 註解：

```php
// DEBUG
// echo json_encode($data);
// die;
```

在開發或除錯時，可以取消這些註解來：
- 檢查傳遞的資料結構
- 驗證 API 回傳的內容
- 追蹤資料流向

### 建議的除錯步驟

1. **檢查資料接收**
   - 在 `payment_background_funpoint.php:50` 取消註解
   - 確認從主網站接收到的資料是否正確

2. **檢查 API 回傳**
   - 在 `payment_background_funpoint.php:70` 取消註解
   - 確認 API 回傳的支付資料結構

3. **檢查金流商回傳**
   - 在 `payment_background_funpoint_receive_mid.php:7` 取消註解
   - 確認金流商回傳的資料內容

### 日誌記錄建議

建議在以下位置增加日誌記錄：
- 接收到支付請求時
- 呼叫 API 前後
- 接收到金流商通知時
- 發生錯誤時

---

## 技術依賴

### 外部類別

#### web_class.php

提供以下方法：

1. **web::err_responce($message)**
   - 錯誤回應處理
   - 記錄錯誤並回傳訊息

2. **web::curl_api($url, $data)**
   - API 呼叫方法
   - 使用 cURL 發送 POST 請求
   - 回傳 API 回應結果

---

## 改進建議

### 1. 程式碼優化
- 將 domain 映射邏輯抽取為獨立函數
- 將支付類型處理封裝為類別方法
- 增加更完整的錯誤處理機制

### 2. 安全性增強
- 實作 CSRF Token 驗證
- 增加 IP 白名單檢查
- 實作請求頻率限制

### 3. 可維護性
- 將設定資料（domain 映射、錯誤代碼）移到設定檔
- 增加完整的日誌記錄機制
- 建立自動化測試

### 4. 監控與告警
- 實作支付失敗率監控
- 建立異常交易告警機制
- 記錄 API 回應時間

---

## 附錄

### A. 測試資料範例

#### 測試支付請求
```php
$test_data = [
    'foran' => 'test_foran',
    'serverid' => '1',
    'lastan' => 'test_lastan',
    'token' => 'test_token_123456',
    'domain' => 'test.paygo'
];
```

#### 測試訂單編號
```
paygo.tw:      2024010112345601
ezpay.mobi:    2024010112345602
ro.paygo.tw:   2024010112345603
tt.paygo.tw:   2024010112345604
test.paygo.tw: 2024010112345605
```

### B. 常見問題

#### Q1: 支付後沒有收到回傳通知？
- 檢查金流商設定的 ReturnURL 是否正確
- 確認伺服器可以接收來自金流商的連線
- 檢查防火牆設定

#### Q2: 訂單狀態沒有更新？
- 檢查 `payment_background_funpoint_receive.php` 是否正常執行
- 確認訂單編號尾碼判斷邏輯正確
- 檢查 `funpoint_r.php` 的執行日誌

#### Q3: 跨域存取錯誤？
- 確認請求來源在允許的網域清單中
- 檢查 CORS 頭設定是否正確

---

## 文件版本

- **版本**：1.0
- **最後更新**：2025-11-07
- **維護者**：技術團隊

---

## 聯絡資訊

如有任何問題或建議，請聯絡技術支援團隊。

---

**本文件涵蓋的檔案**：
- payment_background_funpoint.php
- payment_background_funpoint_payment.php
- payment_background_funpoint_receive.php
- payment_background_funpoint_receive_mid.php

**相關系統**：
- 主網站支付系統
- 金流商 API
- 訂單管理系統
