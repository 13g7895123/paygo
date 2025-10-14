<?include("include.php");

check_login_share();

if($_REQUEST['st'] == 'send') {
	$foran = $_REQUEST["foran"];
	$gift1 = $_REQUEST["gift1"];
	$gift2 = $_REQUEST["gift2"];
	$gift3 = $_REQUEST["gift3"];
	$gift4 = $_REQUEST["gift4"];
	
	$m1 = $_REQUEST["m1"];
	$m2 = $_REQUEST["m2"];
	$pid = $_REQUEST["pid"];
	$sizes = $_REQUEST["sizes"];
	
	foreach ($m1 as $mm1) {
		if($mm1 == "") $mm1 = 0;
		$mm1 = intval($mm1);		
		if(!is_numeric($mm1)) alert("滿額贈禮-金額範圍1只能輸入數字。", 0);
	}
	$ij = 0;
	foreach ($m2 as $mm2) {
		if($mm2 == "") $mm2 = 0;
		$mm2 = intval($mm2);		
		if(!is_numeric($mm2)) alert("滿額贈禮-金額範圍2只能輸入數字。", 0);
		if($m1[$ij] != 0 && $mm2 != 0 && $m1[$ij] >= $mm2) alert("滿額贈禮-前面欄位數字必須小於後面欄位數字。".$m1[$ij]."-".$mm2."", 0);
		$ij++;
	}
	
  foreach ($sizes as $ss) {
		if($ss == "") $ss = 0;
		$ss = intval($ss);		
		if(!is_numeric($ss)) alert("滿額贈禮-贈禮數量只能輸入數字。", 0);
	}
	
	$pdo = openpdo();
	$dquery    = $pdo->prepare("delete from servers_gift where foran=? and types=1");    
    $dquery->execute(array($foran));
  
  if($gift1 == "1") {
  	$input = array(':foran' => $foran,':types' => 1,':pid' => 'stat',':sizes' => 1);
  	} else {
  	$input = array(':foran' => $foran,':types' => 1,':pid' => 'stat',':sizes' => 0);
  }
  	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, sizes) VALUES(:foran,:types,:pid,:sizes)");    
    $query->execute($input);
  
  for ( $i=0 ; $i<30 ; $i++ ) {  	
  	$input2 = array(':foran' => $foran,':types' => 1,':m1' => $m1[$i] == "" ? 0 : $m1[$i],':m2' => $m2[$i] == "" ? 0 : $m2[$i],':pid' => $pid[$i],':sizes' => $sizes[$i] == "" ? 0 : $sizes[$i]);
    $query2 = $pdo->prepare("INSERT INTO servers_gift (foran, types, m1, m2, pid, sizes) VALUES(:foran,:types,:m1,:m2,:pid,:sizes)");    
    $query2->execute($input2);
  }


	$m1_2 = $_REQUEST["m1_2"];
	$m2_2 = $_REQUEST["m2_2"];
	$pid_2 = $_REQUEST["pid_2"];
	$sizes_2 = $_REQUEST["sizes_2"];
	
	foreach ($m1_2 as $mm1_2) {
		if($mm1_2 == "") $mm1_2 = 0;
		$mm1_2 = intval($mm1_2);		
		if(!is_numeric($mm1_2)) alert("首購禮-金額範圍1只能輸入數字。", 0);
	}
	$ij = 0;
	foreach ($m2_2 as $mm2_2) {
		if($mm2_2 == "") $mm2_2 = 0;
		$mm2_2 = intval($mm2_2);		
		if(!is_numeric($mm2_2)) alert("首購禮-金額範圍2只能輸入數字。", 0);
		if($m1_2[$ij] != 0 && $mm2_2 != 0 && $m1_2[$ij] >= $mm2_2) alert("首購禮-前面欄位數字必須小於後面欄位數字。".$m1_2[$ij]."-".$mm2_2."", 0);
		$ij++;
	}
	
  foreach ($sizes_2 as $ss_2) {
		if($ss_2 == "") $ss_2 = 0;
		$ss_2 = intval($ss_2);		
		if(!is_numeric($ss_2)) alert("首購禮-贈禮數量只能輸入數字。", 0);
	}

	$dquery    = $pdo->prepare("delete from servers_gift where foran=? and types=2");    
	$dquery->execute(array($foran));
	
  if($gift2 == "1") {
  	$input = array(':foran' => $foran,':types' => 2,':pid' => 'stat',':sizes' => 1);
  	} else {
  	$input = array(':foran' => $foran,':types' => 2,':pid' => 'stat',':sizes' => 0);
  }
  	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, sizes) VALUES(:foran,:types,:pid,:sizes)");    
    $query->execute($input);
  
  for ( $i=0 ; $i<20 ; $i++ ) {  	
  	$input2 = array(':foran' => $foran,':types' => 2,':m1' => $m1_2[$i] == "" ? 0 : $m1_2[$i],':m2' => $m2_2[$i] == "" ? 0 : $m2_2[$i],':pid' => $pid_2[$i],':sizes' => $sizes_2[$i] == "" ? 0 : $sizes_2[$i]);
    $query2 = $pdo->prepare("INSERT INTO servers_gift (foran, types, m1, m2, pid, sizes) VALUES(:foran,:types,:m1,:m2,:pid,:sizes)");    
    $query2->execute($input2);
  }


	$m1_3 = $_REQUEST["m1_3"];
	$m2_3 = $_REQUEST["m2_3"];
	$pid_3 = $_REQUEST["pid_3"];
	$sizes_3 = $_REQUEST["sizes_3"];
	
	foreach ($m1_3 as $mm1_3) {
		if($mm1_3 == "") $mm1_3 = 0;
		$mm1_3 = intval($mm1_3);		
		if(!is_numeric($mm1_3)) alert("累積儲值-金額範圍1只能輸入數字。", 0);
	}
	
  foreach ($sizes_3 as $ss_3) {
		if($ss_3 == "") $ss_3 = 0;
		$ss_3 = intval($ss_3);		
		if(!is_numeric($ss_3)) alert("累積儲值-贈禮數量只能輸入數字。", 0);
	}

	$dquery    = $pdo->prepare("delete from servers_gift where foran=? and types=3");    
	$dquery->execute(array($foran));
	
  if($gift3 == "1") {
  	$input = array(':foran' => $foran,':types' => 3,':pid' => 'stat',':sizes' => 1);
  	} else {
  	$input = array(':foran' => $foran,':types' => 3,':pid' => 'stat',':sizes' => 0);
  }
  	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, sizes) VALUES(:foran,:types,:pid,:sizes)");    
    $query->execute($input);
  
  for ( $i=0 ; $i<50 ; $i++ ) {  	
  	$input2 = array(':foran' => $foran,':types' => 3,':m1' => $m1_3[$i] == "" ? 0 : $m1_3[$i],':m2' => 0,':pid' => $pid_3[$i],':sizes' => $sizes_3[$i] == "" ? 0 : $sizes_3[$i]);
    $query2 = $pdo->prepare("INSERT INTO servers_gift (foran, types, m1, m2, pid, sizes) VALUES(:foran,:types,:m1,:m2,:pid,:sizes)");    
    $query2->execute($input2);
  }
  
  $m1_4 = $_REQUEST["m1_4"];
  $m2_4 = $_REQUEST["m2_4"];
  $pid_4 = $_REQUEST["pid_4"];
  $sizes_4 = $_REQUEST["sizes_4"];
  $time4_1_1 = $_REQUEST["time4_1_1"];
  $time4_1_2 = $_REQUEST["time4_1_2"];
  $time4_2_1 = $_REQUEST["time4_2_1"];
  $time4_2_2 = $_REQUEST["time4_2_2"];

  foreach ($m1_4 as $mm1_4) {
	  if($mm1_4 == "") $mm1_4 = 0;
	  $mm1_4 = intval($mm1_4);		
	  if(!is_numeric($mm1_4)) alert("活動首購禮-金額範圍1只能輸入數字。", 0);
  }
  $ij = 0;
  foreach ($m2_4 as $mm2_4) {
	  if($mm2_4 == "") $mm2_4 = 0;
	  $mm2_4 = intval($mm2_4);		
	  if(!is_numeric($mm2_4)) alert("活動首購禮-金額範圍2只能輸入數字。", 0);
	  if($m1_4[$ij] != 0 && $mm2_4 != 0 && $m1_4[$ij] >= $mm2_4) alert("活動首購禮-前面欄位數字必須小於後面欄位數字。".$m1_4[$ij]."-".$mm2_4."", 0);
	  $ij++;
  }
  
