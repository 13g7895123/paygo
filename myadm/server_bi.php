<?include("include.php");

check_login_share();

if($_REQUEST["st"] == "svr_st") {
	if($_REQUEST["v"] == "") alert("類型錯誤。", 0);
	if($_REQUEST["ans"] == "") alert("伺服器編號錯誤。", 0);	
	
    $pdo = openpdo(); 
    $input = array(':stats' => $_REQUEST["v"]);
    $query = $pdo->prepare("update servers_bi set stats=:stats where auton in (".$_REQUEST["ans"].")");    
    $query->execute($input);
    
    alert("設定完成。", 0);
    die();
}

if($_REQUEST["st"] == "svr_del") {
	if($_REQUEST["del_alln"] == "") alert("伺服器編號錯誤。", 0);	
	
    $pdo = openpdo(); 	  
    $query = $pdo->prepare("delete from servers_bi where auton in (".$_REQUEST["del_alln"].")");    
    $query->execute($input);
    
    alert("刪除完成。", 0);
    die();
}

if($_REQUEST["st"] == "pay_type_show_change") {

    $pdo = openpdo(); 
    $query = $pdo->prepare("UPDATE servers SET pay_type_show=? WHERE auton=?");  
    $query->execute(array($_GET['pay_type_show'], $_GET['an']));
    
    alert("切換繳費顯示方式完成。", 0);
    die();
}

if($_REQUEST["st"] == "price_switch") {
    if($_REQUEST["v"] == "") alert("類型錯誤。", 0);
	if($_REQUEST["ans"] == "") alert("伺服器編號錯誤。", 0);

    $pdo = openpdo(); 
    $query = $pdo->prepare("UPDATE servers_show_customize_price SET stats=? WHERE id IN (".$_REQUEST["ans"].")");  
    $query->execute(array($_REQUEST["v"]));
    
    alert("切換繳費顯示方式完成。", 0);
    die();
}

if($_REQUEST["st"] == "del_customize_price") {
	if($_REQUEST["del_price"] == "") alert("伺服器編號錯誤。", 0);	
	
    $pdo = openpdo(); 	  
    $query = $pdo->prepare("delete from servers_show_customize_price where id in (".$_REQUEST["del_price"].")");    
    $query->execute();

    alert("刪除完成。", 0);
    die();
}

if($_REQUEST["st"] == "virtual_ratio_change") {

    $pdo = openpdo(); 
    $query = $pdo->prepare("UPDATE servers SET use_virtual_ratio=? WHERE auton=?");  
    $query->execute(array($_GET['virtual_ratio'], $_GET['an']));

    if ($_GET['virtual_ratio'] == 0) $action = '開啟';
    if ($_GET['virtual_ratio'] == 1) $action = '關閉';
    
    alert("幣值換算".$action."。", 0);
    die();
}

top_html();

$pdo = openpdo();
$qq    = $pdo->query("SELECT names, pay_type_show, use_virtual_ratio FROM servers where auton=".$_REQUEST["an"]."");
$result = $qq->fetch();
$names = $result["names"];
$pay_type_show = $result['pay_type_show'];
$virtual_ratio = $result['use_virtual_ratio'];

