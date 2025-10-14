<?php

include("myadm/include.php");

if($_REQUEST["st"] == "readbi" && $_REQUEST["v"] != "") {	
	if($_SESSION["foran"] == "") die("伺服器資料錯誤-8000201。");
	if($_SESSION["serverid"] == "") die("伺服器資料錯誤-8000202。");
	$v = $_REQUEST["v"];

	$pdo = openpdo(); 	
    $query    = $pdo->prepare("SELECT * FROM servers_bi where stats=1 and foran=? order by money2 asc");
    $query->execute(array($_SESSION["foran"]));

    if(!$datalist = $query->fetchAll()) die($v);
    $bb = 0;

    foreach ($datalist as $datainfo) {
    	$m1 = $datainfo["money1"];
    	$m2 = $datainfo["money2"];
    	$bi = $datainfo["bi"];
    	if($v >= $m1 && $v <= $m2) $bb = $bi;
    }

    if($bb==0) $bb = 1;
    $bb = $bb * $v;

    echo $bb;
    die();
}

if($_REQUEST["st"] == "send") {
	// 檢查是否為AJAX請求
	$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

	if ($_SESSION["foran"] == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器資料錯誤-8000201。']);
			die();
		} else {
			alert("伺服器資料錯誤-8000201。", 0);
		}
	}
	if ($_SESSION["serverid"] == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器資料錯誤-8000202。']);
			die();
		} else {
			alert("伺服器資料錯誤-8000202。", 0);
		}
	}
	$gid = $_REQUEST["gid"];
	//$cid = $_REQUEST["cid"];
	$money = $_REQUEST["money"];
	$pt = $_REQUEST["pt"];
	$psn = $_REQUEST["psn"];
	$is_bank = $_REQUEST["is_bank"];

	// ANT專用欄位處理（第79點修正：預設NULL）
	$user_bank_code = null;
	$user_bank_account = null;

	// 初始化資料庫連接
	$pdo = openpdo();

	// 取得伺服器設定
	$server_query = $pdo->prepare("SELECT pay_bank FROM servers WHERE auton = ?");
	$server_query->execute(array($_SESSION["foran"]));
	$server_data = $server_query->fetch();
	
	if ($pt == 2 && $server_data['pay_bank'] == 'ant') {
		$user_bank_code = trim($_REQUEST["user_bank_code"]);
		$user_bank_account = trim($_REQUEST["user_bank_account"]);
		
		// 驗證必填欄位
		if (empty($user_bank_code) || empty($user_bank_account)) {
			if ($is_ajax) {
				echo json_encode(['status' => 'error', 'message' => 'ANT銀行轉帳需要提供銀行代號與帳號']);
				die();
			} else {
				alert("ANT銀行轉帳需要提供銀行代號與帳號", 0);
			}
		}
		
		// 銀行代號格式驗證
		if (!preg_match('/^\d{3}$/', $user_bank_code)) {
			if ($is_ajax) {
				echo json_encode(['status' => 'error', 'message' => '銀行代號格式錯誤']);
				die();
			} else {
				alert("銀行代號格式錯誤", 0);
			}
		}
	}

	if ($gid == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入遊戲帳號。']);
			die();
		} else {
			alert("請輸入遊戲帳號。", 0);
		}
	}
	//if($cid == "") alert("請輸入角色名稱。", 0);
	if ($money == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入繳款金額。']);
			die();
		} else {
			alert("請輸入繳款金額。", 0);
		}
	}
	$money = intval($money);	
	if (!is_numeric($money)) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '繳款金額只能輸入數字。']);
			die();
		} else {
			alert("繳款金額只能輸入數字。", 0);
		}
	}

	if ($pt == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請選擇繳款方式。']);
			die();
		} else {
			alert("請選擇繳款方式。", 0);
		}
	}
	if ($psn == "") {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '請輸入驗證碼。']);
			die();
		} else {
			alert("請輸入驗證碼。", 0);
		}
	}

	if ($psn != $_SESSION['excellence_fun_code']) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '驗證碼錯誤。']);
			die();
		} else {
			alert("驗證碼錯誤。", 0);
		}
	}

	// print_r(123); die();

	// check database config
	$pdo = openpdo(); 
	$dbqq = $pdo->prepare("SELECT * FROM servers where auton=?");
	$dbqq->execute(array($_SESSION["foran"]));

	if (!$datalist = $dbqq->fetch()) {
		if ($is_ajax) {
			echo json_encode(['status' => 'error', 'message' => '伺服器尚未就緒。']);
			die();
		} else {
			alert("伺服器尚未就緒。", 0);
		}
	} else {
		if (!$datalist["db_ip"] || !$datalist["db_port"] || !$datalist["db_name"] || !$datalist["db_user"] || !$datalist["db_pass"]) {
			if ($is_ajax) {
				echo json_encode(['status' => 'error', 'message' => '伺服器尚未就緒 - 資料庫未設定完成。']);
				die();
			} else {
				alert("伺服器尚未就緒 - 資料庫未設定完成。", 0);
			}
		}
	}

	// 定義變數，後面的datalist會被其他查詢結果蓋掉
	$pay_cp = $datalist["pay_cp"];
	$pay_cp2 = $datalist["pay_cp2"];
	$pay_bank = $datalist["pay_bank"];
	$forname = $datalist["names"];
	// check game id

    if ($datalist['game'] == 0){
        $table_name = 'accounts';
        $column_name = 'login';
    }else if ($datalist['game'] == 1){
        $table_name = 'login';
        $column_name = 'userid';
    }    

    // 有選擇遊戲的時候要判斷帳號是否存在
	if ($datalist['game'] == 0 or $datalist['game'] == 1) {
		// 判斷是否為希望
		if ($datalist['paytable'] == 'hope') {
			$gamepdo = opengamepdo($datalist["db_ip"], $datalist["db_port"], $datalist["db_name"], $datalist["db_user"], $datalist["db_pass"]);
			$gamequery = $gamepdo->prepare("select * from users where LOWER(id)=?");
			$gamequery->execute(array(strtolower($gid)));

			if (!$gamequery->fetch()) {
				if ($is_ajax) {
					echo json_encode(['status' => 'error', 'message' => '遊戲內無此帳號，請確認您的遊戲帳號3。']);
					die();
				} else {
					alert("遊戲內無此帳號，請確認您的遊戲帳號4。", 0);
				}
			}
		} else {	// 其他遊戲到各自資料庫取資料確認
			$gamepdo = opengamepdo($datalist["db_ip"], $datalist["db_port"], $datalist["db_name"], $datalist["db_user"], $datalist["db_pass"]);
			$gameq = $gamepdo->prepare("select * from $table_name where LOWER($column_name)=?");
			$gameq->execute(array(strtolower($gid)));
			if (!$gameq->fetch()) {
				if ($is_ajax) {
					echo json_encode(['status' => 'error', 'message' => '遊戲內無此帳號，請確認您的遊戲帳號。']);
					die();
				} else {
					alert("遊戲內無此帳號，請確認您的遊戲帳號。", 0);
				}
			}
		}
	}

	// get ip
	  $user_IP = get_real_ip();    

  // make order id
    $orderid = date("ymdHis");
    $orderid .= strtoupper(substr(uniqid(rand()),0,3));

	/* 訂單編號加上網域判斷 */
	$orderid .= '01';	/* paygo 為 01，ezpay 為 02 */

	//算比值
	$bb = 0;    
	$qq = $pdo->prepare("SELECT * FROM servers_bi where stats=1 and foran=? order by money2 asc");
    $qq->execute(array($_SESSION["foran"]));
    if($datalist = $qq->fetchAll()) {
		foreach ($datalist as $datainfo) {
			$m1 = $datainfo["money1"];
			$m2 = $datainfo["money2"];
			$bi = $datainfo["bi"];		
			if($money >= $m1 && $money <= $m2) $bb = $bi;
		}
    }

	if($pt == 5) { // 信用卡金流
        $pay_cp_check = $pay_cp;
    } else {
		$pay_cp_check = $pay_cp2;
	}

	// 銀行轉帳判定
	if ($pt == 2) {
		$pay_cp_check = $pay_bank;
	}

    if($bb==0) $bb = 1;
    $bmoney = $bb * $money;

	// 取得 username（僅限 ANT 銀行支付）
	$username = null;
	if ($pt == 2 && $is_bank == 1 && $pay_cp_check == 'ant') {
		$username_query = $pdo->prepare("SELECT username FROM bank_funds WHERE server_code = :server_code AND third_party_payment = 'ant' LIMIT 1");
		$username_query->bindValue(':server_code', $_SESSION["serverid"], PDO::PARAM_STR);
		$username_query->execute();

		if ($username_row = $username_query->fetch()) {
			$username = $username_row['username'];
		}
	}

	// 寫入 servers_log
	$input = array(':foran' => $_SESSION["foran"], ':forname' => $forname, ':serverid' => $_SESSION["serverid"],':gameid' => $gid,':money' => $money,':bmoney' => $bmoney,':paytype' => $pt,':bi' => $bb, ':userip' => $user_IP, ':orderid' => $orderid, ':pay_cp' => $pay_cp_check);
    $query = $pdo->prepare("INSERT INTO servers_log (foran, forname, serverid, gameid, money, bmoney, paytype, bi, userip, orderid, pay_cp) VALUES(:foran, :forname, :serverid,:gameid,:money,:bmoney,:paytype,:bi,:userip,:orderid, :pay_cp)");
    $query->execute($input);

    $result = $pdo->lastInsertId();

    $_SESSION["lastan"] = $result;
	if(!empty($shareid = _s("shareid"))) {
		$shq = $pdo->prepare("UPDATE servers_log SET shareid=? where auton=?");
		$shq->execute(array($shareid, $result));
	}
	// 根據請求類型處理重定向
	if ($is_ajax) {
		// AJAX請求：返回重定向URL
		$redirect_url = '';
		switch ($pay_cp_check) {
			case "pchome":
				$redirect_url = 'pchome_next.php';
				break;
			case "ebpay":
				$redirect_url = 'ebpay_next.php';
				break;
			case "gomypay":
				$redirect_url = 'gomypay_next.php';
				break;
			case "smilepay":
				$redirect_url = 'smilepay_next.php';
				break;
			case "funpoint":
				$redirect_url = 'funpoint_next.php';
				break;
			case "szfu":
				$redirect_url = 'szfu_next.php';
				break;
			case "ant":
				$redirect_url = 'ant_next.php';
				break;
			default:
				$redirect_url = 'next.php';
				break;
		}
		echo json_encode(['status' => 'success', 'redirect' => $redirect_url]);
		die();
	} else {
		// 傳統表單提交：直接重定向
		switch ($pay_cp_check) {
			case "pchome":
				header('Location: pchome_next.php');
				break;
			case "ebpay":
				header('Location: ebpay_next.php');
				break;
			case "gomypay":
				header('Location: gomypay_next.php');
				break;
			case "smilepay":
				header('Location: smilepay_next.php');
				break;
			case "funpoint":
				header('Location: funpoint_next.php');
				break;
			case "szfu":
				header('Location: szfu_next.php');
				break;
			case "ant":
				header('Location: ant_next.php');
				break;
			default:
				header('Location: next.php');
				break;
		}
		die();
	}
}

