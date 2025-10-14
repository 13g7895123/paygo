<?php
include("myadm/include.php");
require_once('mwt-newebpay_sdk.php');
include_once('./pay_bank.php');

	if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
	if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
	if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);
	
	//read
		$pdo = openpdo(); 	

    $query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
		$query->execute(array($_SESSION["lastan"]));
		if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
		if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);
		$paytype = $datalist["paytype"];

		$sq = $pdo->prepare("SELECT * FROM servers where auton=?");
		$sq->execute(array($_SESSION["foran"]));
		if(!$sqd = $sq->fetch()) alert("不明錯誤-8000204。", 0);
	
		$gstats = $sqd["gstats"];
		$gstats2 = $sqd["gstats2"];
    $gstats_bank = $sqd["gstats_bank"];

		if ($paytype == 5) {
			if ($gstats == 1) {
				$gurl = "https://core.newebpay.com/MPG/mpg_gateway";
			} else {
				$gurl = "https://ccore.newebpay.com/MPG/mpg_gateway";
			}
      $MerchantID = $sqd["MerchantID"];
      $HashKey = $sqd["HashKey"];
      $HashIV = $sqd["HashIV"];	
		} else if ($paytype == 2) {	// 銀行轉帳
      // 使用新的 bank_funds 資料表取得 ebpay 銀行轉帳設定
      $payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'ebpay');
      
      if ($payment_info && isset($payment_info['payment_config'])) {
        $MerchantID = $payment_info['payment_config']['merchant_id'];
        $HashKey = $payment_info['payment_config']['hashkey'];
        $HashIV = $payment_info['payment_config']['hashiv'];
      }

      $gurl = ($gstats_bank == 1) 
      ? "https://core.newebpay.com/MPG/mpg_gateway"   // 正式
      : "https://ccore.newebpay.com/MPG/mpg_gateway"; // 模擬
      
    } else {
			if ($gstats2 == 1) {
				$gurl = "https://core.newebpay.com/MPG/mpg_gateway";
			} else {				
				$gurl = "https://ccore.newebpay.com/MPG/mpg_gateway";
			}
      $MerchantID = $sqd["MerchantID2"];
      $HashKey = $sqd["HashKey2"];
      $HashIV = $sqd["HashIV2"];
  }
	
    $VER = "1.5";
    $forname = $sqd["names"];
    
    if($MerchantID == "" || $HashKey == "" || $HashIV == "") alert("金流錯誤-8000206。", 0);

    $query    = $pdo->prepare("SELECT * FROM servers_log where auton=?");
    $query->execute(array($_SESSION["lastan"]));
    if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
    if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);
    
    
    $money = $datalist["money"];
    $pt = $datalist["paytype"];
    $stats = $datalist["stats"];
    $tradeno = $datalist["orderid"];
    $nowtime = date("Y/m/d H:i:s");

    $pcredit = 0;
    $pvacc = 0;
    $pcvs = 0;
    $pbarcode = 0;

	  switch($pt) {
	  	case 1:
	  	$pbarcode = 1;
	  	break;
	  	case 2:
	  	$pvacc = 1;
	  	break;
	  	case 3:
      $pcvs = 1;
	  	break;
	  	case 4:

	  	break;
	  	case 5:
	  	$pcredit = 1;
	  	break;
	  	default:
	  	die();
	  	break;
	  }
	  
	  
	  $TradeDesc = "繳款中心";
	  $ItemName = random_products($_SESSION["serverid"]);
	  $rurl = $weburl."ebpay_r.php?an=".$_SESSION["foran"];
    $rurl2 = $weburl."ebpay_payok.php?an=".$_SESSION["foran"];
    
    $trade_info_arr = array(
        'MerchantID' => $MerchantID,
        'RespondType' => 'JSON',
        'TimeStamp' => time(),
        'Version' => $VER,
        'MerchantOrderNo' => $tradeno,
        'Amt' => $money,
        'ItemDesc' => $ItemName,
        'CVS' => $pcvs,
        'BARCODE' => $pbarcode,
        'VACC' => $pvacc,
        'CREDIT'=>$pcredit,
      //  'ReturnURL' => $ReturnURL, //支付完成 返回商店網址
        'NotifyURL' => $rurl, //支付通知網址
        'CustomerURL' =>$rurl2, //商店取號網址
      //  'ClientBackURL' => $ClientBackURL , //支付取消 返回商店網址
        'ExpireDate' => date("Y-m-d" , mktime(0,0,0,date("m"),date("d")+7,date("Y")))
    );
    
        $TradeInfo = create_mpg_aes_encrypt($trade_info_arr, $HashKey, $HashIV);
        $SHA256 = strtoupper(hash("sha256", SHA256($HashKey,$TradeInfo,$HashIV)));

        $sq2 = $pdo->prepare("update servers_log set CheckMacValue=?, forname=? where auton=?");
        $sq2->execute(array($SHA256, $forname, $_SESSION["lastan"]));
    
        echo CheckOut($gurl,$MerchantID,$TradeInfo,$SHA256,$VER);
    

?>