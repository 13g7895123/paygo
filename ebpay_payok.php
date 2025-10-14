<?php
include("myadm/include.php");
require_once('mwt-newebpay_sdk.php');
include_once('./pay_bank.php');
include_once('./web_class.php');

$foran = $_GET["an"];
if(!$foran) die("資料錯誤-8000299。");

$pdo = openpdo(); 	
		
$sq    = $pdo->prepare("SELECT * FROM servers where auton=?");
$sq->execute(array($foran));
if(!$sqd = $sq->fetch()) die("不明錯誤-8000298。");
$MerchantID = $sqd["MerchantID2"];
$HashKey = $sqd["HashKey2"];
$HashIV = $sqd["HashIV2"];

// 先透過 $orderid 取得訂單資訊，檢查是否為銀行轉帳
if (!empty($orderid)) {
    $order_query = $pdo->prepare("SELECT auton, paytype, is_bank, pay_cp FROM servers_log WHERE auton = :orderid");
    $order_query->bindValue(':orderid', $orderid, PDO::PARAM_STR);
    $order_query->execute();
    
    $order_info = $order_query->fetch(PDO::FETCH_ASSOC);
    if ($order_info && $order_info['paytype'] == 2) {
        // 這是銀行轉帳訂單，取得對應的第三方金流資料
        $payment_auton = $order_info['auton'];
        $payment_type = $order_info['pay_cp']; // 例如: smilepay, ecpay, gomypay 等
        
        // 使用 pay_bank.php 的函數取得詳細金流資訊
        $complete_payment_info = getCompletePaymentInfo($pdo, $payment_auton);
		// print_r($complete_payment_info);
        $specific_payment_info = null;
        
        if (!empty($payment_type)) {
            $specific_payment_info = getSpecificBankPaymentInfo($pdo, $payment_auton, $payment_type);
        }
        
        if ($specific_payment_info) {
            // 可以在這裡覆蓋原本的支付設定，改用 bank_funds 的資料
            if (isset($specific_payment_info['payment_config']['merchant_id'])) {
                $MerchantID = $specific_payment_info['payment_config']['merchant_id'];
            }
            if (isset($specific_payment_info['payment_config']['hashkey'])) {
                $HashKey = $specific_payment_info['payment_config']['hashkey'];
            }
            if (isset($specific_payment_info['payment_config']['hashiv'])) {
                $HashIV = $specific_payment_info['payment_config']['hashiv'];
            }
        }
    }
}
	
$TradeInfo = $_POST["TradeInfo"];
if(!$TradeInfo) die("資料錯誤-8000300。");
$Tinfo = create_aes_decrypt($TradeInfo, $HashKey, $HashIV);
$data = json_decode($Tinfo);

if(empty($data->Status)) die("資料錯誤-8000301-Status");
if($data->Status !== "SUCCESS") die("讀取資料失敗-Status");
if(empty($data->Result)) die("資料錯誤-8000301-Result");
$result = $data->Result;

