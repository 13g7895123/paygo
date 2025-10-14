<?php

include("include.php");

$test_mode = 1;		// 1為更新後版本，240512

if ($test_mode == 0):
	check_login();
elseif ($test_mode == 1):
	check_login_share();
endif;

top_html();

?>
<section id="middle">
	<div id="content" class="dashboard padding-20">
		<div id="panel-1" class="panel panel-default">
			<div class="panel-heading">
				<span class="title elipsis">
					<strong><a href="list.php">贊助統計</a></strong> <!-- panel title -->
					<? if ($tts) echo "<small>" . $tts . "</small>" ?>
					<? if ($_REQUEST["keyword"] != "") echo "<small>搜尋：" . $_REQUEST["keyword"] . "</small>" ?>
				</span>

				<!-- right options -->
				<ul class="options pull-right list-inline">
					<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
				</ul>
				<!-- /right options -->

			</div>
			<?php
				$d1 = $_REQUEST["d1"];
				$d2 = $_REQUEST["d2"];
				if ($d1 == "") $d1 = date('Y-m-d', strtotime("-7 days"));
				if ($d2 == "") $d2 = date("Y-m-d");
				$diff = (strtotime($d2) - strtotime($d1)) / (60 * 60 * 24);
				if ($diff < 0) alert("時間設定錯誤。");
				if ($diff > 731) alert("只能搜尋 730 天內的紀錄。");
			?>
			<!-- panel content -->
			<div class="panel-body">
				<form name="form1" method="get" action="counts.php" class="form-inline nomargin noborder padding-bottom-10">
					<div class="form-group">
						<input type="text" name="d1" id="d1" class="form-control datepicker" value="<?= $d1 ?>" placeholder="開單日期"> 至　<input type="text" name="d2" id="d2" value="<?= $d2 ?>" class="form-control datepicker" placeholder="開單日期">
					</div>
					<div class="form-group">
						<select name="serv" id="serv" class="form-control">
							<? 
								if (empty(_s('adminid'))):
									$pdo = openpdo();
									$user_id = _s("shareid");
									$sql_str = "SELECT serverid FROM shareuser_server2 WHERE uid='$user_id'";
									$server2_data = $pdo->query($sql_str)->fetchAll();
									
									$server_id_list = [];
									if (!empty($server2_data)){
										foreach ($server2_data as $_val){
											array_push($server_id_list, "'".$_val['serverid']."'");
										}
									}
									$server_id_list_str = implode(',', $server_id_list);
									
									$sql_str = "SELECT * FROM servers WHERE id IN ($server_id_list_str) order by gp desc, des desc";
									$servlist = $pdo->query($sql_str);
									$servlistq = $servlist->fetchAll();
									if ($servlistq) {
										foreach ($servlistq as $ser) {
											if ($serv == $ser["id"]) echo '<option value="' . $ser["id"] . '" selected>' . $ser["names"] . '[' . $ser["id"] . ']</option>';
											else echo '<option value="' . $ser["id"] . '">' . $ser["names"] . '[' . $ser["id"] . ']</option>';
										}
									}

							?>
							<? elseif (!empty(_s('adminid'))): ?>
								<option value="">所有伺服器</option>
								<?php
									$pdo = openpdo();
									$servlist = $pdo->query("SELECT * FROM servers order by gp desc, des desc");
									$servlistq = $servlist->fetchAll();
									if ($servlistq) {
										foreach ($servlistq as $ser) {
											if ($serv == $ser["id"]) echo '<option value="' . $ser["id"] . '" selected>' . $ser["names"] . '[' . $ser["id"] . ']</option>';
											else echo '<option value="' . $ser["id"] . '">' . $ser["names"] . '[' . $ser["id"] . ']</option>';
										}
									}
								?>
							<? endif ?>
						</select>
						<input type="<? echo ($test_mode == 0) ? 'submit' : 'button';?>" id='btn_submit' class="btn btn-default" value="查詢">
					</div>
				</form>
				<p></p>
				<div id="flot-sin" class="flot-chart"><!-- FLOT CONTAINER --></div>
				<p></p>
				<div class="table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<?php
								echo '<th>' . date("Y") . ' 年</th>';
								echo '<th>一月</th>';
								echo '<th>二月</th>';
								echo '<th>三月</th>';
								echo '<th>四月</th>';
								echo '<th>五月</th>';
								echo '<th>六月</th>';
								echo '<th>七月</th>';
								echo '<th>八月</th>';
								echo '<th>九月</th>';
								echo '<th>十月</th>';
								echo '<th>十一月</th>';
								echo '<th>十二月</th>';
								?>
							</tr>
						</thead>
						<tbody id='month_tbody'></tbody>
					</table>
				</div>
				<hr>
				<div class="table-responsive">
					<table class="table table-hover">
						<tbody id='day_tbody'>
							<?php
							// if ($d1 != "" && $d2 != "") {
							// 	$sql .= " and (times between '$d1 00:00' and '$d2 23:59')" . $ssql;
							// }
							// $chkm = [];
							// $allchkrmoney = 0;
                            // $sql_script = "SELECT DATE(times) as date, SUM(rmoney) as r FROM servers_log where stats=1 and RtnMsg <> '模擬付款成功'". $sql . " GROUP BY DATE(times)";
                            // // echo $sql_script; die();
							// $qq = $pdo->query($sql_script);
							// if ($ql = $qq->fetchAll()) {
							// 	foreach ($ql as $q) {
							// 		$chkm[date("Ymd", strtotime($q["date"]))] = $q["r"];
							// 	}
							// }else{
                            //     // echo $sql_script;
                            // } 

							// $diff++;
							// for ($i = 0; $i < $diff; $i++) {
							// 	echo '<tr>';
							// 	$showdate = date('Y-m-d', strtotime($d2 . ' - ' . $i . ' days'));
							// 	$chkdate = date('Ymd', strtotime($d2 . ' - ' . $i . ' days'));
							// 	echo '<td width=160>' . $showdate . '</td>';
							// 	$chkrmoney = 0;
							// 	if (isset($chkm[$chkdate])) {
							// 		$chkrmoney = $chkm[$chkdate];
							// 	}
							// 	echo '<td>' . $chkrmoney . '</td>';
							// 	echo '</tr>';
							// 	$allchkrmoney = (int)$chkrmoney + $allchkrmoney;
							// }
							// echo '<tr><td>' . $diff . ' 天 合計</td><td>' . $allchkrmoney . '</td></tr>';
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

