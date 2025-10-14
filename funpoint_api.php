<?php

include("myadm/include.php");
include_once('payment_class.php');
include_once('./pay_bank.php');

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'funpoint':
            $pdo = openpdo(); 	
            $query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
            $query->execute(array($_POST["lastan"]));   // 同ID功能
            
            if (!$server_log = $query->fetch()){
                web::err_responce('不明錯誤-8000207。');
            }
            if ($server_log["stats"] != 0){
                web::err_responce('金流狀態有誤-8000208。');
            }
            // 驗證token

            $query2 = $pdo->prepare("SELECT * FROM servers where auton=?");
            $query2->execute(array($_POST["foran"]));   // 同ID功能

            if(!$servers = $query2->fetch()){
                web::err_responce('不明錯誤-8000204。');
            }

            $env = $servers["gstats"];      // 信用卡環境
            $env2 = $servers["gstats2"];
            $env_bank = $servers["gstats_bank"];

            $paytype = $server_log['paytype'];
            if ($paytype == 5){
                if ($env == 1) {
                    $payment_url = "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5";
                    $MerchantID = $servers["MerchantID"];
                    $HashKey = $servers["HashKey"];
                    $HashIV = $servers["HashIV"];			
                } else {
                    $payment_url = "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
                    $MerchantID = "1000031";
                    $HashKey = "265flDjIvesceXWM";
                    $HashIV = "pOOvhGd1V2pJbjfX";
                }
            } else if ($paytype == 2) {
                $payment_url = "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5";
                // 使用新的 bank_funds 資料表取得 funpoint 銀行轉帳設定
                $payment_info = getSpecificBankPaymentInfo($pdo, $_POST["lastan"], 'funpoint');

                if ($payment_info && isset($payment_info['payment_config'])) {
                    $MerchantID = $payment_info['payment_config']['merchant_id'];
                    $HashKey = $payment_info['payment_config']['hashkey'];
                    $HashIV = $payment_info['payment_config']['hashiv'];
                }

                if ($env_bank != 1){
                    $payment_url = "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
                    $MerchantID = "1000031";
                    $HashKey = "265flDjIvesceXWM";
                    $HashIV = "pOOvhGd1V2pJbjfX";
                }
            }else{
                if ($env2 == 1) {    // 正式環境
                    $payment_url = "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5";
                    $MerchantID = $servers["MerchantID2"];
                    $HashKey = $servers["HashKey2"];
                    $HashIV = $servers["HashIV2"];
                } else {				
                    $payment_url = "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
                    $MerchantID = "1000031";
                    $HashKey = "265flDjIvesceXWM";
                    $HashIV = "pOOvhGd1V2pJbjfX";
                }
            }
            $forname = $servers["names"];

            if($MerchantID == "" || $HashKey == "" || $HashIV == "") web::err_responce("金流錯誤-8000206。", 0);

            $money = $server_log["money"];
            $pt = $server_log["paytype"];
            $stats = $server_log["stats"];
            $tradeno = $server_log["orderid"];
            $nowtime = date("Y/m/d H:i:s");

            switch($paytype) {
                case 1:
                    $ptt = "BARCODE";	  	
                    $csp = "BARCODE";
                    break;
                case 2:
                    $ptt = "ATM";
                    $csp = "ESUN";
                    break;
                case 3:
                    $ptt = "CVS";
                    $csp = "CVS";
                    break;
                case 4:
                    $ptt = "CVS";
                    $csp = "IBON";
                    break;
                case 5:
                    $ptt = "Credit";
                    $csp = "";
                    break;
                case 6:
                    $ptt = "WebATM";
                    $csp = "";
                    break;
                default:
                    die();
                    break;
            }

            $mycode = generateRandomString(8);
            $mycode2 = strtolower($mycode."niudunpaycode");
            $mycodepass = hash("sha256", $mycode2);
            $chksn = strtoupper($mycode.$mycodepass);

            $TradeDesc = "帳單中心";
            $ItemName = random_products($_POST["serverid"]);
            // $rurl = $weburl . "funpoint_r.php";
            $rurl = "https://gohost.tw/payment_background_funpoint_receive.php";
            // $rurl = "https://gohost.tw/szfu_test.php";
            // $rurl2 = $weburl . "funpoint_payok.php";
            $rurl2 = 'https://gohost.tw/payment_background_funpoint_receive_mid.php';

            // 以class方式取得檢查碼
            $CheckMacData['ChoosePayment'] = $ptt;
            $CheckMacData['ChooseSubPayment'] = $csp;
            $CheckMacData['ClientRedirectURL'] = $rurl2;
            $CheckMacData['EncryptType'] = 1;
            $CheckMacData['ItemName'] = $ItemName;
            $CheckMacData['MerchantID'] = $MerchantID;
            $CheckMacData['MerchantTradeDate'] = $nowtime;
            $CheckMacData['MerchantTradeNo'] = $tradeno;
            $CheckMacData['PaymentType'] = 'aio';
            $CheckMacData['ReturnURL'] = $rurl;
            $CheckMacData['TotalAmount'] = $money;
            $CheckMacData['TradeDesc'] = $TradeDesc;

            $CheckMacValue = funpoint::generate($CheckMacData, $HashKey, $HashIV);

            $sq2 = $pdo->prepare("update servers_log set CheckMacValue=?, forname=? where auton=?");
            $sq2->execute(array($CheckMacValue, $forname, $_POST["lastan"]));

            $payment_value['test'] = $_SESSION["serverid"];
            $payment_value['ChoosePayment'] = $ptt;
            $payment_value['ChooseSubPayment'] = $csp;
            $payment_value['EncryptType'] = '1';
            $payment_value['ItemName'] = $ItemName;
            $payment_value['MerchantID'] = $MerchantID;
            $payment_value['MerchantTradeDate'] = $nowtime;
            $payment_value['MerchantTradeNo'] = $tradeno;
            $payment_value['ClientRedirectURL'] = $rurl2;
            $payment_value['PaymentType'] = 'aio';
            $payment_value['ReturnURL'] = $rurl;
            $payment_value['TotalAmount'] = $money;
            $payment_value['TradeDesc'] = $TradeDesc;
            $payment_value['CheckMacValue'] = $CheckMacValue;

            foreach ($payment_value as $key => $value){
                $data['payment_value'][$key] = $value;
            }
            $data['success'] = true;
            $data['payment_url'] = $payment_url;
            $data['test'] = $env2;

            echo json_encode($data);
            break;
    }
}

?>