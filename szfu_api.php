<?php

include("myadm/include.php");
include_once('./web_class.php');
include_once('./payment_class.php');

if (isset($_GET['action'])){
    switch ($_GET['action']){
        case 'szfu':
            $pdo = openpdo(); 	
            $query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
            $query->execute(array($_POST["lastan"]));
            
            if (!$server_log = $query->fetch()){
                web::err_responce('不明錯誤-8000207。');
            }
            if ($server_log["stats"] != 0){
                web::err_responce('金流狀態有誤-8000208。');
            }
            // 驗證token
            if(empty($server_log['token'])){
                web::err_responce('驗證有誤-8000209。');
            }else{
                $pdo = openpdo(); 
                $update_token_time = $pdo->prepare("UPDATE servers_log SET is_used=? WHERE auton=? AND token=?");
                $update_token_time->execute(array(date("Y-m-d H:i:s"), $_POST["lastan"], $_POST["token"]));
            }

            $query2 = $pdo->prepare("SELECT * FROM servers where auton=?");
            $query2->execute(array($_POST["foran"]));

            if(!$servers = $query2->fetch()){
                web::err_responce('不明錯誤-8000204。');
            }

            $env = $servers["gstats"];
            $env2 = $servers["gstats2"];

            $paytype = $server_log['paytype'];
            if ($paytype == 5){     // 信用卡繳費，szfu用不到

            }else{
                if ($env2 == 1) {    // 正式環境
                    $payment_url = "https://skp.skysatisfyp.asia";
                    // $MerchantID = $servers["MerchantID2"];
                    $HashKey = $servers["szfupay_key2"];
                    $HashIV = $servers["szfupay_shop_id2"];
                } else {				
                    $payment_url = "https://skpts.skysatisfyp.asia";
                    $HashKey = "04cb0721464090de2f05dfb3785ae8aa";
                    $HashIV = "DABUDYFWABTHP449";
                    $MerBanks = "IBONS";
                }
            }

            $paytype = $server_log["paytype"];    // 支付方式(2=>銀行轉帳，3=>超商代碼)

            switch($paytype) {
                case 2:     // 銀行轉帳
                    $paytype_name = "ATM";	  	
                    $MerBanks = "CATHAY";
                    break;
                case '30':
                    $paytype_name = "CVS";
                    $MerBanks = "FAMILYS";
                    break;
                case '31':
                    $paytype_name = "CVS";
                    $MerBanks = "OKMART";
                    break;
                case '32':
                    $paytype_name = "CVS";
                    $MerBanks = "HILIFE";
                    break;
                case '33':
                    $paytype_name = "CVS";
                    $MerBanks = "IBONS";
                    break;
                default:
                    web::err_responce('支付方式錯誤');
                    break;
            }

            // 測試環境只能選用IBON
            if ($env2 != 1 && $paytype_name == 'CVS'){
                $pdo = openpdo(); 
                $update_token_time = $pdo->prepare("UPDATE servers_log SET paytype=? WHERE auton=? AND token=?");
                $update_token_time->execute(array('33', $_POST["lastan"], $_POST["token"]));

                $MerBanks = "IBONS";
            }

            $payment_value['HashID'] = $HashIV;
            if ($paytype_name == 'CVS') $payment_value['MerBanks'] = $MerBanks;
            $payment_value['MerProductID'] = "P00" . rand(1, 9);
            $payment_value['MerTradeID'] = $server_log["orderid"]; 
            $payment_value['MerUserID'] = $server_log['gameid'];
            $payment_value['PayType'] = $paytype_name; 
            $payment_value['Price'] = $server_log['money'];     
            $payment_value['Validate'] = szfu::validate($payment_value, $HashIV, $HashKey);

            foreach ($payment_value as $key => $value){
                $data['payment_value'][$key] = $value;
            }
            $data['success'] = true;
            $data['payment_url'] = $payment_url;
            $data['test'] = $env2;

            echo json_encode($data);
            break;
        case 'receive':     // 繳費完成
            if (isset($_POST['TradeNo'])) $TradeNo = $_POST['TradeNo'];
            if (isset($_POST['Price'])) $Price = $_POST['Price'];
            if (isset($_POST['RtnCode'])) $RtnCode = $_POST['RtnCode'];
            if (isset($_POST['RtnMsg'])) $RtnMsg = $_POST['RtnMsg'];
            if (isset($_POST['PayDate'])) $PayDate = $_POST['PayDate'];

            $pdo = openpdo();
            $query = $pdo->prepare("SELECT * FROM servers_log WHERE orderid=? AND money=? AND rmoney=0");
            $query->execute(array($TradeNo, $Price));
            $return['serverlog'] = $query->fetch();

            if ($return['serverlog'] != false){     // 有查到資料
                // 更新資料
                $query = $pdo->prepare("UPDATE servers_log SET rmoney=?, RtnCode=?, RtnMsg=?, paytimes=?, stats=? WHERE orderid=?");
                $query->execute(array($Price, $RtnCode, $RtnMsg, $PayDate, 1, $TradeNo));
            }
            echo 1;
            // echo json_encode($return);
            break;
        case 'api_test':
            if (isset($_POST)){
                foreach($_POST as $key => $value){
                    $pdo = openpdo();
                    $query = $pdo->prepare('update test set param=?, value=?');
                    $query->execute(array($key,$value));
                }
                echo json_encode($_POST);
            }
            
            break;
    }   
}


?>