<? down_html() ?>

<script type="text/javascript">

	$(document).ready(function () {
		searching();
		let response_data;

		$('#btn_submit').click(function(){
			searching();
		})

		function searching(){
			const api_data = {
				url: './api/count.php',
				data: {
					action: 'searching',
					date_1: get_value('d1'),
					date_2: get_value('d2'),
					server: get_value('serv'),
				},
				success(data){
					if (data.success){
						// 更新月份資料
						const month_data = Object.values(data.month);
						const style = 'style="padding: 8px;"';
						let month_html = `<td ${style}>收入</td>`;
						month_data.forEach (function (element, index){
							month_html += `<td ${style}>${element}</td>`;
						})
						$('#month_tbody').html(month_html);

						// 更新日期資料
						const day_data_key = (Object.keys(data.day)).reverse();
						const day_data = (Object.values(data.day)).reverse();
						let day_html = '';
						let day_total = 0;
						day_data_key.forEach(function(element, index){
							day_html += `<tr><td width=160>${element}</td><td>${day_data[index]}</td></tr>`;
							day_total += Number(day_data[index]);
						})
						day_html += `<tr><td>${day_data_key.length}天 合計</td><td>${day_total}</td></tr>`
						$('#day_tbody').html(day_html);

						// 圖表資料
						let month_chart = [];
						let last_month_chart = [];
						const last_month_data = Object.values(data.last_month);
						month_data.forEach(function(element, index){
							const tmp_arr = [(index + 1), element];
							const last_tmp_arr = [(index + 1), last_month_data[index]];
							month_chart.push(tmp_arr);
							last_month_chart.push(last_tmp_arr);
						})
						console.log(month_chart);
						console.log(last_month_chart);
						chart(month_chart, last_month_chart);
					}
				}
			}
			callApi(api_data);
		}

		function get_value(element){
			const input_list = [
				'd1',						// 開始日
				'd2',						// 結束日
			];
			const select_list = ['serv'];	// 狀態
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
				async: false,
				success: function(data) {
					api_data.success(data);
				}
			})
		}

		function chart(month, last_month){
			var $color_border_color = "#eaeaea"; /* light gray 	*/
			$color_grid_color = "#dddddd" /* silver	 	*/
			$color_main = "#E24913"; /* red       	*/
			$color_second = "#6595b4"; /* blue      	*/
			$color_third = "#FF9F01"; /* orange   	*/
			$color_fourth = "#7e9d3a"; /* green     	*/
			$color_fifth = "#BD362F"; /* dark red  	*/
			$color_mono = "#000000"; /* black 	 	*/

			var months = ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"];

			var d = month;
			var d2 = last_month;
			var dataSet = [
				{
					label: "今年收入",
					data: d,
					color: "#FF55A8"
				},
				{
					label: "去年收入",
					data: d2,
					color: "#999999"
				}
			];

			loadScript(plugin_path + "chart.flot/jquery.flot.min.js", function() {
				loadScript(plugin_path + "chart.flot/jquery.flot.resize.min.js", function() {
					loadScript(plugin_path + "chart.flot/jquery.flot.time.min.js", function() {
						loadScript(plugin_path + "chart.flot/jquery.flot.fillbetween.min.js", function() {
							loadScript(plugin_path + "chart.flot/jquery.flot.orderBars.min.js", function() {
								loadScript(plugin_path + "chart.flot/jquery.flot.pie.min.js", function() {
									loadScript(plugin_path + "chart.flot/jquery.flot.tooltip.min.js", function() {

										if (jQuery("#flot-sin").length > 0) {
											var plot = jQuery.plot(jQuery("#flot-sin"), dataSet, {
												series: {
													lines: {
														show: true
													},
													points: {
														show: true
													}
												},
												grid: {
													hoverable: true,
													clickable: false,
													borderWidth: 1,
													borderColor: "#633200",
													backgroundColor: {
														colors: ["#ffffff", "#EDF5FF"]
													}
												},
												tooltip: true,
												tooltipOpts: {
													content: "(%s) %x 月<br/><strong>%y</strong>",
													defaultTheme: false
												},
												colors: [$color_second, $color_fourth],
												yaxes: {
													axisLabelPadding: 3,
													tickFormatter: function(v, axis) {
														return $.formatNumber(v, {
															format: "#,###",
															locale: "nt"
														});
													}
												},
												xaxis: {
													ticks: [
														[1, "一月"],
														[2, "二月"],
														[3, "三月"],
														[4, "四月"],
														[5, "五月"],
														[6, "六月"],
														[7, "七月"],
														[8, "八月"],
														[9, "九月"],
														[10, "十月"],
														[11, "十一月"],
														[12, "十二月"]
													]
												}
											});

										}
									});
								});
							});
						});
					});
				});
			});
		}
		
	})

	
</script>