$MerchantTradeNo = $result->MerchantOrderNo;
if(!$MerchantTradeNo) die("資料錯誤-8000301。");

	//read
	$pdo = openpdo(); 	
		
    $sq    = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
    $sq->execute(array($MerchantTradeNo));
    if(!$sqd = $sq->fetch()) die("不明錯誤-8000302。");
    $user_IP =get_real_ip();
    
    $custombg = "assets/images/particles_bg.jpg";
    $pagebgpdo= $pdo->prepare("SELECT custombg FROM servers where auton=?");
    $pagebgpdo->execute(array($sqd["foran"]));
    if($pagebg = $pagebgpdo->fetch()) {
        if (isset($pagebg["custombg"]) && !empty($pagebg["custombg"])) {
            $custombg = "assets/images/custombg/".$pagebg["custombg"];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Game Sponsor</title>
		<meta name="description" content="Game Sponsor" />
		<meta name="Author" content="<?=$weburl?>" />

		<!-- mobile settings -->
		<meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0" />
		<!--[if IE]><meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'><![endif]-->

		<!-- CORE CSS -->
		<link href="/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

		<!-- THEME CSS -->
		<link href="/assets/css/essentials.css" rel="stylesheet" type="text/css" />
		<link href="/assets/css/layout.css?v=1.1" rel="stylesheet" type="text/css" />

		<!-- PAGE LEVEL SCRIPTS -->		
		<link href="/assets/css/color_scheme/green.css" rel="stylesheet" type="text/css" id="color_scheme" />
	</head>

	<body>

		<!-- wrapper -->
		<div id="wrapper">

			<!-- SLIDER -->
			<section id="slider" class="fullheight" style="background:url('<?=$custombg?>')">
				<span class="overlay dark-2"><!-- dark overlay [0 to 9 opacity] --></span>

				<canvas id="canvas-particle" data-rgb="156,217,249"><!-- CANVAS PARTICLES --></canvas>

				<div class="display-table">
					<div class="display-table-cell vertical-align-middle">
						
						<div class="container text-center">
							<h2>Game Sponsor</h2>
							<h1 class="nomargin wow fadeInUp" data-wow-delay="0.4s">
								
								<!--
									TEXT ROTATOR
									data-animation="fade|flip|flipCube|flipUp|spin"
								-->
								<span class="rotate" data-animation="fade" data-speed="1500">
									自助贊助中心, 快速金流, 安全隱私
								</span>
							</h1>
							<hr>
							<div class="main-form">
							
								<div class="col-md-12 col-xs-12 main-title pb-20">遊戲伺服器：【<?=$sqd["forname"]?>】</div>
								<div class="col-md-12 col-xs-12 pb-20">								
								<p>請至對應金流繳費，並確保您的代碼抄寫正確<br>
                <p>繳費教學：<a href="https://www.opay.tw/Service/pay_way_cvcde" target="_blank">超商代碼繳費</a>｜<a href="https://www.opay.tw/Service/pay_way_webatm" target="_blank">ATM虛擬帳號轉帳</a>｜<a href="https://www.opay.tw/Service/pay_way_cvpay" target="_blank">7-11 ibon代碼</a></p>
								</div>
								<div class="col-md-12 col-xs-12 pb-20">
									<h2>
									<?
			$PaymentNo = $result->CodeNo;
			$BankCode = $result->BankCode;
			$vAccount = $result->CodeNo;
			$ExpireDate = $result->ExpireDate;
			switch($sqd["paytype"]) {
	  	case 1:
	  	echo "超商繳費";
	  	break;
	  	case 2:
	  	if(!$vAccount) echo "ATM 虛擬帳號獲取失敗。";
	  	else {
	  	if(!$sqd["PaymentNo"]) {
	  		$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
        $sq2->execute(array($BankCode."-".$vAccount,$ExpireDate, $MerchantTradeNo));
	  	}
	  	// echo "<div style='font-size:26px;'>銀行代碼：".$BankCode."&nbsp;&nbsp;繳費帳號：".$vAccount."<br>請在繳費期限 ".$ExpireDate." 前繳款</div>";
		echo web::payment_inf_render(0, $BankCode, $vAccount, $ExpireDate);
	    }
	  	break;
	  	case 3:
	  	if(!$PaymentNo) echo "超商繳費代碼獲取失敗。";
	  	else {
	  	if(!$sqd["PaymentNo"]) {
	  		$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
        $sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
	  	}
	  	// echo "超商繳費代碼：".$PaymentNo."<br><div style='font-size:26px;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
		  echo web::payment_inf_render(1, '超商繳費', $vAccount, $ExpireDate);
	    }
	  	break;
	  	case 4:
	  	if(!$PaymentNo) echo "7-11 ibon 繳費代碼獲取失敗。";
	  	else {
	  	if(!$sqd["PaymentNo"]) {
	  		$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
        $sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
	  	}
		echo web::payment_inf_render(1, 'ibon', $vAccount, $ExpireDate);
	  	// echo "ibon 代碼：".$PaymentNo."<br><div style='font-size:26px;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
	    }
	  	break;
	  	case 5:
	  	$ptt = "線上刷卡";
	  	alert("請盡速刷卡付款。", 0);
	  	break;
	  	default:
	  	$ptt = "不明";
	  	break;
	  }
									?>
									</h2>
								</div>
								<div class="col-md-12 col-xs-12 pb-20">								
								<a class="btn btn-default" href="<?=$weburl.$sqd["serverid"]?>" target="_self">回首頁</a>
								</div>
							  <div class="col-md-12 col-xs-12 pb-20" style="color:white">您的 IP 位置：<?=$user_IP?></div>
							</div>
						</div>

					</div>
				</div>

			</section>
			<!-- /SLIDER -->

					<!-- FOOTER -->
					<footer id="footer">

<div class="row">
  <div class="col-md-3"></div>
	<div class="col-md-6 text-center">
		&copy Game Sponsor
	</div>
	<div class="col-md-3"></div>

</div>


</footer>
<!-- /FOOTER -->

		</div>
		<!-- /wrapper -->


		<!-- SCROLL TO TOP -->
		<a href="#" id="toTop"></a>


		<!-- PRELOADER -->
		<div id="preloader">
			<div class="inner">
				<span class="loader"></span>
			</div>
		</div><!-- /PRELOADER -->


		<!-- JAVASCRIPT FILES -->
		<script type="text/javascript">var plugin_path = 'assets/plugins/';</script>
		<script type="text/javascript" src="/assets/plugins/jquery/jquery-2.2.3.min.js"></script>

		<script type="text/javascript" src="/assets/js/scripts.js"></script>
		<!-- PARTICLE EFFECT -->
		<script type="text/javascript" src="/assets/plugins/canvas.particles.js"></script>
		<? include_once('./integration.php');?>
	</body>
</html>
