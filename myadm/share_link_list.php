<? include("include.php");

check_login_share();

top_html();

$uid = _r("uid");
if (empty($uid)) $uid = _s("shareid");
//如果是直播主要檢查是不是他的資料
if (!empty(_s("shareid"))) $uid = _s("shareid");
if (empty($uid)) alert("讀取資料錯誤。");


$serverid = _r("serverid");
if (empty($serverid)) alert("讀取資料錯誤。-serverid");

// 是否為刪除區
$isDelte = ($_GET['is_delete'] == 0) ? False : True;

?>
<section id="middle">
	<div id="content" class="dashboard padding-20">

		<div id="panel-1" class="panel panel-default">
			<div class="panel-heading">
				<span class="title elipsis">
					<strong><a href="list"><?= $uid ?> - <?= $serverid ?> 分享詳情 - 贊助紀錄</a></strong> <!-- panel title -->
					<? if ($tts) echo "<small>" . $tts . "</small>" ?>
					<? if ($_REQUEST["keyword"] != "") echo "<small>搜尋：" . $_REQUEST["keyword"] . "</small>" ?>
				</span>

				<!-- right options -->
				<ul class="options pull-right list-inline">
					<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa fa-expand"></i></a></li>
				</ul>
				<!-- /right options -->

			</div>
			<?
			$d1 = $_REQUEST["d1"];
			$d2 = $_REQUEST["d2"];
			if ($d1 == "") $d1 = date('Y-m-d', strtotime("-7 days"));;
			if ($d2 == "") $d2 = date("Y-m-d");

			?>
			<!-- panel content -->
			<div class="panel-body">
				<? $test_mode = 1; ?>
				<form name="form1" method="get" action="share_link_list.php" class="form-inline nomargin noborder padding-bottom-10">
					<div class="form-group">
						<? if ($test_mode == 0) { ?>
							<input type="text" name="d1" id="d1" class="form-control datepicker" value="<?= $d1 ?>" placeholder="開單日期">
							<span>至</span>
							<input type="text" name="d2" id="d2" value="<?= $d2 ?>" class="form-control datepicker" placeholder="開單日期">
						<? } else {
							$week_first_day = date('Y-m-d', strtotime("-7 days"));
							$date_1 = (isset($_GET['date_1'])) ? ($_GET['date_1']) : $week_first_day;
							$date_2 = (isset($_GET['date_2'])) ? ($_GET['date_2']) : date('Y-m-d');
						?>
							<input type="text" name="d1" id="d1" class="form-control datepicker" value="<?= $date_1; ?>" placeholder="開單日期">
							<span>至</span>
							<input type="text" name="d2" id="d2" value="<?= $date_2; ?>" class="form-control datepicker" placeholder="開單日期">
						<? } ?>
					</div>
					<div class="form-group">
						<input type="hidden" name="isdel" value="<?=$isdel?>">
						<input type="hidden" name="uid" value="<?= $uid ?>">
						<input type="hidden" name="serverid" value="<?= $serverid ?>">
						<input type="button" class="form_submit btn btn-default" value="查詢">
					</div>
				</form>
				<form name="form2" method="get" action="share_link_list.php" class="form-inline nomargin noborder padding-bottom-10">
					<div class="form-group">
						<input type="text" name="keyword" id="keyword" class="form-control" placeholder="遊戲帳號/角色名稱/伺服器名稱/尾綴代號/訂單編號" value="<?= $_REQUEST["keyword"] ?>">
					</div>
					<div class="form-group">
						<select name="rstat" id="rstat" class="form-control">
							<option value="">所有狀態</option>
							<option value="0">等待付款</option>
							<option value="1">付款完成</option>
							<option value="2">付款失敗</option>
							<option value="3">模擬付款完成</option>
						</select>
					</div>
					<div class="form-group">
						<input type="hidden" name="uid" value="<?= $uid ?>">
						<input type="hidden" name="serverid" value="<?= $serverid ?>">
						<input type="button" class="form_submit btn btn-default" value="搜尋">
					</div>
					<div class="form-group">
						<input id='is_delete_recycle' value='0' type='hidden'/>
						<a id='last_page' class="btn btn-primary"><i class="fa-solid fa-backward"></i> 上一頁</a>

						<a href="#r" id='btn_delete' onclick="ch_seln('2')" class="btn btn-danger"><i class="fa fa-remove"></i> 刪除</a>
						<button type='button' id='btn_delete_recycle' class="btn btn-info"><i class="fa-solid fa-trash"></i>刪除回收區</button>
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
								<th>目前狀態</th>
								<th></th>
							</tr>
						</thead>
						<tbody id='form_tbody'></tbody>
					</table>
				</div>
				<span id='pagestr'></span>
			</div>
		</div>
	</div>
