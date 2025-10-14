<?php

include("include.php");

// check_login();

check_login_share();

$nowtime = date("Y/m/d H:i:s");

if(empty($an = _r("an"))) alert("贊助編號錯誤。", 0);



$pdo = openpdo();

$qq    = $pdo->query("SELECT * FROM servers_log where auton=".$an."");

if(!$datainfo = $qq->fetch()) alert("讀取失敗。", "close");



if ($datainfo["stats"] != 0 && $datainfo["stats"] != 2) alert("付款狀態不符。", "close");



switch ($datainfo["pay_cp"]) {

    case "ecpay":

      // 綠界

      $tourl = "r.php";

      $params = [

          'MerchantTradeNo' => $datainfo["orderid"],

          'RtnCode' => 1,     // 更新資料前才更新狀態碼

          'RtnMsg' => '模擬付款成功',

          'CheckMacValue' => 'system',

          'TradeAmt' => $datainfo["money"],

          'PaymentDate' => $nowtime,

          'PaymentTypeChargeFee' => 0

      ];

    break;

    case "ebpay":

      // 藍新

    $foran = $datainfo["foran"];

    $result = [

        'MerchantOrderNo' => $datainfo["orderid"],

        'CheckCode' => 'system',

        'Amt' => $datainfo["money"],

        'PayTime' => $nowtime

    ];



    require_once('../mwt-newebpay_sdk.php');

    $tradeInfo = [
        'Status' => 'SuCCESS',
        'Message' => '模擬付款成功',
        'Result' => $result,
    ];

    $tourl = "ebpay_r.php?an=".$foran;

    $params = [

      'Status' => 'SUCCESS',

      'TradeInfo' => trim(bin2hex(openssl_encrypt(addpadding(json_encode($tradeInfo)), 'aes-256-cbc', 'mockPayHashKey', OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, 'mockPayHashIV')))

    ];

    break;

    case "pchome":

      // 支付連

      $result = [

          'status' => 'S',

          'order_id' => $datainfo["orderid"],

          'trade_amount' => $datainfo["money"],

          'pay_date' => $nowtime,

          'pp_fee' => 0

      ];

      $tourl = "pchome_r.php";

      $params = [

          'notify_type' => 'order_confirm',

          'notify_message' => json_encode($result)

      ];

    break;

    case "gomypay":

      // 萬事達

      $tourl = "gomypay_r.php";

      $params = [

          'e_orderno' => $datainfo["orderid"],

          'result' => 1,  // 更新資料前才更新狀態碼

          'ret_msg' => '模擬付款成功',

          'str_check' => 'system',

          'e_money' => $datainfo["money"],

          'e_date' => chtimed($nowtime),

          'e_time' => chtimet($nowtime)

      ];

    break;

    case "smilepay":

      // 速買配

      $tourl = "smilepay_r.php";

      $params = [

          'Data_id' => $datainfo["orderid"],

          'result' => 1,

          'Errdesc' => '模擬付款成功',

          'str_check' => 'system',

          'Amount' => $datainfo["money"],

          'Process_date' => $nowtime

      ];

      $endstr = '<Roturlstatus>OK</Roturlstatus>';

    break;

    case "funpoint":

      // 綠界

      $tourl = "funpoint_r.php";

      $params = [

          'MerchantTradeNo' => $datainfo["orderid"],

          'RtnCode' => 1,   // 更新資料前才更新狀態碼

          'RtnMsg' => '模擬付款成功',

          'CheckMacValue' => 'system',

          'TradeAmt' => $datainfo["money"],

          'PaymentDate' => $nowtime,

          'PaymentTypeChargeFee' => 0

      ];

    break;

    case "szfu":

      // 數支付
      $tourl = "szfu_r.php";
      $params = [
          'TradeNo' => $datainfo["orderid"],
          'RtnCode' => 1,       // 更新資料前才更新狀態碼
          'RtnMsg' => '模擬付款成功',
          // 'CheckMacValue' => 'system',
          'Price' => $datainfo["money"],
          'PayDate' => $nowtime,
          // 'PaymentTypeChargeFee' => 0
      ];
      $endstr = 1;
    break;

    default:

        

    break;

}



$params["mockpay"] = 1;

$res = curl($weburl.$tourl, $params, 1, 1);

if ($res == "1|OK" || $res == $endstr) alert("模擬付款完成。", "reloadclose");

else alert("模擬付款失敗。".$res, "close");

