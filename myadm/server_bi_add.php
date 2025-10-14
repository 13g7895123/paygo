<?include("include.php");

check_login_share();

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

if($_REQUEST["an"] == '') alert("伺服器編號錯誤。", 0);
top_html();

$pdo = openpdo();
$qq    = $pdo->query("SELECT names FROM servers where auton=".$_REQUEST["an"]."");
$names = $qq->fetch()["names"];
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
								<strong><a href="index.php">伺服器管理</a></strong> <!-- panel title -->
                <small>幣值管理</small> / <small><?=$names?></small> / <small><?=$tt?>幣值</small>
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
								<form name="form1" method="post" action="server_bi_add.php<?=$tt2?>">
	<table class="table table-bordered">
						  <tbody>
<tr><td style="background:#666;color:white;text-align:center;"><?=$tt?>幣值</td></tr>
<tr><td>金額範圍：<input name="money1" id="money1" type="number" min="1" value="<?=$datalist['money1']?>" required> 到 <input name="money2" id="money2" type="number" min="1" value="<?=$datalist['money2']?>" required> 之間&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>前面欄位數字必須小於後面欄位數字</small></td></tr>
<tr><td>比值：<input name="bi" id="bi" type="number" step="any" value="<?=$datalist['bi']?>" required>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>請輸入數字 例 1, 1.1, 1.2</small></td></tr>
<tr><td>狀態：<input type="radio" name="stats" value="1"<?if($sts == 1) echo " checked"?> required> 開啟 &nbsp;&nbsp;<input type="radio" name="stats" value="0"<?if($sts != 1) echo " checked"?>> 停用</td></tr>
  </tbody>
	</table>
 					  
					  <div align="center"> 
					<input type="hidden" id="foran" name="foran" value="<?=$_REQUEST["an"]?>">
          <?if($_REQUEST["van"] != "") {?>
          <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定修改">
          <input type="hidden" id="van" name="van" value="<?=$_REQUEST["van"]?>">
		      <?} else {?>
		      <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定新增">
		      <?}?>
        </div>
</form>
	
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