foreach ($sizes_4 as $ss_4) {
	  if($ss_4 == "") $ss_4 = 0;
	  $ss_4 = intval($ss_4);		
	  if(!is_numeric($ss_4)) alert("活動首購禮-贈禮數量只能輸入數字。", 0);
  }
  
    if(!empty($time4_1_1)) {
		$time41 = $time4_1_1." ".$time4_1_2;		
		$time41 = strtotime($time41);
		if($time41 === false) alert("活動首購禮-開始時間錯誤。", 0);		
		$time41 = date("Y-m-d H:i", $time41);				
	}

    if(!empty($time4_2_1)) {
	$time42 = $time4_2_1." ".$time4_2_2;
	$time42 = strtotime($time42);
	if($time42 === false) alert("活動首購禮-結束時間錯誤。", 0);
	$time42 = date("Y-m-d H:i", $time42);
	}

if($gift4 == "1") {
	if(empty($time4_1_1) || empty($time4_2_1)) alert("活動首購禮-開啟前請輸入活動時間。", 0);
	$input = array(':foran' => $foran,':types' => 4,':pid' => 'stat',':sizes' => 1);
	if($time41 !== false && $time42 !== false) if($time42 <= $time41) alert("活動首購禮-結束時間不可大於開始時間。", 0);	
	
    } else {
	$input = array(':foran' => $foran,':types' => 4,':pid' => 'stat',':sizes' => 0);
	}
		

	$dquery    = $pdo->prepare("delete from servers_gift where foran=? and types=4");    
    $dquery->execute(array($foran));

	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, sizes) VALUES(:foran,:types,:pid,:sizes)");    
	$query->execute($input);
	
	if(!empty($time41)) {
	$inputt1 = array(':foran' => $foran,':types' => 4,':pid' => 'time1',':dd' => $time41);
	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, dd) VALUES(:foran,:types,:pid,:dd)");    
	$query->execute($inputt1);
	}
	if(!empty($time42)) {
	$inputt2 = array(':foran' => $foran,':types' => 4,':pid' => 'time2',':dd' => $time42);
	$query = $pdo->prepare("INSERT INTO servers_gift (foran, types, pid, dd) VALUES(:foran,:types,:pid,:dd)");    
	$query->execute($inputt2);
	}

