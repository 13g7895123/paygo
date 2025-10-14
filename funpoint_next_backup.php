<?php

    include("myadm/include.php");

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

	if ($paytype == 5) {
		if ($gstats == 1) {
			$gurl = "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5";
			$MerchantID = $sqd["MerchantID"];
			$HashKey = $sqd["HashKey"];
			$HashIV = $sqd["HashIV"];			
		} else {
			$gurl = "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
			$MerchantID = "1000031";
			$HashKey = "265flDjIvesceXWM";
			$HashIV = "pOOvhGd1V2pJbjfX";
		}
	} else {
		if ($gstats2 == 1) {
			$gurl = "https://payment.funpoint.com.tw/Cashier/AioCheckOut/V5";
			$MerchantID = $sqd["MerchantID2"];
			$HashKey = $sqd["HashKey2"];
			$HashIV = $sqd["HashIV2"];
		} else {				
			$gurl = "https://payment-stage.funpoint.com.tw/Cashier/AioCheckOut/V5";
			$MerchantID = "1000031";
			$HashKey = "265flDjIvesceXWM";
			$HashIV = "pOOvhGd1V2pJbjfX";
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

	$mycode = generateRandomString(8);
	$mycode2 = strtolower($mycode."niudunpaycode");
	$mycodepass = hash("sha256", $mycode2);
	$chksn = strtoupper($mycode.$mycodepass);

	$TradeDesc = "帳單中心";
	$ItemName = random_products();
	$rurl = $weburl . "funpoint_r.php";
	$rurl2 = $weburl . "funpoint_payok.php";

// 	  $rurl = $fake_weburl . "payment_background.php?cp=funpoint&chksn=".$chksn;

// 	  $rurl2 = $fake_weburl . "payment_payok.php?cp=funpoint&chksn=".$chksn;

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

    $jsondata = [];
    $jsondata["gurl"] = $gurl;
    $jsondata["ptt"] = $ptt;
    $jsondata["csp"] = $csp;
    $jsondata["ItemName"] = $ItemName;
    $jsondata["nowtime"] = $nowtime;
    $jsondata["tradeno"] = $tradeno;
    $jsondata["MerchantID"] = $MerchantID;
    $jsondata["rurl2"] = $rurl2;
    $jsondata["rurl"] = $rurl;
    $jsondata["money"] = $money;
    $jsondata["TradeDesc"] = $TradeDesc;
    $jsondata["CheckMacValue"] = $CheckMacValue;

    $sq3 = $pdo->prepare("INSERT INTO transtopay (json, sn, types) VALUES (?, ?, ?)");
    $sq3->execute(
        [
            json_encode($jsondata),
            $chksn,
            'funpoint'
        ]

    );
    $id = $pdo->lastInsertId();

    // header('Location: '  . $transpay ."?payreturnid=".$id."&chksn=".$chksn);
    // header('Location: '  . $weburl . "funpoint_r.php");
    // exit;

?>

<body>

<form id="fff" method="post" action="https://gohost.tw/payment_background_funpoint.php">	
<!-- <form id="fff" method="post" action="<?=$gurl?>">	 -->
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
	<input type="hidden" name="gurl" value="<?=$gurl?>">	
</form>

</body>

<script type="text/javascript">
	document.getElementById('fff').submit();
</script>