$id = $_REQUEST["id"];

if($id == "") die("server id error.");

$pdo = openpdo(); 	
$query = $pdo->prepare("SELECT * FROM servers where id=?");
$query->execute(array($id));
if(!$datalist = $query->fetch()) die("server id error.");

if($datalist["stats"] == 0) die("server stop.");
$_SESSION["foran"] = $datalist["auton"];
$_SESSION["serverid"] = $datalist["id"];

if(!$datalist["db_ip"] || !$datalist["db_port"] || !$datalist["db_name"] || !$datalist["db_user"] || !$datalist["db_pass"]) $dbstat = "<small style='color:#999'>資料庫尚未就緒</small>";
else $dbstat = "";

$base_money = $datalist["base_money"];
if(!$base_money) $base_money = 100;

$user_ip = get_real_ip();    

if(!empty($ss = _r("s"))) {
	$shq = $pdo->prepare("SELECT * FROM shareuser where uid=? limit 1");
	$shq->execute(array($ss));
	if($shqi = $shq->fetch()) $_SESSION["shareid"] = $shqi["uid"];
}

$custombg = $datalist["custombg"];

if(empty($custombg)) $custombg = "assets/images/particles_bg.jpg";
else $custombg = "assets/images/custombg/".$custombg;

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
				<canvas id="canvas-particle" data-rgb="156, 217, 249">CANVAS PARTICLES</canvas>
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
							<form method="post" action="index.php">
								<div class="col-md-12 col-xs-12 main-title padding-bottom-20 bold">遊戲伺服器：【<?=$datalist["names"]?>】<?=$dbstat?></div>
								<?php
								if($gp = $datalist["gp"]) {
									$query2 = $pdo->prepare("SELECT * FROM servers where gp=? order by des desc");
                                    $query2->execute(array($gp));
                                     if($gsarr = $query2->fetchALL()) {  
										echo '<div class="col-md-12 col-xs-12 main-title padding-bottom-20">';
										echo '<select class="form-control" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';										
									       foreach($gsarr as $gs) {
											   if($gs["id"] == $id) $gssel = " selected";
											   else $gssel = "";
											   echo '<option value="/'.$gs["id"].'"'.$gssel.'>'.$gs["names"].'</option>';
										   }
								        echo '</select>';
								        echo '</div>';
									 }
								}
								?>

								<div class="col-md-12 col-xs-12 padding-bottom-20">		
									<input type="text" class="form-control" name="gid" id="gid" placeholder="請輸入遊戲帳號" autocomplete="off" required>
								</div>

								<!--<div class="col-md-12 col-xs-12 padding-bottom-20">
								<input type="text" class="form-control" name="cid" id="cid" placeholder="請輸入角色名稱" required>
								</div>-->

								<div class="col-md-12 col-xs-12 padding-bottom-20">
									<select name="pt" id="pt" class="form-control" required>
										<option value="">請選取繳款方式</option>
									<?php
										if($datalist["pay_cp2"] == "gomypay" || $datalist["pay_cp2"] == "szfu") {
											echo '<option value="30">超商代碼(上限20000)全家</option>
												<option value="31">超商代碼(上限20000)OK</option>
												<option value="32">超商代碼(上限20000)萊爾富</option>
												<option value="33">超商代碼(上限20000)7-11</option>';
										}elseif ($datalist["pay_cp2"] == "smilepay"){
											echo '<option value="30">超商代碼(上限20000)全家</option>
												<option value="31">超商代碼(上限20000)7-11</option>
												<option value="32">超商代碼(上限20000)萊爾富</option>';
										} else {
											// 限制不顯示的超商
											$noShop = array('破界仙境','浪流連天堂','最終之戰','時空裂痕','初樂天堂','反轉仙境','天堂憶舊服');
											if (!in_array($datalist["names"], $noShop)){
                                            	echo '<option value="3">超商代碼(7-11/OK/全家/萊爾富)</option>';
                                            }
										}
									?>
									<?php
										// 不顯示銀行轉帳
										$noBank = array('大天使經典服');
										if (!in_array($datalist["names"], $noBank) && $datalist["pay_bank"] != "no"){
											echo '<option value="2">銀行轉帳</option>';
										}
									?>
									<?php
										if ($datalist["pay_cp"] != "no"){
											echo '<option value="5">信用卡</option>';
										}
									?>
										<!--option value="6">網路ATM(僅支援IE瀏覽器)</option-->
										<!--<option value="5">信用卡</option>-->
                            		</select>
								</div>

								<div id='form_hidden' style="opacity: 1; transition: opacity 0.5s ease-in-out; display: none;">
									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<? if ($datalist['pay_type_show'] == 0) { ?>
											<input type="number" class="form-control" name="money" id="money" min="<?=$base_money?>" placeholder="請輸入繳款金額" required>
										<? }else{
												$server_id = $datalist['auton'];    // server ID
												$sscp_query = $pdo->prepare("SELECT * FROM servers_show_customize_price WHERE foran=? ORDER BY money");
												$sscp_query->execute(array($server_id));
												if($sscp = $sscp_query->fetchALL()) {
													$option_html = '<option value="0">請選擇金額</option>';
													foreach ($sscp as $skey => $sval) {
														$option_html .= "<option value='".$sval['money']."'>".$sval['money']."</option>";
													}
												}
										?>
											<select name="money" id="sel_money" class="form-control" required>
												<?=$option_html;?>
											</select>
										<?} ?>
									</div>

									<? if ($datalist['use_virtual_ratio'] == 0) { ?>
										<div class="col-md-12 col-xs-12 padding-bottom-20">
											<div class="col-md-10 col-xs-9 pl-0"><input type="text" class="form-control" name="money2" id="money2" placeholder="幣值換算" readonly></div>
											<div class="col-md-2 col-xs-3 pl-0"><button id="read_bi_btn" type="button" class="btn btn-primary btn-md" style="color:#fff !important;">點我換算</button></div>
										</div>
									<? } ?>

									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<div class="col-md-6 col-xs-6 pl-0">
									<input type="text" class="form-control" name="psn" id="psn" placeholder="驗證碼" autocomplete="off" required>
									</div>
										<div class="col-md-6 col-xs-6">
										<a href="#r" onclick="reload_psn($(this))"><img id="index_psn_img" src="psn.php"></a>
										</div>
									</div>
									<div class="col-md-12 col-xs-12 padding-bottom-20">
										<input type="hidden" name="st" value="send">
										<!-- <input type="submit" class="btn btn-default" value="確定儲值"> -->
										<button type="button" id="submit_btn" class="btn btn-default">確定儲值</button>
									</div>
								</div>
							</form>
							 <div class="col-md-12 col-xs-12 pb-20" style="color:white">　
							 <br><br><br><br><br><br>
                             <br>所有繳費資料包含IP電磁紀錄皆已留存，如有惡意人士利用此繳費平台進行第三方詐騙，請受害者立即與我們客服聯繫提供資料報警處理，請注意您的贊助皆為個人自願性，繳費後將無法做退費的動作，我們會將該筆費用維持伺服器運行與開發研究，並捐出部分款項給慈善機構，如贊助金流系統故障請聯絡客服人員！</div>
							  <div class="col-md-12 col-xs-12 padding-bottom-20" style="color:white">您的 IP 位置：<?=$user_ip?></div>
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



