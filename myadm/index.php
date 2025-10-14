<?include("include.php");

check_login();

if($_REQUEST["st"] == "svr_st") {
	if($_REQUEST["v"] == "") alert("類型錯誤。", 0);
	if($_REQUEST["ans"] == "") alert("伺服器編號錯誤。", 0);	
	
	$pdo = openpdo(); 
	$input = array(':stats' => $_REQUEST["v"]);
    $query = $pdo->prepare("update servers set stats=:stats where auton in (".$_REQUEST["ans"].")");    
    $query->execute($input);
    
    alert("設定完成。", 0);
	die();
}

if($_REQUEST["st"] == "svr_del") {
	if($_REQUEST["del_server_alln"] == "") alert("伺服器編號錯誤。", 0);	
	
	  $pdo = openpdo();

    $query    = $pdo->prepare("delete from servers where auton in (".$_REQUEST["del_server_alln"].")");    
    $query->execute($input);
    $query    = $pdo->prepare("delete from servers_bi where foran in (".$_REQUEST["del_server_alln"].")");    
    $query->execute($input);
    
    alert("刪除完成。", 0);
    die();
}

top_html();
?>
			<section id="middle">
				<div id="content" class="dashboard padding-20">

					<div id="panel-1" class="panel panel-default">
						<div class="panel-heading">
							<span class="title elipsis">
								<strong><a href="index">伺服器管理</a></strong> <!-- panel title -->								
								<?if($_REQUEST["keyword"] != "") echo "<small>搜尋：".$_REQUEST["keyword"]."</small>"?>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>

						<!-- panel content -->
						<div class="panel-body">
              <form name="form1" method="get" action="index.php" onsubmit="return chk_kform()" class="form-inline nomargin noborder padding-bottom-10">	              	              	              	
						   <div class="form-group">
                <input type="text" name="keyword" id="keyword" class="form-control" placeholder="群組/伺服器名稱/尾綴代號" value="<?=$_REQUEST["keyword"]?>" required>                
               </div>
               <div class="form-group">
               <input type="submit" class="btn btn-default" value="搜尋">
               </div>
               <div class="form-group">
               <a href="server_add" class="btn btn-info"><i class="fa fa-plus"></i> 新增</a>
               <a href="#r" onclick="ch_seln('2')" class="btn btn-danger"><i class="fa-solid fa-trash"></i> 刪除</a>
               <a href="#r" onclick="ch_seln('1')" class="btn btn-success"><i class="fa-solid fa-check"></i> 開啟</a>	
               <a href="#r" onclick="ch_seln('0')" class="btn btn-warning"><i class="fa-solid fa-xmark"></i> 停用</a>	
               </div>
						  </form>		
						  			
							<div class="table-responsive">
	              <table class="table table-hover">
						      <thead>
						  	  <tr>
								<th><input type="checkbox" onclick="allseln($(this))"></th>
								    <th>排序</th>
									<th>群組</th>			  	  
									<th>伺服器名稱</th>
									<th>尾綴代號</th>									
									<th>資料庫</th>
									<th>信用卡金流</th>
									<th>其他金流</th>
									<th>幣值筆數</th>
									<th>最低金額</th>
									<th>使用狀態</th>
									<th></th>
                  </tr>
              </thead>
  <tbody>
  <? 
  $kword = $_REQUEST["keyword"];
  $sql = "";
  if($kword != "") {
  	$sql = " and (gp like '%$kword%' or names like '%$kword%' or id like '%$kword%')";
  }
  
    $pdo = openpdo();
 // 運行 SQL    
    $offset = isset($_REQUEST['offset']) ? $_REQUEST['offset']:0;
    $limit_row = 20;
    $query    = $pdo->query("SELECT count(auton) as t FROM servers where 1=1".$sql."");
    $numsrow = $query->fetch()["t"];
    
    $pagestr = pages($numsrow, $offset, $limit_row);     
    // $query = $pdo->query("SELECT * FROM servers where 1=1".$sql." order by gp desc, des desc limit ".$offset.", ".$limit_row."");
    $query = $pdo->query("SELECT * FROM servers where 1=1".$sql." order by gp desc, times asc limit ".$offset.", ".$limit_row."");
    $query->execute();
    if(!$datalist = $query->fetchAll()) {
    	echo "<tr><td colspan=7>暫無資料</td></tr>";
    } else {
    	foreach ($datalist as $datainfo) {
    		
    		if($datainfo["stats"] == 1) $stats = '<span class="label label-success">開啟</span>';
    		else $stats = '<span class="label label-warning">停用</span>';
    		
		echo "<tr>";
		echo '<td><input type="checkbox" name="seln" value="'.$datainfo["auton"].'"></td>';
		echo "<td>".$datainfo["des"]."</td>";		
		echo '<td>';
		if($datainfo["gp"]) echo $datainfo["gp"];
		else  echo '無';
		echo '</td>';
    	echo '<td><a href="'.$weburl.$datainfo["id"].'" target="_blank">'.$datainfo["names"].'</a></td>';
		echo "<td>".$datainfo["id"]."</td>";		
    	$dbstr = "";
    	if($datainfo["db_ip"]) $dbstr = $datainfo["db_ip"];
      if($dbstr) {
      	$dbstr = '<a href="#d" data-toggle="tooltip" data-placement="top" title="資料庫位置：'.$datainfo["db_ip"].'<br>資料庫端口：'.$datainfo["db_port"].'<br>資料庫名稱：'.$datainfo["db_name"].'<br>資料庫帳號：'.$datainfo["db_user"].'<br>資料庫密碼：'.$datainfo["db_pass"].'<br>物品代碼：'.$datainfo["db_pid"].'">'.$dbstr.'</a>';
      }      
		echo "<td>".$dbstr."</td>";
		if($datainfo["gstats"] == 1) $gstr = "&nbsp;[正式]";
		else $gstr = "&nbsp;[模擬]";
		echo '<td>'.pay_cp_name($datainfo["pay_cp"]).$gstr.'</td>';
    	
		if($datainfo["gstats2"] == 1) $gstr2 = "&nbsp;[正式]";
		else $gstr2 = "&nbsp;[模擬]";
        echo '<td>'.pay_cp_name($datainfo["pay_cp2"]).$gstr2.'</td>';

    	$qq = $pdo->query("SELECT count(auton) as t FROM servers_log where foran=".$datainfo["auton"]."");
      $qq->execute();
      $qq = $qq->fetch();
      if($qq["t"]) $qcount = $qq["t"];
      else $qcount = 0;
    	echo "<td>".$qcount."</td>";
    	echo "<td>".$datainfo["base_money"]."</td>";
    	echo "<td>".$stats."</td>";
    	echo '<td><a href="server_add.php?an='.$datainfo["auton"].'" class="btn btn-default btn-sm">修改</a> <a href="server_bi.php?an='.$datainfo["auton"].'" class="btn btn-default btn-sm">幣值</a> <a href="gift.php?an='.$datainfo["auton"].'" class="btn btn-default btn-sm">贈禮</a></td>';
    	echo "</tr>";
      }
    }
