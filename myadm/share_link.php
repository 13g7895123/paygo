<?include("include.php");

check_login_share();

top_html();

    $uid = _r("uid");
	if(empty($uid)) $uid = _s("shareid");
	//如果是直播主要檢查是不是他的資料
	if(!empty(_s("shareid"))) $uid = _s("shareid");
	if(empty($uid)) alert("讀取資料錯誤。");

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
								<strong><?=$uid?> 分享詳情 - 伺服器列表</strong> <!-- panel title -->								
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
	<table class="table table-bordered">
						  <thead>
  <tr>
    <th>伺服器名稱</th>
    <th>尾綴代號</th>
	<th>金流</th>	
	<th>筆數</th>	
	<th>成功</th>	
    <th>儲值總額</th>
    <th>分享連結</th>
	<th>開始分享</th>
    <th>運作狀態</th>
	<th></th>
  </tr>
  <?
	$pdo = openpdo();	
	$query    = $pdo->query("SELECT *, (SELECT count(auton) FROM servers_log where shareid=a.uid and foran=servers.auton) as total, (SELECT count(auton) FROM servers_log where shareid=a.uid and foran=servers.auton and stats=1) as total2, (SELECT sum(rmoney) FROM servers_log where shareid=a.uid and foran=servers.auton and stats=1) as sponsor FROM shareuser_server as a left join servers on servers.id = a.serverid where uid='".$uid."' order by a.auton desc");
    $query->execute();
    if($datalist = $query->fetchAll()) {
    
    foreach ($datalist as $datainfo)
    {
		echo "<tr>";				
    	echo '<td><a href="'.$weburl.$datainfo["id"].'?s='.$uid.'" target="_blank">'.$datainfo["names"].'</a></td>';
		echo "<td>".$datainfo["id"]."</td>";		
		echo '<td>'.pay_cp_name($datainfo["pay_cp"]).'/'.pay_cp_name($datainfo["pay_cp2"]).'</td>';    	
		echo "<td>".($datainfo["total"] ? $datainfo["total"]:0)."</td>";
		echo "<td>".($datainfo["total2"] ? $datainfo["total2"]:0)."</td>";
		echo "<td>".($datainfo["sponsor"] ? $datainfo["sponsor"]:0)."</td>";
		echo '<td><input type="text" value="'.$weburl.$datainfo["id"].'?s='.$uid.'" onclick="oCopy($(this))" style="width:100%;"></td>'; 
		echo "<td>".chtimed($datainfo["times"])."</td>";	
		if($datainfo["stats"] == 1) $stats = '<span class="label label-success">開啟</span>';
		else $stats = '<span class="label label-warning">停用</span>';
		echo "<td>".$stats."</td>";   
		echo '<td><a href="share_link_list.php?uid='.$uid.'&serverid='.$datainfo["id"].'" class="btn btn-default btn-xs">贊助紀錄</a></td>'; 	
    	echo "</tr>";
	}
}
  ?>
    </tbody>
	</table>
</div>
<?print $pagestr;?>

						</div>
						<!-- /panel content -->


					</div>

				</div>
			</section>
			<!-- /MIDDLE -->

<?down_html()?>
<script type="text/javascript">
    function oCopy($this){
        $this.select();    // 选中输入框中的内容
        
        try{
        if(document.execCommand('copy', false, null)){            
            
        } else{
alert("無法自動複製，請手動按 ctrl + c 複製，謝謝");
        }
    } catch(err){
alert("無法自動複製，請手動按 ctrl + c 複製，謝謝");      
    }
    
    }
</script>