?>
			<section id="middle">
				<div id="content" class="dashboard padding-20">
					<div id="panel-1" class="panel panel-default">
						<div class="panel-heading">
							<span class="title elipsis">
								<strong><a href="index.php">伺服器管理</a></strong> <!-- panel title -->								
								<small>幣值管理</small> / <small><?=$names?></small>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->
						</div>

						<!-- panel content -->
						<div class="panel-body">
                            <div class="form-group">
                                <a href="server_bi.php?an=<?=$_REQUEST['an']?>&pay_type_show=<?=$pay_type_show == 0 ? 1 : 0?>&st=pay_type_show_change" class='btn btn-success'>切換繳費模式</a>
                            </div>
                            <div>
                                <label><b>繳費金額顯示方式: </b></label>
                                <input type='radio' id='default' name='showPayType' value='0' <? if ($pay_type_show == 0){ ?> checked <?}?> disabled>
                                <label for='default'><b>預設值(使用者自行輸入)</b></label>
                                <input type='radio' id='customize' name='showPayType' value='1'<? if ($pay_type_show == 1){ ?> checked <?}?> disabled>
                                <label for='customize'><b>選取自定義金額</b></label>
                            </div>
                            <div class="form-group">
                                <a href="server_bi.php?an=<?=$_REQUEST['an']?>&virtual_ratio=<?=$virtual_ratio == 0 ? 1 : 0?>&st=virtual_ratio_change" class='btn btn-warning'>關閉幣值換算</a>
                            </div>
                            <div class="form-group" style='margin-bottom:5px;'>
                                <label><b>關閉幣值換算: </b></label>
                                <input type='radio' id='default' name='virtual_ratio' value='0' <? if ($virtual_ratio == 1){ ?> checked <?}?> disabled>
                                <label for='default'><b>是</b></label>
                                <input type='radio' id='customize' name='virtual_ratio' value='1'<? if ($virtual_ratio == 0){ ?> checked <?}?> disabled>
                                <label for='customize'><b>否</b></label>
                            </div>
                            <div class="form-group">
                                <!-- <a href="<?=$_SERVER['HTTP_REFERER']?>" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-left"></i> 上一頁</a> -->
                                <a href="server_bi_add_customize.php?an=<?=$_REQUEST["an"]?>" class="btn btn-info"><i class="fa fa-plus"></i> 新增</a>
                                <a href="#r" onclick="btn_action('2')" class="btn btn-danger"><i class="fa fa-remove"></i> 刪除</a>
                                <a href="#r" onclick="btn_action('1')" class="btn btn-success"><i class="fa-solid fa-check"></i> 開啟</a>	
                                <a href="#r" onclick="btn_action('0')" class="btn btn-warning"><i class="fa-solid fa-xmark"></i> 停用</a>	
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" onclick="selAll($(this))"></th>						  	  
                                            <th>金額</th>
                                            <th>比值</th>
                                            <th>使用狀態</th>									
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <? 
                                        $query = $pdo->query("SELECT * FROM servers_show_customize_price where foran=".$_REQUEST["an"]." order by money DESC");
                                        $query->execute();
                                        if(!$datalist = $query->fetchAll()) {
                                            echo "<tr><td colspan=7>暫無資料</td></tr>";
                                        } else {
                                            foreach ($datalist as $datainfo) {

                                                // 取得幣值
                                                $money = $datainfo["money"];
                                                $foran = $_REQUEST["an"];   // 伺服器ID
                                                $servers_bi_query = $pdo->query("SELECT * FROM servers_bi WHERE money1 <= $money AND money2 >= $money AND foran = $foran");
                                                $servers_bi_query->execute();
                                                if(!$servers_bi = $servers_bi_query->fetchAll()) {
                                                    $currency = 0;
                                                }else{
                                                    foreach ($servers_bi as $servers_bi) {
                                                        $currency = $servers_bi['bi'];
                                                    }
                                                }


                                                if($datainfo["stats"] == 1) $stats = '<span class="label label-success">開啟</span>';
                                                else $stats = '<span class="label label-warning">停用</span>';
                                                echo "<tr>";
                                                echo '<td><input type="checkbox" name="selItem" value="'.$datainfo["id"].'"></td>';
                                                echo "<td>".$datainfo["money"]."</td>";
                                                echo "<td>".$currency."</td>";
                                                echo "<td>".$stats."</td>";
                                                echo '<td><a href="server_bi_add_customize.php?an='.$datainfo["foran"].'&van='.$datainfo["id"].'" class="btn btn-default btn-xs">修改</a></td>';
                                                echo "</tr>";
                                            }
                                        }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <!-- <a href="<?=$_SERVER['HTTP_REFERER']?>" class="btn btn-primary"><i class="glyphicon glyphicon-arrow-left"></i> 上一頁</a> -->
                                <a href="server_bi_add.php?an=<?=$_REQUEST["an"]?>" class="btn btn-info"><i class="fa fa-plus"></i> 新增</a>
                                <a href="#r" onclick="ch_seln('2')" class="btn btn-danger"><i class="fa fa-remove"></i> 刪除</a>
                                <a href="#r" onclick="ch_seln('1')" class="btn btn-success"><i class="fa-solid fa-check"></i> 開啟</a>	
                                <a href="#r" onclick="ch_seln('0')" class="btn btn-warning"><i class="fa-solid fa-xmark"></i> 停用</a>	
                            </div>
							<div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" onclick="allseln($(this))"></th>						  	  
                                            <th>金額範圍</th>
                                            <th>比值</th>
                                            <th>使用狀態</th>									
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <? 
                                        $query = $pdo->query("SELECT * FROM servers_bi where foran=".$_REQUEST["an"]." order by auton desc");
                                        $query->execute();
                                        if(!$datalist = $query->fetchAll()) {
                                            echo "<tr><td colspan=7>暫無資料</td></tr>";
                                        } else {
                                            foreach ($datalist as $datainfo) {
                                                
                                                if($datainfo["stats"] == 1) $stats = '<span class="label label-success">開啟</span>';
                                                else $stats = '<span class="label label-warning">停用</span>';
                                                
                                                echo "<tr>";
                                                echo '<td><input type="checkbox" name="seln" value="'.$datainfo["auton"].'"></td>';
                                                echo "<td>".$datainfo["money1"]." - ".$datainfo["money2"]."</td>";
                                                echo "<td>".$datainfo["bi"]."</td>";
                                                echo "<td>".$stats."</td>";
                                                echo '<td><a href="server_bi_add.php?an='.$datainfo["foran"].'&van='.$datainfo["auton"].'" class="btn btn-default btn-xs">修改</a></td>';
                                                echo "</tr>";
                                            }
                                        }
                                    ?>
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
<div id="del_server_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- Modal Body -->
			<div class="modal-body">
				<p>是否確定要將這些幣值刪除？<br><br><b>請注意刪除後將無法回復紀錄。</b></p>
			</div>
            <form method="post" action="server_bi.php" class="nomargin noborder">
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <input type="hidden" id="del_alln" name="del_alln" value="">
                    <input type="hidden" name="st" value="svr_del">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消刪除</button>
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                </div>
            </form>
		</div>
	</div>
