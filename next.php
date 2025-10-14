<?php
    include("myadm/include.php");
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
				$gurl = "https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5";
				$MerchantID = $sqd["MerchantID"];
				$HashKey = $sqd["HashKey"];
				$HashIV = $sqd["HashIV"];			
			} else {
				$gurl = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
				$MerchantID = "2000132";
				$HashKey = "5294y06JbISpM5x9";
				$HashIV = "v77hoKGq4kWxNNIS";
			}
		} else if ($paytype == 2) {	// 銀行轉帳
			$gurl = "https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5";

			// 使用新的 bank_funds 資料表取得 ecpay 銀行轉帳設定
			$payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'ecpay');
				
			if ($payment_info && isset($payment_info['payment_config'])) {
				$MerchantID = $payment_info['payment_config']['merchant_id'];
				$HashKey = $payment_info['payment_config']['hashkey'];
				$HashIV = $payment_info['payment_config']['hashiv'];
			}

			// 模擬環境
			if ($gstats_bank != 1){
				$gurl = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
				$MerchantID = "2000132";
				$HashKey = "5294y06JbISpM5x9";
				$HashIV = "v77hoKGq4kWxNNIS";
			}
		}else {
			if ($gstats2 == 1) {
				$gurl = "https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5";
				$MerchantID = $sqd["MerchantID2"];
				$HashKey = $sqd["HashKey2"];
				$HashIV = $sqd["HashIV2"];
			} else {				
				$gurl = "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5";
				$MerchantID = "2000132";
				$HashKey = "5294y06JbISpM5x9";
				$HashIV = "v77hoKGq4kWxNNIS";
			}
		}
	

    $forname = $sqd["names"];

    if($MerchantID == "" || $HashKey == "" || $HashIV == "") alert("金流錯誤-8000206。", 0);
    
    $money = $datalist["money"];
    $pt = $datalist["paytype"];
    $stats = $datalist["stats"];
    $tradeno = $datalist["orderid"];
    $nowtime = date("Y/m/d H:i:s");
    
	  switch($pt) {
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
	  
	  
	  $TradeDesc = "贊助中心";
	  $ItemName = random_products($_SESSION["serverid"]);
	  $rurl = $weburl."r.php";
	  $rurl2 = $weburl."payok.php";
	  
	  $CheckMacValue = "HashKey=".$HashKey."&ChoosePayment=".$ptt."&ChooseSubPayment=".$csp."&ClientRedirectURL=".$rurl2."&EncryptType=1&ItemName=".$ItemName."&MerchantID=".$MerchantID."&MerchantTradeDate=".$nowtime."&MerchantTradeNo=".$tradeno."&PaymentType=aio&ReturnURL=".$rurl."&TotalAmount=".$money."&TradeDesc=".$TradeDesc."&HashIV=".$HashIV."";
	  $CheckMacValue = urlencode($CheckMacValue);
	  $CheckMacValue = strtolower($CheckMacValue);
	  $CheckMacValue = str_replace('%2d', '-', $CheckMacValue);
    $CheckMacValue = str_replace('%5f', '_', $CheckMacValue);
    $CheckMacValue = str_replace('%2e', '.', $CheckMacValue);
    $CheckMacValue = str_replace('%21', '!', $CheckMacValue);
    $CheckMacValue = str_replace('%2a', '*', $CheckMacValue);
    $CheckMacValue = str_replace('%28', '(', $CheckMacValue);
    $CheckMacValue = str_replace('%29', ')', $CheckMacValue);
    $CheckMacValue = hash('sha256', $CheckMacValue);
    $CheckMacValue = strtoupper($CheckMacValue);
    
    $sq2 = $pdo->prepare("update servers_log set CheckMacValue=?, forname=? where auton=?");
    $sq2->execute(array($CheckMacValue, $forname, $_SESSION["lastan"]));
    
?>
<body>
<form id="fff" method="post" action="<?=$gurl?>">	
	<input type="hidden" name="ChoosePayment" value="<?=$ptt?>">
	<input type="hidden" name="ChooseSubPayment" value="<?=$csp?>">
	<input type="hidden" name="EncryptType" value="1">
	<input type="hidden" name="ItemName" value="<?=$ItemName?>">
	<input type="hidden" name="MerchantID" value="<?=$MerchantID?>">
	<input type="hidden" name="MerchantTradeDate" value="<?=$nowtime?>">
	<input type="hidden" name="MerchantTradeNo" value="<?=$tradeno?>">	
	<input type="hidden" name="ClientRedirectURL" value="<?=$rurl2?>">	
	<input type="hidden" name="PaymentType" value="aio">	
	<input type="hidden" name="ReturnURL" value="<?=$rurl?>">	
	<input type="hidden" name="TotalAmount" value="<?=$money?>">
	<input type="hidden" name="TradeDesc" value="<?=$TradeDesc?>">
	<input type="hidden" name="CheckMacValue" value="<?=$CheckMacValue?>">	
</form>
</body>
<script type="text/javascript">
	document.getElementById('fff').submit();
</script>