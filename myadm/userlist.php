<?include("include.php");

check_login();

if($_REQUEST["st"] == "del") {
	
	  $pdo = openpdo(); 	  	  
    $query    = $pdo->prepare("delete from manager where auton=:an");    
    $query->execute(array(':an' => $_REQUEST["an"]));
    win_alert("刪除成功。");
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
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>

						<!-- panel content -->
						<div class="panel-body">
							<div class="panel-btn"><a href="useradd" class="btn btn-info btn-sm">新增管理者</a></div>
							<div class="table-responsive">
	<table class="table table-bordered">
						  <thead>
  <tr>
    <th>姓名</th>    
    <th>帳號</th>
    <th>最後登入</th>
    <th>建立時間</th>
    <th>　</th>
  </tr>
  <?
    $pdo = openpdo();
 // 運行 SQL    
    $offset = isset($_REQUEST['offset']) ? $_REQUEST['offset']:0;
    $limit_row = 20;
    $query    = $pdo->query("SELECT count(auton) as t FROM manager");
    $numsrow = $query->fetch()["t"];
    
    $pagestr = pages($numsrow, $offset, $limit_row);     
    $query    = $pdo->query("SELECT * FROM manager order by auton desc limit ".$offset.", ".$limit_row."");
    $query->execute();
    $datalist = $query->fetchAll();    
    
    //第一次輸出
    foreach ($datalist as $datainfo)
    {
    ?>
  <tr> 
  <td align="center"><?=$datainfo['names']?></td>
	<td align="center"><?=$datainfo['uid']?></td>
	<td align="center"><?=$datainfo['lasttime']?>(<?=$datainfo['lastip']?>)</td>
	<td align="center"><?=$datainfo['times']?></td>
    <td align="center">
    	<a class="btn btn-default btn-xs" href="useradd.php?an=<?=$datainfo['auton']?>"><i class="fa fa-edit white"></i> 修改</a>    	
      <a class="btn btn-default btn-xs" href="#del" onclick="Mars_popup2('userlist.php?st=del&an=<?=$datainfo['auton']?>', 'udel', 'width=350,height=250')"><i class="fa fa-times white"></i> 刪除</a>      
   </td>
  </tr>
    <?
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