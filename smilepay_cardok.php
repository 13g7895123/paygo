<?php
include("myadm/include.php");

$MerchantTradeNo = $_GET["o"];

if(!$MerchantTradeNo) alert("資料錯誤-8000301。", 0);
	//read
		$pdo = openpdo(); 	
		
    $sq = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
    $sq->execute(array($MerchantTradeNo));
    if(!$sqd = $sq->fetch()) alert("不明錯誤-8000302。", 0);
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
							
							<hr>
							<div class="main-form">
							
								<h2 class="col-md-12 col-xs-12 main-title pt-100">付款完成</h2>
							
								<div class="col-md-12 col-xs-12 pt-100 pb-20">								
								<a class="btn btn-default" href="<?=$weburl.$sqd["serverid"]?>" target="_self">回首頁</a>
								</div>
								<div class="col-md-12 col-xs-12 pb-20" style="color:white;"><br><br><br><br><br><br><br><br><br><br><br><br><br><br>所有繳費資料包含IP電磁紀錄皆已留存，如有惡意人士利用此繳費平台進行第三方詐騙，請受害者立即與我們客服聯繫提供資料報警處理，請注意您的贊助皆為個人自願性，繳費後將無法做退費的動作，我們會將該筆費用維持伺服器運行與開發研究，並捐出部分款項給慈善機構，如贊助金流系統故障請聯絡客服人員！</div>
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
	</body>
</html>
