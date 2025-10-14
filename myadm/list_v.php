<?include("include.php");

check_login();

if($_REQUEST['st'] == 'addsave') {
	$foran = $_REQUEST["foran"];
	$van = $_REQUEST["van"];
	$stats = $_REQUEST["stats"];
	$money1 = $_REQUEST["money1"];
	$money1 = intval($money1);
	if($money1 == "") alert("請輸入金額範圍。", 0);
	if(!is_numeric($money1)) alert("金額範圍只能輸入數字。", 0);

	$money2 = $_REQUEST["money2"];
	$money2 = intval($money2);
	if($money2 == "") alert("請輸入金額範圍。", 0);
	if(!is_numeric($money2)) alert("金額範圍只能輸入數字。", 0);
	
	
	if($money1 >= $money2) alert("前面欄位數字必須小於後面欄位數字。", 0);
	
	$bi = $_REQUEST["bi"];
	$bi = (float)$bi;
	if($bi == "") alert("請輸入比值。", 0);	
	if(!is_numeric($bi)) alert("比值只能輸入數字。", 0);
		
	if($stats == "") alert("請輸入狀態。", 0);
	if($stats == "1") $stats = 1;
	else $stats = 0;
	
	if($van == "") {
	  $pdo = openpdo(); 
	  $input = array(':money1' => $money1,':money2' => $money2,':stats' => $stats,':bi' => $bi,':foran' => $foran);
    $query    = $pdo->prepare("INSERT INTO servers_bi (money1, money2, stats, bi, foran) VALUES(:money1,:money2,:stats,:bi,:foran)");    
    $query->execute($input);
    
    
    alert("幣值新增完成。", "server_bi.php?an=".$foran);
    die();
		
	} else {

	  $pdo = openpdo(); 
	  $input = array(':money1' => $money1,':money2' => $money2,':stats' => $stats,':bi' => $bi,':van' => $van);
    $query    = $pdo->prepare("update servers_bi set money1=:money1, money2=:money2, stats=:stats, bi=:bi where auton=:van");    
    $query->execute($input);
    
    alert("幣值修改完成。", "server_bi.php?an=".$foran);
    die();
	}
	
}

if($_REQUEST["van"] != '') {
	  $pdo = openpdo(); 
    $query    = $pdo->query("SELECT * FROM servers_bi where auton='".$_REQUEST["van"]."'");
    $query->execute();
    $datalist = $query->fetch();
    $tt = "修改";
    $tt2 = "?st=addsave";
    $sts = $datalist['stats'];
  } else {
    $tt = "新增";
    $tt2 = "?st=addsave";
    $sts = 1;
}

if($_REQUEST["an"] == '') alert("贊助編號錯誤。", 0);
top_html();

$pdo = openpdo();
$qq    = $pdo->query("SELECT * FROM servers_log where auton=".$_REQUEST["an"]."");
if(!$datainfo = $qq->fetch()) alert("讀取失敗。", 0);

?>
			<!-- 
				MIDDLE 
			-->
			<section id="middle">
				<div id="content" class="dashboard padding-20">

					<!-- 
						PANEL CLASSES:
							panel-default
							panel-danger
							panel-warning
							panel-info
							panel-success

						INFO: 	panel collapse - stored on user localStorage (handled by app.js _panels() function).
								All pannels should have an unique ID or the panel collapse status will not be stored!
					-->
					<div id="panel-1" class="panel panel-default">
						<div class="panel-heading">
							<span class="title elipsis">
								<strong><a href="list.php">贊助紀錄</a></strong> <!-- panel title -->
                <small>詳細資料</small>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa fa-expand"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>
						<!-- panel content -->
						<div class="panel-body">
							
							<a href="<?=$_SERVER['HTTP_REFERER']?>" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-left"></i> 上一頁</a>
							<div class="table">
								
	<table class="table table-bordered">
						  <tbody>
<tr><td style="background:#666;color:white;text-align:center;">詳細資料</td></tr>
<?
	    switch($datainfo["stats"]) {
	    	case 0:
	    	$stats = '<span class="label label-primary">等待付款</span>';
	    	break;

	    	case 1:
	    	$stats = '<span class="label label-success">付款完成</span>';
	    	break;
	    	
	    	case 2:
	    	$stats = '<span class="label label-danger">付款失敗</span>';
	    	break;

	  	default:
	  	$stats = "不明";
	  	break;
	    }
?>
<tr><td>目前狀態：<?=$stats?></td></tr>
<tr><td>訂單編號：<?=$datainfo["orderid"]?></td></tr>
<tr><td>伺服器名稱：<?=$datainfo["forname"].'['.$datainfo["serverid"].']'?></td></tr>
<tr><td>繳費方式：<?=pay_paytype_name($datainfo["paytype"])?></td></tr>
<?if($datainfo["PaymentNo"]) {?>
<tr><td>繳費代碼：<?=$datainfo["PaymentNo"]?></td></tr>
<?}?>
<?if($datainfo["ExpireDate"]) {?>
<tr><td>繳費期限：<?=$datainfo["ExpireDate"]?></td></tr>
<?}?>
<tr><td>遊戲帳號：<?=$datainfo["gameid"]?></td></tr>
<tr><td>角色名稱：<?=$datainfo["charid"]?></td></tr>
<tr><td>換算金額：<?=$datainfo["bmoney"]?></td></tr>
<tr><td>應繳金額：<?=$datainfo["money"]?></td></tr>
<tr><td>手續費：<?=$datainfo["hmoney"]?></td></tr>
<tr><td>金流回傳：<?=$datainfo["rmoney"]?></td></tr>
<tr><td>比值：<?=$datainfo["bi"]?></td></tr>
<tr><td>開單日期：<?=$datainfo["times"]?></td></tr>
<tr><td>付款日期：<?=$datainfo["paytimes"]?></td></tr>
<tr><td>連線位置：<?=$datainfo["userip"]?></td></tr>
<tr><td>交易訊息：<?=$datainfo["RtnMsg"]?></td></tr>
<tr><td>交易訊息號：<?=$datainfo["RtnCode"]?></td></tr>
  </tbody>
	</table>
 					  

</div>

						</div>
						<!-- /panel content -->


					</div>

				</div>
			</section>
			<!-- /MIDDLE -->

<?down_html()?>

<script type="text/javascript">
$(function() {

});

</script>