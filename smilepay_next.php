<?php

    include("myadm/include.php");
	include_once('./web_class.php');
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
				$gurl = "https://ssl.smse.com.tw/ezpos/mtmk_utf.asp";		
				// $gurl = "https://ssl.smse.com.tw/api/SPPayment.asp";		
			} else {
				$gurl = "https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp";
			}
			$Dcvc = $sqd["smilepay_shop_id"];
			$Str_Check = $sqd["smilepay_key2"];
		}else if ($paytype == 2){
			// 使用新的 bank_funds 資料表取得 smilepay 銀行轉帳設定
			$payment_info = getSpecificBankPaymentInfo($pdo, $_SESSION["lastan"], 'smilepay');
			if ($payment_info && isset($payment_info['payment_config'])) {
				$Dcvc = $payment_info['payment_config']['merchant_id'];
				$Str_Check = $payment_info['payment_config']['verify_key'];
			}

			$gurl = ($gstats_bank == 1) 
			? "https://ssl.smse.com.tw/api/SPPayment.asp"   // 正式
			: "https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp"; // 模擬
		} else {
			if ($gstats2 == 1) {
				// $gurl = "https://ssl.smse.com.tw/ezpos/mtmk_utf.asp";
				$gurl = "https://ssl.smse.com.tw/api/SPPayment.asp";
			} else {				
				$gurl = "https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp";
			}
			$Dcvc = $sqd["smilepay_shop_id2"];
			$Str_Check = $sqd["smilepay_key2"];
		}

    $forname = $sqd["names"];

    if($Dcvc == "" || $Str_Check == "") alert("金流錯誤-8000206。", 0);

    $money = $datalist["money"];
    $pt = $datalist["paytype"];
    $stats = $datalist["stats"];
    $tradeno = $datalist["orderid"];
    $nowtime = date("Y/m/d H:i:s");

	switch($pt) {
		case 2:
			$Pay_zg = "2";
			break;
		case 30:
			$Pay_zg = "6";		// 全家
			break;
		case 31:
			$Pay_zg = "4";		// ibon
			break;
		case 32:
			$Pay_zg = "4";		// ibon
			
			break;
		default:
			die();
			break;
	}

	$TradeDesc = "贊助中心";
	$ItemName = random_products($_SESSION["serverid"]);

	// print_r($ItemName); die();

	$rurl = $weburl."smilepay_r.php";
	// $rurl = $weburl."smilepay_receive.php";
	$rurl2 = $weburl."gomypay_payok.php";

    $sq2 = $pdo->prepare("update servers_log set forname=? where auton=?");
    $sq2->execute(array($forname, $_SESSION["lastan"]));

	$payValue['gurl'] = $gurl;
	$payValue['Dcvc'] = $Dcvc;
	$payValue['Rvg2c'] = 1;
	$payValue['Od_sob'] = $ItemName;
	$payValue['Pay_zg'] = $Pay_zg;
	$payValue['Data_id'] = $tradeno;
	$payValue['Amount'] = $money;
	$payValue['Roturl'] = $rurl;
	$payValue['Verify_key'] = $Str_Check;

	// echo json_encode($payValue);
	// die();

	// 打API取得回傳結果
	$xml = web::curl_api($gurl, $payValue);

	// 解析 XML
	$doc = new DOMDocument();
	$doc->loadXML($xml);

	$status = $doc->getElementsByTagName('Status')->item(0)->nodeValue;		// 狀態

	if ($status == 1){			// 成功
		$data_id = $doc->getElementsByTagName('Data_id')->item(0)->nodeValue;			// 訂單編號
		$pay_end_date = $doc->getElementsByTagName('PayEndDate')->item(0)->nodeValue;	// 繳費期限

		if ($Pay_zg == 6){
			$fami_no = $doc->getElementsByTagName('FamiNO')->item(0)->nodeValue;		// 全家繳費代碼
			$payment_no = $fami_no;
		}elseif ($Pay_zg == 4){
			$ibon_no = $doc->getElementsByTagName('IbonNo')->item(0)->nodeValue;
			$payment_no = $ibon_no;
		}elseif ($Pay_zg == 2){
			$atm_bank_no = $doc->getElementsByTagName('AtmBankNo')->item(0)->nodeValue;
			$atm_no = $doc->getElementsByTagName('AtmNo')->item(0)->nodeValue;
			$payment_no = $atm_bank_no . '-' . $atm_no;
		}

		$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
    	$sq2->execute(array($payment_no, $pay_end_date, $data_id));
	}

	// echo $data_id;

	//read	(從payok複製過來)
	$pdo = openpdo(); 	
	$sq = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
	$sq->execute(array($data_id));

	if(!$sqd = $sq->fetch()) alert("不明錯誤-8000302。".$data_id, 0);
	$user_IP = get_real_ip();    
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
								<div class="col-md-12 col-xs-12 main-title pb-20">遊戲伺服器：【<?=$sqd["forname"]?>】<?=$dbstat?></div>
								<div class="col-md-12 col-xs-12 pb-20">								
									<p>請至對應金流繳費，並確保您的代碼抄寫正確<br>
									<p>繳費教學：<a href="https://www.opay.tw/Service/pay_way_cvcde" target="_blank">超商代碼繳費</a>｜<a href="https://www.opay.tw/Service/pay_way_webatm" target="_blank">ATM虛擬帳號轉帳</a>｜<a href="https://www.opay.tw/Service/pay_way_cvpay" target="_blank">7-11 ibon代碼</a></p>
								</div>
								<div class="col-md-12 col-xs-12 pb-20">
									<h2>
			<?
			$PaymentNo = $payment_no;
			$BankCode = $atm_bank_no;
			$vAccount = $atm_no;
			$ExpireDate = $pay_end_date;
			switch($sqd["paytype"]) {
				case 2:
					if(!$vAccount) echo "ATM 虛擬帳號獲取失敗。";
					else {
						if(!$sqd["PaymentNo"]) {
							$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
							$sq2->execute(array($BankCode."-".$vAccount,$ExpireDate, $MerchantTradeNo));
						}
						echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";
                        echo web::payment_inf_render(0, $BankCode, $vAccount, $ExpireDate);
						// echo "<div style='font-size:26px;color:white;'>銀行代碼：".$BankCode."&nbsp;&nbsp;繳費帳號：".$vAccount."<br>請在繳費期限 ".$ExpireDate." 前繳款</div>";
					}
					break;
				case 4:
					if(!$PaymentNo) echo "7-11 ibon 繳費代碼獲取失敗。";
					else {
						if(!$sqd["PaymentNo"]) {
							$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
							$sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
						}
							echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";
                            echo web::payment_inf_render(1, 'ibon ', $PaymentNo, $ExpireDate);
							// echo "ibon 代碼：".$PaymentNo."<br><div style='font-size:26px;color:white;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
						}
					break;
				case 30:
					if(!$PaymentNo) echo "全家 繳費代碼獲取失敗。";
					else {
						if(!$sqd["PaymentNo"]) {
							$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
							$sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
						}
						echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";
                        echo web::payment_inf_render(1, '全家', $PaymentNo, $ExpireDate);
						// echo "全家代碼：".$PaymentNo."<br><div style='font-size:26px;color:white;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
					}
					break;
				case 31:
					if(!$PaymentNo) echo "7-11 繳費代碼獲取失敗。";
					else {
						if(!$sqd["PaymentNo"]) {
							$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
							$sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
						}
						echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";
                        echo web::payment_inf_render(1, '7-11超商', $PaymentNo, $ExpireDate);
						// echo "7-11超商代碼：".$PaymentNo."<br><div style='font-size:26px;color:white;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
					}
					break;
				case 32:
					if(!$PaymentNo) echo "萊爾富 繳費代碼獲取失敗。";
					else {
						if(!$sqd["PaymentNo"]) {
							$sq2 = $pdo->prepare("update servers_log set PaymentNo=?, ExpireDate=? where orderid=?");
							$sq2->execute(array($PaymentNo,$ExpireDate, $MerchantTradeNo));
						}
						echo "<div style='font-size:26px;color:white;'>繳費金額：".$sqd["money"]."</div>";
                        echo web::payment_inf_render(1, '萊爾富', $PaymentNo, $ExpireDate);
						// echo "萊爾富代碼：".$PaymentNo."<br><div style='font-size:26px;color:white;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
					}
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
								<div class="col-md-12 col-xs-12 pb-20" style="color:white">所有繳費資料包含IP電磁紀錄皆已留存，如有惡意人士利用此繳費平台進行第三方詐騙，請受害者立即與我們客服聯繫提供資料報警處理，請注意您的贊助皆為個人自願性，繳費後將無法做退費的動作，我們會將該筆費用維持伺服器運行與開發研究，並捐出部分款項給慈善機構，如贊助金流系統故障請聯絡客服人員！</div>
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

        <? include_once('./integration.php'); ?>

		<!-- PARTICLE EFFECT -->
		<script type="text/javascript" src="/assets/plugins/canvas.particles.js"></script>

	</body>
</html>