for ( $i=0 ; $i<20 ; $i++ ) {  	
    $input2 = array(':foran' => $foran,':types' => 4,':m1' => $m1_4[$i] == "" ? 0 : $m1_4[$i],':m2' => $m2_4[$i] == "" ? 0 : $m2_4[$i],':pid' => $pid_4[$i],':sizes' => $sizes_4[$i] == "" ? 0 : $sizes_4[$i]);
  $query2 = $pdo->prepare("INSERT INTO servers_gift (foran, types, m1, m2, pid, sizes) VALUES(:foran,:types,:m1,:m2,:pid,:sizes)");    
  $query2->execute($input2);
}
    alert("贈禮修改完成。", "gift.php?an=".$foran);
    die();

}

if($_REQUEST["an"] == '') alert("伺服器編號錯誤。", 0);
top_html();

$pdo = openpdo();
$qq    = $pdo->query("SELECT names FROM servers where auton=".$_REQUEST["an"]."");
$qq->execute();
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
                <small>贈禮管理</small> / <small><?=$names?></small>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa fa-expand"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>
						<!-- panel content -->
						<div class="panel-body">
							<?
$arrs = array();
$gstat1 = 0;
$qq    = $pdo->query("SELECT * FROM servers_gift where foran=".$_REQUEST["an"]." and types=1 order by auton");
$qq->execute();
if($querys = $qq->fetchALL()) {
	$ix = 0;	
	foreach ($querys as $qqs) {
		$m1 = 0;
		$m2 = 0;
		$pid = "";
		$sizes = 0;
		
		if($qqs["m1"]) $m1 = $qqs["m1"];
		if($qqs["m2"]) $m2 = $qqs["m2"];
		if($qqs["pid"]) $pid = $qqs["pid"];
		if($qqs["sizes"]) $sizes = $qqs["sizes"];
	  if($pid == "stat")  {
	  	if($sizes == 1) $gstat1 = 1;
	  } else {	  	
	  	$arrs[$ix]=array($m1,$m2,$pid,$sizes);
	  	$ix++;
	  }	  
  }
}
							?>
							<a href="javascript:history.back();" class="btn btn-primary"><i class="fa-solid fa-backward"></i> 上一頁</a>	
								<form name="form1" method="post" action="?st=send">
								
<a data-toggle="collapse" href="#collgift1">
<div class="margin-top-10 margin-bottom-10 alert alert-info col-md-12 col-xs-12">滿額贈禮</div>
</a>