?>
						  </tbody>
	</table>
</div><?=$pagestr;?>

						</div>
						<!-- /panel content -->


					</div>

				</div>
			</section>
			<!-- /MIDDLE -->

<?down_html()?>
<div id="del_server_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- Modal Body -->
			<div class="modal-body">
				
				<p>是否確定要將伺服器刪除？<br><br><b>請注意刪除後將無法回復資料庫紀錄。</b></p>
			</div>
      <form method="post" action="index.php" class="nomargin noborder">
			<!-- Modal Footer -->
			<div class="modal-footer">
				<input type="hidden" id="del_server_alln" name="del_server_alln" value="">
				<input type="hidden" name="st" value="svr_del">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消刪除</button>
				<button type="submit" class="btn btn-danger">確定刪除</button>
			</div>
		  </form>

		</div>
	</div>
</div>
<script type="text/javascript">
$(function() {

});

function ch_seln($sts) {
	var $alln = [];
	$("input[name='seln']:checked").each(function() {
    $alln.push($.trim($(this).val()));
  });
  
  if(!$alln.length) {
  	alert("請選擇要動作的伺服器。");
  	return true;
  }
  switch($sts) {
  	case "0":
  	case "1":
		location.href="index.php?st=svr_st&v="+$sts+"&ans="+$alln;
		break;
  	case "2":
		$("#del_server_alln").val($alln);
		$("#del_server_modal").modal("show");
		break;
  	default:
		alert("類型出錯。");
		break;
  }  
}
function allseln($this) {
	$("input[name='seln']:checkbox").not($this).prop("checked", $this.prop("checked"));
}
</script>