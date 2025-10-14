<?php
    include("myadm/include.php");
	if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
	if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
	if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);

	if($_SESSION["Buyer_Name"] == "") header('Location: gomypay_get.php');
	if($_SESSION["Buyer_Telm"] == "") header('Location: gomypay_get.php');
	if($_SESSION["Buyer_Mail"] == "") header('Location: gomypay_get.php');
	$Buyer_Name = _s("Buyer_Name");
	$Buyer_Telm = _s("Buyer_Telm");
	$Buyer_Mail = _s("Buyer_Mail");
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
				$gurl = "https://n.gomypay.asia/ShuntClass.aspx";		
			} else {
				$gurl = "https://n.gomypay.asia/TestShuntClass.aspx";
			}
			$CustomerId = $sqd["gomypay_shop_id"];
			$Str_Check = $sqd["gomypay_key"];
		} else {
			if ($gstats2 == 1) {
				$gurl = "https://n.gomypay.asia/ShuntClass.aspx";
			} else {				
				$gurl = "https://n.gomypay.asia/TestShuntClass.aspx";
			}
			$CustomerId = $sqd["gomypay_shop_id2"];
			$Str_Check = $sqd["gomypay_key2"];
		}
	

    $forname = $sqd["names"];

    if($CustomerId == "" || $Str_Check == "") alert("金流錯誤-8000206。", 0);
    
    $money = $datalist["money"];
    $pt = $datalist["paytype"];
    $stats = $datalist["stats"];
    $tradeno = $datalist["orderid"];
    $nowtime = date("Y/m/d H:i:s");
    
	  switch($pt) {
	  	case 1:
		$Send_Type = "2";

	  	break;
	  	case 2:
		$Send_Type = "4";
	  	break;
	  	case 3:
		$Send_Type = "6";
	  	break;
		case 30:
		$Send_Type = "6";
		$StoreType = "0";
		break;
		case 31:
		$Send_Type = "6";
		$StoreType = "1";
		break;
		case 32:
		$Send_Type = "6";
		$StoreType = "2";
		break;
		case 33:
		$Send_Type = "6";
		$StoreType = "3";
		break;
	  	case 5:
	  	$Send_Type = "0";
	  	break;
	  	case 6:
		$Send_Type = "3";
	  	break;
	  	default:
	  	die();
	  	break;
	  }
	  
	  $TradeDesc = "贊助中心";
	  $ItemName = random_products($_SESSION["serverid"]);
	  $rurl = $weburl."gomypay_r.php";
	  $rurl2 = $weburl."gomypay_payok.php";

    $sq2 = $pdo->prepare("update servers_log set forname=? where auton=?");
    $sq2->execute(array($forname, $_SESSION["lastan"]));
    
?>
<body>
<form id="fff" method="post" action="<?=$gurl?>">	
    <input type="hidden" name="Send_Type" value="<?=$Send_Type?>">
    <input type="hidden" name="Pay_Mode_No" value="2">
	<input type="hidden" name="CustomerId" value="<?=$CustomerId?>">
	<input type="hidden" name="Order_No" value="<?=$tradeno?>">
	<input type="hidden" name="Amount" value="<?=$money?>">
	<input type="hidden" name="TransCode" value="00">	
	<input type="hidden" name="Callback_Url" value="<?=$rurl?>">
	<input type="hidden" name="Return_url" value="<?=$rurl2?>">
	<input type="hidden" name="Buyer_Name" value="<?=$Buyer_Name?>">
	<input type="hidden" name="Buyer_Telm" value="<?=$Buyer_Telm?>">
	<input type="hidden" name="Buyer_Mail" value="<?=$Buyer_Mail?>">
	<input type="hidden" name="Buyer_Memo" value="<?=$ItemName?>">
	<input type="hidden" name="StoreType" value="<?=$StoreType?>">
	
</form>
</body>
<script type="text/javascript">
	document.getElementById('fff').submit();
</script>