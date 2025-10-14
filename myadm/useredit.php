<?include("include.php");

check_login();

if($_REQUEST["st"] == "ed") {
	
	if($_REQUEST["upd"] == "") alert("請輸入密碼。", 0);
	
	  $pdo = openpdo(); 
	  $input = array(':upd' => $_REQUEST["upd"],':uid' => $_SESSION["adminid"]);
    $query    = $pdo->prepare("update manager set upd=:upd where uid=:uid");    
    $query->execute($input);
    
    alert("修改完成。", "useredit.php");
    die();
}

top_html();

	  $pdo = openpdo(); 
    $query    = $pdo->query("SELECT * FROM manager where uid='".$_SESSION["adminid"]."'");
    $query->execute();
    $datalist = $query->fetch();
    
    $uid = $datalist["uid"];
    $upd = $datalist["upd"];
    $names = $datalist["names"];
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
								<strong>修改密碼</strong> <!-- panel title -->								
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa fa-expand"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>

						<!-- panel content -->
						<div class="panel-body">							
							<div class="table-responsive">
	
						 <form name="form1" method="post" action="useredit.php?st=ed" onsubmit="return chk_form()">
						<table class="table table-bordered">
						  <thead>
<tr><td>姓名：<input type="text" value="<?=$names?>" readonly disabled></td></tr>
    <tr> 
      <td>帳號：
        <input type="text" value="<?=$uid?>"  readonly disabled>
		      密碼：
        <input name="upd" id="upd" type="text" value="<?=$upd?>">
      </td>
    </tr>
						  </tbody>
					  </table>					  
			
			<div align="center"> 
		  <input type="submit" name="Submit" value="確定修改" class="btn btn-default btn-sm">
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