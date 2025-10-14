<?php
include("include.php");

check_login();

$an = _r("an");
$uid = _r("uid");
	
if($_REQUEST['st'] == 'addsave') {
	if(empty($upd = _r("upd"))) alert("請輸入密碼。", 0);
	if(empty($names = _r("names"))) alert("請輸入姓名。", 0);
	
	if(empty($an)) {
	  if(empty($uid)) alert("請輸入帳號。", 0);
	  $pdo = openpdo(); 

      $query = $pdo->prepare("select * from shareuser where uid=?");    
	  $query->execute([$uid]);
	  if($query->fetch()) return alert("帳號重覆。", 0);
	  
	  $query = $pdo->prepare("INSERT INTO shareuser (names, uid, upd) VALUES(?, ?, ?)");    
	  $query->execute([
	      $names,
	      $uid,
	      $upd
	  ]);
	  
	  $servers = _r("servers");
      $sth = $pdo->prepare("delete from shareuser_server where uid=?");    
      $sth->execute([$uid]);

      foreach($servers as $ss) {
          $sth = $pdo->prepare("INSERT INTO shareuser_server (uid, serverid, times) VALUES(?, ?, ?)");
          $sth->execute([
              $uid,
              $ss,
              $nowtime
          ]);
      }
      
      $servers2 = _r("servers2");
      $sth = $pdo->prepare("delete from shareuser_server2 where uid=?");    
      $sth->execute([$uid]);

      foreach($servers2 as $ss2) {
          $sth = $pdo->prepare("INSERT INTO shareuser_server2 (uid, serverid, times) VALUES(?, ?, ?)");
          $sth->execute([
              $uid,
              $ss2,
              $nowtime
          ]);
      }
	  

      alert("新增完成。", "share_userlist.php");
      die();
		
	} else {
		
	  $pdo = openpdo(); 
      $query = $pdo->prepare("update shareuser set upd=?, names=? where auton=?");    
      $query->execute([
          $upd,
          $names,
          $an
      ]);

	  $servers = _r("servers");
      $sth = $pdo->prepare("delete from shareuser_server where uid=?");    
      $sth->execute([$uid]);

      foreach($servers as $ss) {
          $sth = $pdo->prepare("INSERT INTO shareuser_server (uid, serverid, times) VALUES(?, ?, ?)");
          $sth->execute([
              $uid,
              $ss,
              $nowtime
          ]);
      }
      
      $servers2 = _r("servers2");
      $sth = $pdo->prepare("delete from shareuser_server2 where uid=?");    
      $sth->execute([$uid]);

      foreach($servers2 as $ss2) {
          $sth = $pdo->prepare("INSERT INTO shareuser_server2 (uid, serverid, times) VALUES(?, ?, ?)");
          $sth->execute([
              $uid,
              $ss2,
              $nowtime
          ]);
      }

      alert("修改完成。", "share_userlist.php");
    die();
	}
	
}


top_html();

$pdo = openpdo(); 

if(!empty($an)) {	  
    $query = $pdo->prepare("SELECT * FROM shareuser where auton=?");
    $query->execute([$an]);
    $datalist = $query->fetch();
    $tt = "修改";
	$tt2 = "?st=addsave";
	$readonly = " readonly";
	$readonltt = " 無法修改";

	$servers = [];
	$query2 = $pdo->prepare("SELECT serverid FROM shareuser_server where uid=?");
    $query2->execute([$datalist["uid"]]);
	if($info2 = $query2->fetchAll()) {
	foreach($info2 as $ii) $servers[] = $ii["serverid"];	
	}
	$servers2 = [];
	$query2 = $pdo->prepare("SELECT serverid FROM shareuser_server2 where uid=?");
    $query2->execute([$datalist["uid"]]);
	if($info2 = $query2->fetchAll()) {
	foreach($info2 as $ii) $servers2[] = $ii["serverid"];	
	}
  } else {
    $tt = "新增";
	$tt2 = "?st=addsave";
	$readonly = "";
	$readonltt = "";
	$servers = [];
}
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
								<strong>子帳號設定</strong> <!-- panel title -->
                <small class="size-12 weight-300 text-mutted hidden-xs"><?=$tt?>使用者</small>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>

						<!-- panel content -->
						<div class="panel-body">

							<div class="table">
								<form name="form1" method="post" action="share_useradd.php<?=$tt2?>" onsubmit="return chk_form()">
	<table class="table table-bordered">
						  <tbody>
<tr><td>姓名：<input name="names" id="names" type="text" value="<?=$datalist['names']?>"></td></tr>
<tr><td>帳號：<input name="uid" id="uid" type="text" value="<?=$datalist['uid']?>"<?=$readonly?>><?=$readonltt?></td></tr>
<tr><td>密碼：<input name="upd" id="upd" type="text" value="<?=$datalist['upd']?>"></td></tr>
<tr><td><div class="margin-bottom-20">可分享伺服器：</div>
<div class="margin-bottom-20">
<?php
$query = $pdo->query("SELECT * FROM servers order by gp desc, des desc");
$query->execute();
if($datalist = $query->fetchAll()) {
  $ix = 0;
  foreach($datalist as $info) {
      $cc = in_array($info["id"], $servers) ? ' checked' : '';
      $cc2 = in_array($info["id"], $servers2) ? ' checked' : '';
	    echo '<div class="col-md-3 col-xs-12">
	            <div class="panel panel-info">
	                <div class="panel-heading">
		                <h2 class="panel-title text-center">'.$info["names"].'['.$info["id"].']</h2>
	                </div>
	                <div class="panel-body text-center">
        	            <div class="col-md-6 col-xs-6">
        	                <label class="checkbox"><input type="checkbox" name="servers[]" value="'.$info["id"].'"'.$cc.'><i></i> 分享</label>
        	            </div>
        	            <div class="col-md-6 col-xs-6">
        	                <label class="checkbox"><input type="checkbox" name="servers2[]" value="'.$info["id"].'"'.$cc2.'><i></i> 管理</label>
        	            </div>
        	        </div>
	            </div>
	        </div>';
  }
}
?>
</div>
</td></tr>
  </tbody>
	</table>
 					  
		<div align="center"> 
          <?if($_REQUEST["an"] != "") {?>
          <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定修改">
          <input type="hidden" id="an" name="an" value="<?=$_REQUEST["an"]?>">
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
function chk_form() {

if(!$("#names").val()) {
alert("請輸入姓名。");
$("#names").focus();
return false;
}
if(!$("#uid").val()) {
alert("請輸入帳號。");
$("#uid").focus();
return false;
}
if(!$("#upd").val()) {
alert("請輸入密碼。");
$("#upd").focus();
return false;
}

return true;
}
</script>