</section>
<!-- /MIDDLE -->

<? down_html() ?>
<div id="del_server_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- Modal Body -->
			<div class="modal-body">
				<p>是否確定要將贊助資料刪除？<br><br><b>刪除後可在刪除回收區查看資料。</b></p>
			</div>
			<form method="post" class="nomargin noborder">
				<!-- Modal Footer -->
				<div class="modal-footer">
					<input type="hidden" id="del_server_alln" name="del_server_alln" value="">
					<input type="hidden" name="st" value="svr_del">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消刪除</button>
					<button type="button" id='btn_delete_confirm' class="btn btn-danger">確定刪除</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	function ch_seln($sts) {
		var $alln = [];
		$("input[name='seln']:checked").each(function() {
			console.log($(this).val());
			$alln.push($.trim($(this).val()));
		});

		if (!$alln.length) {
			alert("請選擇要動作的資料。");
			return true;
		}

		switch ($sts) {
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
	$(document).ready(function() {
		<? if ($test_mode == 1) { ?>
			/* 載入執行 */
			searching();

			$('.form_submit').click(function() {
				searching();
			})

			$('#btn_delete_confirm').click(function() {
				get_value('del_server_alln');
				delete_data();
			})

			$('#btn_delete_recycle').click(function() {
				let is_delete_recycle_val = $('#is_delete_recycle').val();
				let new_state = 0;
				if (is_delete_recycle_val == 0) {
					new_state = 1;
				} else if (is_delete_recycle_val == 1) {
					new_state = 0;
				}
				$('#is_delete_recycle').val(new_state);

				is_delete_recycle(new_state);
				delete_recycle_data();
			})

			$('#last_page').click(() => {
				let is_delete_recycle_val = $('#is_delete_recycle').val();
				if (is_delete_recycle_val == 1){
					is_delete_recycle(0);
					searching();
				}else{
					history.go(-1);
				}
			})

			function is_delete_recycle(type) {
				if (type == 0) {
					// $('#last_page').css('display', 'none');
					$('#btn_delete').css('display', 'inline');
					$('#btn_delete_recycle').css('display', 'inline-block');
				} else {
					$('#last_page').css('display', 'inline');
					$('#btn_delete').css('display', 'none');
					$('#btn_delete_recycle').css('display', 'none');
				}
			}

			/* 搜尋 */
			function searching() {
				const api_data = {
					url: './api/share_link_list.php',
					data: {
						action: 'searching',
						uid: '<?=$uid;?>',
						serverid: '<?=$serverid;?>',
						date_1: get_value('d1'),
						date_2: get_value('d2'),
						is_delete: get_value('isdel'),
						keyword: get_value('keyword'),
						rstat: get_value('rstat'),
						offset: '<? echo isset($_GET['offset']) ? $_GET['offset'] : 0; ?>'
					},
					success(data) {
						if (data.success) {
							$('#pagestr').html(data.page);
							$('#form_tbody').html(data.table_html);
						}
					}
				}
				callApi(api_data);
			}

			/* 刪除 */
			function delete_data() {
				const api_data = {
					url: './api/list.php',
					data: {
						action: 'delete',
						del_server_alln: get_value('del_server_alln'),
					},
					success(data) {
						if (data.success) {
							alert(data.msg);
							history.go(0);
						}
					}
				}
				callApi(api_data);
			}

			/* 刪除回收區 */
			function delete_recycle_data() {
				const api_data = {
					url: './api/share_link_list.php',
					data: {
						action: 'searching',
						uid: '<?=$uid;?>',
						serverid: '<?=$serverid;?>',
						date_1: get_value('d1'),
						date_2: get_value('d2'),
						is_delete: 1,
						keyword: get_value('keyword'),
						rstat: get_value('rstat'),
						offset: '<? echo isset($_GET['offset']) ? $_GET['offset'] : 0; ?>'
					},
					success(data) {
						if (data.success) {
							$('#pagestr').html(data.page);
							$('#form_tbody').html(data.table_html);
						}
					}
				}
				callApi(api_data);
			}

		<? } ?>

		function get_value(element) {
			const input_list = [
				'd1', // 開始日
				'd2', // 結束日
				'isdel',
				'keyword', // 關鍵字
				'del_server_alln',
				'st'
			];
			const select_list = ['rstat']; // 狀態

			if (input_list.includes(element)) {
				value = $(`#${element}`).val();
			} else {
				value = $(`#${element}`).find(':selected').val();
			}

			return value;
		}

		function callApi(api_data) {
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
</script>