<script type="text/javascript">
$(function() {
	// alert('<?=$forname?>')
	$('#money').on('input', function() {
		const payType = $('#pt').val();
		const store = [3, 30, 31, 32, 33];
		let max_money = 0;
		
		if (payType == 2) {
			max_money = <?=$datalist['max_bank'] != null && $datalist['max_bank'] != 0 ? $datalist['max_bank'] : 0?>;
		} else if (store.includes(parseInt(payType))) {
			max_money = <?=$datalist['max_store'] != 'null' && $datalist['max_store'] != 0 ? $datalist['max_store'] : 0?>;
		} else if (payType == 5) {
			max_money = <?=$datalist['max_credit'] != null && $datalist['max_credit'] != 0 ? $datalist['max_credit'] : 0?>;
		}

		if (max_money == 0) {
			return;
		}

		let inp_money = $(this).val();
		if (inp_money > max_money) {
			$('#money').val(max_money);
			alert('您選擇的繳款金額上限為' + max_money + '元');
			return false;
		}
	});

	// Initially hide the form elements below
	$('#form_hidden').hide();

	// Function to check if conditions are met
	function checkConditions() {
		const selectVal = $("#pt").val();
		const inputVal = $("#gid").val().trim();
		const serverPayBank = '<?= $datalist["pay_bank"] ?>';
		
		// Check if select has valid value (not 0) and input is not empty
		if (selectVal != "0" && selectVal != "" && inputVal !== "") {
			// Show elements with fade animation
			$('#form_hidden').slideDown(800);

			// 當帳號被輸入的同時，判斷支付方式是否為銀行轉帳，如果是的話要顯示ANT欄位
			if (selectVal == '2' && serverPayBank == 'ant') {
				$('#ant_bank_fields').slideDown();
			} else {
				$('#ant_bank_fields').slideUp();
				// 清空欄位值
				$('#user_bank_code, #user_bank_account').val('');
			}

			return;
		}

		$('#form_hidden').slideUp(400);

		// 當條件不符合時，同時隱藏ANT欄位
		$('#ant_bank_fields').slideUp();
		$('#user_bank_code, #user_bank_account').val('');
	}

	// Monitor both select and input for changes
	$("#pt, #gid").on('change keyup', function() {
		checkConditions();
	});

	// 支付方式改變，如果皆不為預設，則清空繳款金額
	$("#pt").on('change', function() {
		const selectVal = $("#pt").val();
		const inputVal = $("#money").val();

		if (selectVal != '' && inputVal != '') {
			$("#money").val('');
			$('#money2').val('');
		}

		// 重新檢查所有條件，包括ANT欄位顯示
		checkConditions();
	});

	// 驗證碼輸入框按Enter鍵送出
	$("#psn").on("keypress", function(e) {
		if (e.which == 13) {  // Enter鍵的鍵碼是13
			e.preventDefault();
			$("#submit_btn").click();
		}
	});

	// 表單提交處理
	$("#submit_btn").on("click", async function() {
		try {

			// ANT欄位驗證
			const payType = $("#pt").val();
			const serverPayBank = '<?= $datalist["pay_bank"] ?>';
			
			if (payType == '2' && serverPayBank == 'ant') {
				const bankCode = $("#user_bank_code").val().trim();
				const bankAccount = $("#user_bank_account").val().trim();
				
				if (!bankCode) {
					alert('請輸入銀行代號');
					$("#user_bank_code").focus();
					return;
				}
				
				if (!bankAccount) {
					alert('請輸入銀行帳號');
					$("#user_bank_account").focus();
					return;
				}
				
				// 銀行代號格式驗證（3位數字）
				if (!/^\d{3}$/.test(bankCode)) {
					alert('銀行代號格式錯誤，請輸入3位數字');
					$("#user_bank_code").focus();
					return;
				}
			}

			// 收集表單資料
			var formData = {
				st: "send",
				gid: $("#gid").val(),
				money: <?php if ($datalist['pay_type_show'] == 0) { ?>$("#money").val() <?php } else { ?>$("#sel_money").val() <?php } ?>,
				pt: $("#pt").val(),
				psn: $("#psn").val(),
				is_bank: ($("#pt").val() == 2) ? 1 : 0,
				user_bank_code: (payType == '2' && serverPayBank == 'ant') ? $("#user_bank_code").val() : '',
				user_bank_account: (payType == '2' && serverPayBank == 'ant') ? $("#user_bank_account").val() : ''
			};

			// 發送AJAX請求
			$.ajax({
				url: "index.php",
				type: "POST",
				data: formData,
				dataType: "json",
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
					$("#submit_btn").prop("disabled", true).text("處理中...");
				},
				success: function(response) {
					if (response.status === 'success') {
						// 重定向到支付頁面
						window.location.href = response.redirect;
					} else if (response.status === 'error') {
						alert(response.message);
						$("#submit_btn").prop("disabled", false).text("確定儲值");
					}
				},
				error: function(xhr, status, error) {
					alert("系統錯誤，請稍後再試。");
					$("#submit_btn").prop("disabled", false).text("確定儲值");
				}
			});
		} catch (error) {
		}
	});

	$("#read_bi_btn").on("click", function() {
		var $oi = $("#money2");

		let inp_money
		<?if ($datalist['pay_type_show'] == 0){?>
			inp_money = $("#money").val()
		<?}else{?>
			if ($("#sel_money :selected").val() != 0){
				inp_money = $("#sel_money :selected").val();
			}else{
				alert('請選擇金額')
			}			
		<?}?>

		if(!inp_money) {
			$oi.val("請先輸入繳款金額。");
			return false;	
		}

		if(!$.isNumeric(inp_money)) {
			$oi.val("繳款金額只能是數字。");
			return false;	 	
		}

		if(inp_money < <?=$base_money?>) {
			$oi.val("繳款金額必須大於 <?=$base_money?>。");
			return false;	 	
		}

		$.ajax({
			url: "index.php",
			data: { st: "readbi", v: inp_money },
			dataType: "html"
		}).done(function(msg) {
			$oi.val(msg);
		});
	});
});

function reload_psn($th) {
	var $d = new Date();
	var $img = $th.find("img");	
	$img.attr("src", $img.attr("src")+"?"+$d.getTime());
}

</script>