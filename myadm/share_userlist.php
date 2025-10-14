<?include("include.php");

check_login();
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
								<strong>子帳號管理</strong> <!-- panel title -->								
							</span>

							<!-- right options -->
							<ul class="options pull-right list-inline">								
								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
							</ul>
							<!-- /right options -->

						</div>

						<!-- panel content -->
						<div class="panel-body">
							<div class="panel-btn" style='display: flex;'>
								<a id='btn_new_account' href="share_useradd.php" class="btn btn-info btn-sm"><i class="fa-solid fa-plus"></i>新增子帳號</a>
								<button id='btn_recycle' class="btn btn-success btn-sm" style='margin-left: 3px;'><i class="fa-solid fa-trash"></i>刪除回收區</button>
								<button id='last_page' class="btn btn-info btn-sm" style='display: none;'><i class="fa-solid fa-backward"></i>回上一頁</button>
							</div>
							<div class="table-responsive">
	<table class="table table-bordered">
			<thead>
				<tr>
					<th>姓名</th>    
					<th>帳號</th>
					<th>分享數</th>	
					<th>管理數</th>	
					<th>贊助成功</th>	
					<th>管理伺服器</th>
					<th>建立時間</th>
					<th>　</th>
				</tr>
			</thead>
			<tbody id='tbody'>
	
    </tbody>
	</table>
</div>
<div id='pagestr'></div>

						</div>
						<!-- /panel content -->


					</div>

				</div>
			</section>
			<!-- /MIDDLE -->

<?down_html()?>

<script>
	$(document).ready(function () {
		check_delete();
		searching();

		function searching(){
			default_render();
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'searching',
					offset: '<? echo isset($_GET['offset']) ? $_GET['offset'] : 0 ;?>'
				},
				success(data){
					if (data.success){
						const tableData = data.data;
						const tbodyHtml = render_table(tableData, 0);						
						
						$('#pagestr').html(data.page);
					}
				}
			}
			callApi(api_data);
		}

		function recycle(){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'recycle',
					offset: '<? echo isset($_GET['offset']) ? $_GET['offset'] : 0 ;?>'
				},
				success(data){
					if (data.success){
						const tableData = data.data;
						const tbodyHtml = render_table(tableData, 1);						
						
						$('#pagestr').html(data.page);
					}
				}
			}
			callApi(api_data);
		}

		$('#btn_recycle').click(function(){
			$('#btn_new_account').css('display', 'none');
			$('#btn_recycle').css('display', 'none');
			$('#last_page').css('display', 'block');
			recycle();
		});

		$('#last_page').click(function(){
			searching();
		})

		$('#tbody').delegate('.btn_open', 'click', function(){
			open_account($(this).attr('data_id'));
		})

		$('#tbody').delegate('.btn_close', 'click', function(){
			close_account($(this).attr('data_id'));
		})

		$('#tbody').delegate('.btn_del', 'click', function(){
			delete_account($(this).attr('data_id'));
		})

		$('#tbody').delegate('.btn_cancel_del', 'click', function(){
			cancel_delete_account($(this).attr('data_id'));
			searching();
		})

		function open_account(id){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'open',
					id: id
				},
				success(data){
					if (data.success){
						alert(data.msg);
						searching();	
					}
				}
			}
			callApi(api_data);
		}

		function close_account(id){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'close',
					id: id
				},
				success(data){
					if (data.success){
						alert(data.msg);	
						searching();
					}
				}
			}
			callApi(api_data);
		}

		function delete_account(id){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'delete',
					id: id
				},
				success(data){
					if (data.success){
						alert(data.msg);	
						searching();
					}
				}
			}
			callApi(api_data);
		}

		function cancel_delete_account(id){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'cancel_delete',
					id: id
				},
				success(data){
					if (data.success){
						alert(data.msg);	
						searching();
					}
				}
			}
			callApi(api_data);
		}

		function check_delete(){
			const api_data = {
				url: './api/share_userlist.php',
				data: {
					action: 'check_delete'
				},
				success(data){}
			}
			callApi(api_data);
		}

		function render_table(tableData, action){
			let tbodyHtml = '';
			tableData.forEach(function(data){

				// 管理伺服器
				let manage_server = '';
				if (data.manage_server.length > 0){
					manage_server = data.manage_server.map(function(server){
						return `<span class="label label-info" style="font-size: 12px;">${server.name}</span>`;
					}).join(' ');
				}

				tbodyHtml += `
					<tr>
					<td align='center'>${data.names}</td>
					<td align='center'>${data.uid}</td>
					<td align='center'>${data.share_count}</td>
					<td align='center'>${data.manage_count}</td>
					<td align='center'>${data.sponsor}</td>
					<td align='center'>${manage_server}</td>
					<td align='center'>${data.times}</td>
					<td align="left">
				`
				if (action == 0){
					if (data.total > 0){
						tbodyHtml += `<a class="btn btn-default btn-xs" href="share_link.php?uid='${data.uid}'"><i class="fa fa-link white"></i> 分享連結</a>`
					}

					tbodyHtml += `<a class="btn btn-default btn-xs" href="share_useradd.php?an=${data.auton}"><i class="fa fa-edit white"></i> 修改</a>`;
					tbodyHtml += `<button data_id='${data.auton}' class="btn_del btn btn-default btn-xs"><i class="fa fa-trash"></i>刪除</button>`;
					if (data.stats == -1){
						tbodyHtml += `<button data_id='${data.auton}' class="btn_open btn btn-success btn-xs"><i class="fa fa-check white"></i> 開啟</button>`;								
					}else{
						tbodyHtml += `<button data_id='${data.auton}' class="btn_close btn btn-danger btn-xs"><i class="fa fa-times white"></i> 關閉</button>`;
					}
				}else{
					tbodyHtml += `<button data_id='${data.auton}' class="btn_cancel_del btn btn-success btn-xs"><i class="fa-solid fa-clock-rotate-left"></i> 取消刪除</button>`;
				}
				tbodyHtml += '</td></tr>';
			})

			$('#tbody').empty();
			$('#tbody').html(tbodyHtml);
		}

		function default_render(){
			$('#btn_new_account').css('display', 'inline');
			$('#btn_recycle').css('display', 'block');
			$('#last_page').css('display', 'none');
		}
	});

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
</script>