<table id="collgift1" class="table table-bordered collapse">
<tbody>
<tr><td>	
<label class="radio">
	<input type="radio" name="gift1" value="1"<?if($gstat1 == 1) echo " checked";?>>
	<i></i> 開啟
</label>
<label class="radio">
	<input type="radio" name="gift1" value="0"<?if($gstat1 == 0) echo " checked";?>>
	<i></i> 關閉
</label>
	<small>前面欄位數字必須小於後面欄位數字</small></td></tr>
<?
for ( $i=0 ; $i<30 ; $i++ ) {
?>
<tr>
	<td>金額範圍：<input name="m1[]" type="number" value="<?=$arrs[$i][0]?>"> 到 <input name="m2[]" type="number" value="<?=$arrs[$i][1]?>">&nbsp;&nbsp;
  贈禮編號：<input name="pid[]" type="text" value="<?=$arrs[$i][2]?>">&nbsp;&nbsp;
  贈禮數量：<input name="sizes[]" type="number" value="<?=$arrs[$i][3]?>"></td>
</tr>
<?
}
?>

<?
$arrs = array();
$gstat2 = 0;
$qq    = $pdo->query("SELECT * FROM servers_gift where foran=".$_REQUEST["an"]." and types=2 order by auton");
$qq->execute();
if($querys = $qq->fetchALL()) {
	$ix = 0;	
	foreach ($querys as $qqs) {
		$m1 = 0;
		$m2 = 0;
		$pid = "";
		$sizes = 0;
		
		if($qqs["m1"]) $m1 = $qqs["m1"];
		if($qqs["m2"]) $m2 = $qqs["m2"];
		if($qqs["pid"]) $pid = $qqs["pid"];
		if($qqs["sizes"]) $sizes = $qqs["sizes"];
	  if($pid == "stat")  {
	  	if($sizes == 1) $gstat2 = 1;
	  } else {	  	
	  	$arrs[$ix]=array($m1,$m2,$pid,$sizes);
	  	$ix++;
	  }	  
  }
}
?>
</tbody>
</table>

<a data-toggle="collapse" href="#collgift2">
<div class="margin-top-10 margin-bottom-10 alert alert-info col-md-12 col-xs-12">首購禮</div>
</a>

<table id="collgift2" class="table table-bordered collapse">
<tbody>
<tr><td>
<label class="radio">
	<input type="radio" name="gift2" value="1"<?if($gstat2 == 1) echo " checked";?>>
	<i></i> 開啟
</label>
<label class="radio">
	<input type="radio" name="gift2" value="0"<?if($gstat2 == 0) echo " checked";?>>
	<i></i> 關閉
</label>
	<small>前面欄位數字必須小於後面欄位數字</small></td></tr>
<?
for ( $i=0 ; $i<20 ; $i++ ) {
?>
<tr>
	<td>金額範圍：<input name="m1_2[]" type="number" value="<?=$arrs[$i][0]?>"> 到 <input name="m2_2[]" type="number" value="<?=$arrs[$i][1]?>">&nbsp;&nbsp;
  贈禮編號：<input name="pid_2[]" type="text" value="<?=$arrs[$i][2]?>">&nbsp;&nbsp;
  贈禮數量：<input name="sizes_2[]" type="number" value="<?=$arrs[$i][3]?>"></td>
</tr>
<?
}
?>

<?
$arrs = array();
$gstat4 = 0;
$qq    = $pdo->query("SELECT * FROM servers_gift where foran=".$_REQUEST["an"]." and types=4 order by auton");
$qq->execute();
if($querys = $qq->fetchALL()) {
	$ix = 0;	
	foreach ($querys as $qqs) {
		$m1 = 0;
		$m2 = 0;
		$pid = "";
		$sizes = 0;
		$dd = "";
		
		if($qqs["m1"]) $m1 = $qqs["m1"];
		if($qqs["m2"]) $m2 = $qqs["m2"];
		if($qqs["pid"]) $pid = $qqs["pid"];
		if($qqs["sizes"]) $sizes = $qqs["sizes"];
		if($qqs["dd"]) $dd = $qqs["dd"];

	  if($pid == "stat") {
	  	if($sizes == 1) $gstat4 = 1;
	  } elseif($pid == "time1") {
		$time4_1_1 = chtimed($dd);
		$time4_1_2 = chtimet($dd);
	  } elseif($pid == "time2") {
		$time4_2_1 = chtimed($dd);
		$time4_2_2 = chtimet($dd);
	  } else {
	  	$arrs[$ix]=array($m1,$m2,$pid,$sizes);
	  	$ix++;
	  }	  
  }
}
?>
</tbody>
</table>
<style>
.time_pick {
	display: inline;	
}
</style>
<a data-toggle="collapse" href="#collgift4">
<div class="margin-top-10 margin-bottom-10 alert alert-info col-md-12 col-xs-12">活動首購禮</div>
</a>

