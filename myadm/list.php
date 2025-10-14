<?include("include.php");

check_login();

if($_REQUEST["st"] == "svr_del") {
	if($_REQUEST["del_server_alln"] == "") alert("編號錯誤。", 0);	
	
	$pdo = openpdo(); 	  
    $query = $pdo->prepare("update servers_log set indel=1 where auton in (".$_REQUEST["del_server_alln"].")");    
    $query->execute($input);
    
    alert("刪除完成。", 0);
    die();
}
$isdel = $_GET["isdel"];

if($isdel == "1") $tts = "刪除回收區";
  
top_html();
?>
			<section id="middle">
				<div id="content" class="dashboard padding-20">

					<div id="panel-1" class="panel panel-default">
						<div class="panel-heading">
							<span class="title elipsis">
								<strong><a href="list.php">贊助紀錄</a></strong> <!-- panel title -->								
								<?if($tts) echo "<small>".$tts."</small>"?>
								<?if($_GET["keyword"] != "") echo "<small>搜尋：".$_GET["keyword"]."</small>"?>
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>
						<?
						$d1 = $_REQUEST["d1"];
						$d2 = $_REQUEST["d2"];						
						if($d1 == "") $d1 = date('Y-m-d', strtotime("-7 days"));;
						if($d2 == "") $d2 = date("Y-m-d");
						
						?>
						<!-- panel content -->
						<div class="panel-body">
							<? $test_mode = 1; ?>
							<form name="form1" method="get" <? if ($test_mode == 0){ ?> action="list.php" <? } ?> class="form form-inline nomargin noborder padding-bottom-10">
								<div class="form-group">
									<? if ($test_mode == 0){ ?>
										<input type="text" name="d1" id="d1" class="form-control datepicker" value="<?=$d1;?>" placeholder="開單日期">
										<span>至</span>
										<input type="text" name="d2" id="d2" value="<?=$d2;?>" class="form-control datepicker" placeholder="開單日期">
									<? }else{ 
										$week_first_day = date('Y-m-d', strtotime("-7 days"));
										$date_1 = (isset($_GET['date_1'])) ? ($_GET['date_1']) : $week_first_day;
										$date_2 = (isset($_GET['date_2'])) ? ($_GET['date_2']) : date('Y-m-d');
									?>
										<input type="text" name="d1" id="d1" class="form-control datepicker" value="<?=$date_1;?>" placeholder="開單日期">
										<span>至</span>
										<input type="text" name="d2" id="d2" value="<?=$date_2;?>" class="form-control datepicker" placeholder="開單日期">
									<? } ?>
									
								</div>
								<div class="form-group">
									<input type="hidden" name="isdel" value="<?=$isdel?>">
									<input type="<? echo ($test_mode == 0) ? 'submit' : 'button';?>" class="form_submit btn btn-default" value="查詢">
								</div>
						  	</form>	
              				<form name="form2" method="get" <? if ($test_mode == 0){ ?> action="list.php" <? } ?> class="form-inline nomargin noborder padding-bottom-10">	              	              	              	
						   		<div class="form-group">
									<input type="text" name="keyword" id="keyword" class="form-control" placeholder="遊戲帳號/角色名稱/伺服器名稱/尾綴代號/訂單編號" value="<?=$_REQUEST["keyword"]?>">                
								</div>
               					<div class="form-group">
									<select name="rstat" id="rstat" class="form-control">
										<option value="" <? if ($test_mode == 1 && $_GET['rstat'] == '') echo 'selected'; ?>>所有狀態</option>
										<option value="0" <? if ($test_mode == 1 && $_GET['rstat'] == '0') echo 'selected'; ?>>等待付款</option>
										<option value="1" <? if ($test_mode == 1 && $_GET['rstat'] == '1') echo 'selected'; ?>>付款完成</option>
										<option value="2" <? if ($test_mode == 1 && $_GET['rstat'] == '2') echo 'selected'; ?>>付款失敗</option>
										<option value="3" <? if ($test_mode == 1 && $_GET['rstat'] == '3') echo 'selected'; ?>>模擬付款完成</option>
									</select>
								</div>
								<div class="form-group">
									<input type="hidden" name="isdel" value="<?=$isdel?>">
									<input type="<? echo ($test_mode == 0) ? 'submit' : 'button';?>" class="form_submit btn btn-default" value="搜尋">
								</div>
               					<div class="form-group">
									<input id='is_delete_recycle' value='0' type='hidden'/>
									<?// if($isdel == "1") {?>               
										<a href="list" id='last_page' class="btn btn-primary" style='display: none;'><i class="fa-solid fa-backward"></i> 上一頁</a>
									<?// } else {?>
										<a href="#r" id='btn_delete' onclick="ch_seln('2')" class="btn btn-danger"><i class="fa fa-remove"></i> 刪除</a>
										<? if ($test_mode == 0):?>
											<a href="list.php?isdel=1" class="btn btn-info"><i class="glyphicon glyphicon-th-list"></i> 刪除回收區</a>           
										<? endif ?>
										<? if ($test_mode == 1):?>
											<button type='button' id='btn_delete_recycle' class="btn btn-info"><i class="fa-solid fa-trash"></i>刪除回收區</button>
										<? endif ?>
									<?// }?>
               					</div>
						  </form>		
						  			
							<div class="table-responsive">
	              				<table class="table table-hover">
						      	<thead>
						  	  		<tr>
						  	  			<th><input type="checkbox" onclick="allseln($(this))"></th>
										<th>伺服器名稱</th>
										<th>金流</th>
										<th>繳費方式</th>
										<th>遊戲帳號</th>
										<th>換算金額</th>
										<th>應繳金額</th>
										<th>手續費</th>
										<th>金流回傳</th>
										<th>開單日期</th>
										<th>付款日期</th>
										<th>分享來源</th>	
										<th>目前狀態</th>
										<th></th>
                  					</tr>
              					</thead>
								<tbody id='form_tbody'>
								<? if ($test_mode == 0){ ?>
								<?
									$rstat = $_REQUEST["rstat"];
									$kword = $_REQUEST["keyword"];

									if($isdel == "1") $sql = "indel=1";
									else $sql = "indel=0";

									if($kword != "") {
										$sql .= " and (orderid like '%$kword%' or forname like '%$kword%' or serverid like '%$kword%' or gameid like '%$kword%' or charid like '%$kword%')";
									}
									if($d1 != "" && $d2 != "") {
										$sql .= " and (times between '$d1 00:00' and '$d2 23:59')";
									}
									if($rstat != "") {
										$sql .= " and (stats = '$rstat')";
									}
									
									$pdo = openpdo();
									// 運行 SQL    
									$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset']:0;
									$limit_row = 20;
									$query    = $pdo->query("SELECT count(auton) as t FROM servers_log where ".$sql."");
									$numsrow = $query->fetch()["t"];
									
									$pagestr = pages($numsrow, $offset, $limit_row);
									
									// $query = $pdo->query("SELECT * FROM servers_log where ".$sql." order by auton desc limit ".$offset.", ".$limit_row."");
									// $sql_str = "SELECT * FROM servers_log where ".$sql." order by auton desc";
									// echo $sql_str; die();

									$query = $pdo->query("SELECT * FROM servers_log where ".$sql." order by auton desc");
									$query->execute();
									if(!$datalist = $query->fetchAll()) {
										echo "<tr><td colspan=7>暫無資料</td></tr>";
									} else {
										foreach ($datalist as $datainfo) {
													
											echo "<tr>";
											echo '<td><input type="checkbox" name="seln" value="'.$datainfo["auton"].'"></td>';
											echo '<td>'.$datainfo["forname"].'['.$datainfo["serverid"].']</td>';
											echo '<td>'.pay_cp_name($datainfo["pay_cp"]).'</td>';
											echo '<td>'.pay_paytype_name($datainfo["paytype"]).'</td>';
										
											echo "<td><a href='list_v.php?an=".$datainfo["auton"]."'>".$datainfo["gameid"]."</a></td>";
											echo "<td>".$datainfo["bmoney"]."</td>";
											echo "<td>".$datainfo["money"]."</td>";
											echo "<td>".$datainfo["hmoney"]."</td>";    	
											echo "<td>".$datainfo["rmoney"]."</td>";
											echo "<td>".$datainfo["times"]."</td>";

											if($datainfo["paytimes"] == "0000-00-00 00:00:00") $paytimes = "";
											else $paytimes = $datainfo["paytimes"];
											
											echo "<td>".$paytimes."</td>";
											
											switch($datainfo["stats"]) {
												case 0:
												$stats = '<span class="label label-primary">等待付款</span>';
												$mockPay = '<a href="javascript:mockPay(\''.$datainfo["auton"].'\');" class="btn btn-warning btn-xs">模擬付款</a>';
												break;

												case 1:
												$stats = '<span class="label label-success">付款完成</span>';
												if ($datainfo["RtnMsg"] == "模擬付款成功") $stats = '<span class="label label-info">模擬付款完成</span>';
												$mockPay = "";
												break;
												
												case 2:
												$stats = '<span class="label label-danger">付款失敗</span>';
												$mockPay = '<a href="javascript:mockPay(\''.$datainfo["auton"].'\');" class="btn btn-warning btn-xs">模擬付款</a>';
												break;

												default:
												$stats = "不明";
												$mockPay = "";
												break;
											}
											echo "<td>".$datainfo["shareid"]."</td>";
											echo "<td>".$stats."</td>";
											echo "<td>".$mockPay."</td>";
											echo "</tr>";
										}
									}
								?>
								<? } ?>
						  	</tbody>
						</table>
					</div>
					<? if ($test_mode == 0) {
						echo $pagestr;
					}else{ ?>
						<span id='pagestr'></span>
					<? } ?>
					

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
				<p>是否確定要將贊助資料刪除？<br><br><b>刪除後可在刪除回收區查看資料。</b></p>
			</div>
      		<form method="post" <? if ($test_mode == 0){ ?> action="list.php" <? } ?> class="nomargin noborder">
			<!-- Modal Footer -->
				<div class="modal-footer">
					<input type="hidden" id="del_server_alln" name="del_server_alln" value="">
					<input type="hidden" name="st" value="svr_del">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消刪除</button>
					<button type="<? echo ($test_mode == 0) ? 'submit' : 'button';?>" id='btn_delete_confirm' class="btn btn-danger">確定刪除</button>
				</div>
		  	</form>
		</div>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function () {

	<? if ($test_mode == 1){ ?> 
		/* 載入執行 */
		searching();

		$('.form_submit').click(function(){
			searching();
		})

		$('#btn_delete_confirm').click(function(){
			delete_data();
		})

		$('#btn_delete_recycle').click(function(){
			let is_delete_recycle_val = $('#is_delete_recycle').val();
			if (is_delete_recycle_val == 0){
				new_state = 1;
			}else if (is_delete_recycle_val == 1){
				new_state = 0;
			}
			$('#is_delete_recycle').val(new_state);
			
			is_delete_recycle(new_state);
			delete_recycle_data();
		})

		function is_delete_recycle(type){
			if (type == 0){
				$('#last_page').css('display', 'none');
				$('#btn_delete').css('display', 'inline');
				$('#btn_delete_recycle').css('display', 'inline-block');
			}else{
				$('#last_page').css('display', 'inline');
				$('#btn_delete').css('display', 'none');
				$('#btn_delete_recycle').css('display', 'none');
			}
		}

		/* 搜尋 */
		function searching(){
			const api_data = {
				url: './api/list.php',
				data: {
					action: 'searching',
					date_1: get_value('d1'),
					date_2: get_value('d2'),
					is_delete: get_value('isdel'),
					keyword: get_value('keyword'),
					rstat: get_value('rstat'),
					offset: '<? echo isset($_GET['offset']) ? $_GET['offset'] : 0 ;?>'
				},
				success(data){
					if (data.success){
						$('#pagestr').html(data.page);
						$('#form_tbody').html(data.table_html);
					}
				}
			}
			callApi(api_data);
		}

		/* 刪除 */
		function delete_data(){
			const api_data = {
				url: './api/list.php',
				data: {
					action: 'delete',
					del_server_alln: get_value('del_server_alln'),
				},
				success(data){
					if (data.success){
						alert(data.msg);
						history.go(0);
					}
				}
			}
			callApi(api_data);
		}

		/* 刪除回收區 */
		function delete_recycle_data(){
			const api_data = {
				url: './api/list.php',
				data: {
					action: 'delete_recycle',
				},
				success(data){
					if (data.success){
						$('#form_tbody').html(data.table_html);
					}
				}
			}
			callApi(api_data);
		}

	<? } ?>

	function get_value(element){
		const input_list = [
			'd1',						// 開始日
			'd2',						// 結束日
			'isdel',					
			'keyword',					// 關鍵字
			'del_server_alln',
			'st'
		];
		const select_list = ['rstat'];	// 狀態
		
		if (input_list.includes(element)){
			value = $(`#${element}`).val();
		}else{
			value = $(`#${element}`).find(':selected').val();
		}

		return value;
	}

	function callApi(api_data){
		$.ajax({
			type: "POST",
			url: api_data.url,
			data: api_data.data,
			dataType: "json",
			success: function(data) {
				api_data.success(data);
			}
		})
	}
});
$(function() {

});

function ch_seln($sts) {
	var $alln = [];
	$("input[name='seln']:checked").each(function() {
		console.log($(this).val());
		$alln.push($.trim($(this).val()));
	});
  
	if(!$alln.length) {
		alert("請選擇要動作的伺服器。");
		return true;
	}

  switch($sts) {  
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