</div>
<div id="del_customize_price" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- Modal Body -->
			<div class="modal-body">
				<p>是否確定要將這些幣值刪除？<br><br><b>請注意刪除後將無法回復紀錄。</b></p>
			</div>
            <form method="post" action="server_bi.php" class="nomargin noborder">
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <!-- 刪除金額 -->
                    <input type="hidden" id="del_price" name="del_price" value="">
                    <!-- 指定動作 -->
                    <input type="hidden" name="st" value="del_customize_price">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消刪除</button>
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                </div>
            </form>
		</div>
	</div>
</div>
<script type="text/javascript">

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
            location.href="server_bi.php?st=svr_st&v="+$sts+"&ans="+$alln;  // ans 伺服器編號
        break;
        case "2":   // 刪除
            $("#del_alln").val($alln);
            $("#del_server_modal").modal("show");
        break;
        default:
            alert("類型出錯。");
        break;
    }  
}

// 按鈕行為
function btn_action($action) {
	var $priceArr = [];
    $("input[name='selItem']:checked").each(function() {
        $priceArr.push($.trim($(this).val()));
    });
  
    if(!$priceArr.length) {
        alert("請選擇要動作的伺服器。");
        return true;
    }
    switch($action) {
        case "0":   // 停用
        case "1":   // 啟用
            location.href="server_bi.php?st=price_switch&v="+$action+"&ans="+$priceArr;  // ans 伺服器編號
        break;
        case "2":   // 刪除
            $("#del_price").val($priceArr);
            $("#del_customize_price").modal("show");
        break;
        default:
            alert("類型出錯。");
        break;
    }  
}

function allseln($this) {
    $("input[name='seln']:checkbox").not($this).prop("checked", $this.prop("checked"));
}

function selAll($this) {
    $("input[name='selItem']:checkbox").not($this).prop("checked", $this.prop("checked"));
}

</script>