<table id="collgift4" class="table table-bordered collapse in">
<tbody>

<tr><td>	
<label class="radio">
	<input type="radio" name="gift4" value="1"<?if($gstat4 == 1) echo " checked";?>>
	<i></i> 開啟
</label>
<label class="radio">
	<input type="radio" name="gift4" value="0"<?if($gstat4 == 0) echo " checked";?>>
	<i></i> 關閉
</label>
	<small>前面欄位數字必須小於後面欄位數字</small></td></tr>

<tr><td>
開始時間：<input type="text" name="time4_1_1" class="datepicker" style="display: inline;" value="<?=$time4_1_1?>">
<input type="text" name="time4_1_2" style="padding:4px;" class="masked" data-format="99:99" data-placeholder="0" value="<?=$time4_1_2?>">
<br><br>
結束時間：<input type="text" name="time4_2_1" class="datepicker" style="display: inline;" value="<?=$time4_2_1?>">
<input type="text" name="time4_2_2" style="padding:4px;" class="masked" data-format="99:99" data-placeholder="0" value="<?=$time4_2_2?>">
</td></tr>

<?
for ( $i=0 ; $i<20 ; $i++ ) {
?>
<tr>
	<td>金額範圍：<input name="m1_4[]" type="number" value="<?=$arrs[$i][0]?>"> 到 <input name="m2_4[]" type="number" value="<?=$arrs[$i][1]?>">&nbsp;&nbsp;
  贈禮編號：<input name="pid_4[]" type="text" value="<?=$arrs[$i][2]?>">&nbsp;&nbsp;
  贈禮數量：<input name="sizes_4[]" type="number" value="<?=$arrs[$i][3]?>"></td>
</tr>
<?
}
?>

<?
$arrs = array();
$gstat3 = 0;
$qq    = $pdo->query("SELECT * FROM servers_gift where foran=".$_REQUEST["an"]." and types=3 order by auton");
$qq->execute();
if($querys = $qq->fetchALL()) {
	$ix = 0;	
	foreach ($querys as $qqs) {
		$m1 = 0;
		$m2 = 0;
		$pid = "";
		$sizes = 0;
		
		if($qqs["m1"]) $m1 = $qqs["m1"];
		if($qqs["m2"]) $m2 = $qqs["m2"];
		if($qqs["pid"]) $pid = $qqs["pid"];
		if($qqs["sizes"]) $sizes = $qqs["sizes"];
	  if($pid == "stat")  {
	  	if($sizes == 1) $gstat3 = 1;
	  } else {	  	
	  	$arrs[$ix]=array($m1,$m2,$pid,$sizes);
	  	$ix++;
	  }	  
  }
}
?>

</tbody>
</table>

<a data-toggle="collapse" href="#collgift3">
<div class="margin-top-10 margin-bottom-10 alert alert-info col-md-12 col-xs-12">累積儲值</div>
</a>

<table id="collgift3" class="table table-bordered collapse">
<tbody>

<tr><td>	
<label class="radio">
	<input type="radio" name="gift3" value="1"<?if($gstat3 == 1) echo " checked";?>>
	<i></i> 開啟
</label>
<label class="radio">
	<input type="radio" name="gift3" value="0"<?if($gstat3 == 0) echo " checked";?>>
	<i></i> 關閉
</label>
	<small>前面欄位數字必須小於後面欄位數字</small></td></tr>
<?
for ( $i=0 ; $i<50 ; $i++ ) {
?>
<tr>
	<td>累積儲值金額：<input name="m1_3[]" type="number" value="<?=$arrs[$i][0]?>">&nbsp;&nbsp;
  贈禮編號：<input name="pid_3[]" type="text" value="<?=$arrs[$i][2]?>">&nbsp;&nbsp;
  贈禮數量：<input name="sizes_3[]" type="number" value="<?=$arrs[$i][3]?>"></td>
</tr>
<?
}
?>
  </tbody>
	</table>
 					  
        <div align="center"> 
					<input type="hidden" id="foran" name="foran" value="<?=$_REQUEST["an"]?>">
          <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定修改">
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