<?include("include.php");

check_login();

if($_REQUEST['st'] == 'addsave') {
	$an = $_REQUEST["an"];
	$uid = $_REQUEST["uid"];
	$upd = $_REQUEST["upd"];
	$names = $_REQUEST["names"];
	
	if($upd == "") alert("請輸入密碼。", 0);
	if($names == "") alert("請輸入姓名。", 0);
	
	if($an == "") {
		if($uid == "") alert("請輸入帳號。", 0);
	  $pdo = openpdo(); 
	  $input = array(':uid' => $_REQUEST["uid"],':upd' => $_REQUEST["upd"],':names' => $_REQUEST["names"]);
    $query    = $pdo->prepare("INSERT INTO manager (names, uid, upd) VALUES(:names,:uid,:upd)");    
    $query->execute($input);
    
    alert("新增完成。", "userlist.php");
    die();
		
	} else {
		
	  $pdo = openpdo(); 
	  $input = array(':upd' => $_REQUEST["upd"],':names' => $_REQUEST["names"],':an' => $an);
    $query    = $pdo->prepare("update manager set upd=:upd, names=:names where auton=:an");    
    $query->execute($input);
    
    alert("修改完成。", "userlist.php");
    die();
	}
	
}

if($_REQUEST["an"] != '') {
	  $pdo = openpdo(); 
    $query    = $pdo->query("SELECT * FROM manager where auton='".$_REQUEST["an"]."'");
    $query->execute();
    $datalist = $query->fetch();
    $tt = "修改";
    $tt2 = "?st=addsave";
  } else {
    $tt = "新增";
    $tt2 = "?st=addsave";
}
top_html();
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
								<strong>管理者設定</strong> <!-- panel title -->
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
								<form name="form1" method="post" action="useradd.php<?=$tt2?>" onsubmit="return chk_form()">
	<table class="table table-bordered">
						  <tbody>
<tr><td>姓名：<input name="names" id="names" type="text" value="<?=$datalist['names']?>"></td></tr>
<tr><td>帳號：<input name="uid" id="uid" type="text" value="<?=$datalist['uid']?>">    　</td></tr>
<tr><td>密碼：<input name="upd" id="upd" type="text" value="<?=$datalist['upd']?>"></td></tr>
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