<?include("include.php");

check_login_share();
  
top_html();
?>

<section id="middle">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-gift"></i> 手動派獎
                        </h3>
                        <div class="panel-actions">
                            <button type="button" class="btn btn-sm btn-info" id="itemSettingsBtn" title="道具設定">
                                <i class="fa fa-cog"></i> 道具設定
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <!-- 階段指示器 -->
                        <div class="stage-indicator">
                            <div class="stage-item active" id="stage1-indicator">
                                <div class="stage-number">1</div>
                                <div class="stage-title">選擇伺服器與帳號</div>
                            </div>
                            <div class="stage-item" id="stage2-indicator">
                                <div class="stage-number">2</div>
                                <div class="stage-title">確認資訊並送出</div>
                            </div>
                        </div>

                        <!-- 第一階段：選擇伺服器與帳號 -->
                        <div id="stage1" class="stage-content">
                            <form id="stage1Form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="serverSelect">選擇伺服器 <span class="text-danger">*</span></label>
                                            <select class="form-control" id="serverSelect" name="serverSelect" required>
                                                <option value="">載入中...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gameAccount">遊戲帳號 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="gameAccount" name="gameAccount" placeholder="請輸入遊戲帳號，多個帳號請用逗號分隔" required>
                                            <small class="help-block text-muted">
                                                <i class="fa fa-info-circle"></i>
                                                可輸入多個帳號，用逗號分隔（例：player1,player2,player3）
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="items-section">
                                            <div class="items-header">
                                                <h4>選擇道具與數量</h4>
                                                <div class="items-controls">
                                                    <button type="button" class="btn btn-sm btn-success" id="addItemBtn">
                                                        <i class="fa fa-plus"></i> 新增道具
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div id="itemsContainer" class="items-container">
                                                <!-- 道具項目將通過 JavaScript 動態產生 -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary btn-lg" id="nextStageBtn">
                                            <i class="fa fa-arrow-right"></i> 下一步
                                        </button>
                                        <button type="reset" class="btn btn-default btn-lg">
                                            <i class="fa fa-refresh"></i> 重置
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- 第二階段：確認資訊並送出 -->
                        <div id="stage2" class="stage-content" style="display: none;">
                            <div class="confirmation-section">
                                <h4><i class="fa fa-check-circle"></i> 確認贈送資訊</h4>
                                
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">贈送詳情</h4>
                                    </div>
                                    <div class="panel-body">
                                        <dl class="dl-horizontal">
                                            <dt>伺服器：</dt>
                                            <dd id="confirmServer">-</dd>
                                            <dt>遊戲帳號：</dt>
                                            <dd id="confirmGameAccount">-</dd>
                                            <dt>選擇道具：</dt>
                                            <dd id="confirmItems">-</dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-default btn-lg" id="prevStageBtn">
                                            <i class="fa fa-arrow-left"></i> 上一步
                                        </button>
                                        <button type="button" class="btn btn-success btn-lg" id="finalSubmitBtn">
                                            <i class="fa fa-send"></i> 確認送出禮物
                                        </button>
                                        <button type="button" class="btn btn-info btn-lg" id="queryLogBtn" style="display: none;">
                                            <i class="fa fa-search"></i> 查詢執行記錄
                                        </button>
                                        <button type="button" class="btn btn-warning btn-lg" id="testConnectionBtn" style="display: none;">
                                        <!-- <button type="button" class="btn btn-warning btn-lg" id="testConnectionBtn"> -->
                                            <i class="fa fa-plug"></i> 測試連線
                                        </button>
                                        <button type="button" class="btn btn-primary btn-lg" id="sendNextBtn" style="display: none;">
                                            <i class="fa fa-plus-circle"></i> 發送下一筆
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
<!-- /MIDDLE -->

<!-- 道具設定 Modal -->
<div class="modal fade" id="itemSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-cog"></i> 道具設定
                </h4>
            </div>
            <div class="modal-body">
                <div class="item-settings-section">
                    <h5 class="section-title">新增道具</h5>
                    <form id="itemSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="itemCode">道具編號 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="itemCode" name="itemCode" placeholder="輸入道具編號" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="itemName">道具名稱</label>
                                    <input type="text" class="form-control" id="itemName" name="itemName" placeholder="輸入道具名稱（選填）">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-success" id="addServerItemBtn">
                                    <i class="fa fa-plus"></i> 新增道具
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <hr class="modal-divider">
                
                <div class="server-items-section">
                    <h5 class="section-title">伺服器道具清單</h5>
                    <div class="items-table-container">
                        <div class="table-responsive">
                            <table class="table table-hover items-table" id="serverItemsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">道具編號</th>
                                        <th width="40%">道具名稱</th>
                                        <th width="20%">操作</th>
                                    </tr>
                                </thead>
                                <tbody id="serverItemsTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted table-loading">
                                            <i class="fa fa-spinner fa-spin"></i> 載入中...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

 <?php
down_html();
?>

<style>
/* 表格美化樣式 */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 16px; /* 增加表格整體字體大小 */
}

.table thead th {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    font-weight: 600;
    text-align: center;
    padding: 15px 8px;
    font-size: 16px; /* 增加標題字體大小 */
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table tbody td {
    vertical-align: middle;
    padding: 12px 8px;
    border-top: 1px solid #e9ecef;
    font-size: 15px; /* 增加表格內容字體大小 */
}

/* 載入動畫 */
.loading-spinner {
    padding: 40px;
    text-align: center;
}

.loading-spinner i {
    color: #667eea;
    margin-bottom: 10px;
}

.loading-spinner p {
    color: #6c757d;
    margin: 0;
}

/* 無資料狀態 */
.no-data {
    padding: 40px;
    text-align: center;
}

.no-data i {
    margin-bottom: 15px;
    opacity: 0.5;
}

/* 錯誤狀態 */
.error-message {
    padding: 40px;
    text-align: center;
}

/* 標籤樣式 */
.label {
    padding: 6px 10px;
    border-radius: 12px;
    font-size: 13px; /* 增加標籤字體大小 */
    font-weight: 600;
    text-transform: uppercase;
}

.label-success {
    background-color: #28a745;
    color: white;
}

.label-warning {
    background-color: #ffc107;
    color: #212529;
}

.label-info {
    background-color: #17a2b8;
    color: white;
}

.label-primary {
    background-color: #007bff;
    color: white;
}

/* 徽章樣式 */
.badge {
    padding: 6px 10px;
    border-radius: 10px;
    font-size: 13px; /* 增加徽章字體大小 */
    font-weight: 600;
}

.badge-primary {
    background-color: #007bff;
    color: white;
}



/* 分頁樣式 */
.pagination {
    margin: 20px 0;
    justify-content: center;
}

.pagination > li > a {
    color: #667eea;
    border: 1px solid #dee2e6;
    margin: 0 2px;
    border-radius: 4px;
    padding: 8px 12px;
    transition: all 0.3s ease;
}

.pagination > li > a:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.pagination > .active > a {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    font-weight: bold;
}

.pagination > .disabled > a {
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.5;
}

.pagination > .disabled > a:hover {
    background-color: transparent;
    border-color: #dee2e6;
    color: #6c757d;
    transform: none;
    box-shadow: none;
}

/* 分頁圖示樣式 */
.pagination i {
    font-size: 12px;
}

/* 省略號樣式 */
.pagination .disabled a[href="#"] {
    background-color: transparent;
    border-color: transparent;
    color: #6c757d;
    cursor: default;
}

/* 搜尋區域樣式 */
.form-inline .form-group {
    margin-right: 15px;
    margin-bottom: 10px;
}

.form-inline .form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    font-size: 16px; /* 增加輸入框字體大小 */
}

.form-inline .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background-color: #667eea;
    border-color: #667eea;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #5a6fd8;
    border-color: #5a6fd8;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-default {
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-default:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

/* 搜尋結果統計 */
.search-stats {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 15px;
    font-size: 16px; /* 增加搜尋統計字體大小 */
    color: #6c757d;
}


/* 表單樣式增強 */
.panel {
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border: none;
    border-radius: 8px;
}

.panel-heading {
    background-color: #667eea;
    color: white;
    border-radius: 8px 8px 0 0;
    border: none;
}

.panel-title {
    font-size: 18px;
    font-weight: 600;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* 階段指示器樣式 */
.stage-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    position: relative;
}

.stage-indicator::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 25%;
    right: 25%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 1;
}

.stage-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 200px;
    position: relative;
    z-index: 2;
}

.stage-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.stage-title {
    text-align: center;
    font-size: 14px;
    color: #6c757d;
    font-weight: 600;
    transition: color 0.3s ease;
}

.stage-item.active .stage-number {
    background-color: #667eea;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.stage-item.active .stage-title {
    color: #667eea;
}

.stage-item.completed .stage-number {
    background-color: #28a745;
    color: white;
}

.stage-item.completed .stage-title {
    color: #28a745;
}

.stage-item.completed .stage-number::after {
    /* content: '✓'; */
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 16px;
    color: #28a745;
    font-weight: bold;
}

/* 階段內容樣式 */
.stage-content {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.items-section {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background-color: #f8f9fa;
    margin-top: 15px;
}

.items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.items-header h4 {
    margin: 0;
    color: #495057;
}

.items-controls .btn {
    margin-left: 5px;
}

.items-container {
    min-height: 150px;
}

.item-group {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: white;
    position: relative;
}

.item-group:last-child {
    margin-bottom: 0;
}

.item-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.item-group-title {
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.item-group-remove {
    color: #dc3545;
    cursor: pointer;
    font-size: 18px;
    transition: color 0.3s ease;
}

.item-group-remove:hover {
    color: #c82333;
}

.server-items-list h5 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
}

.list-group-item {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    font-size: 14px;
}

.list-group-item:first-child {
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}

.list-group-item:last-child {
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}

.list-group-item + .list-group-item {
    border-top: 0;
}

.item-name {
    font-weight: 600;
    color: #495057;
}

.item-database {
    font-size: 12px;
    color: #6c757d;
}

.confirmation-section .panel {
    margin-top: 20px;
}

.dl-horizontal dt {
    width: 120px;
    font-weight: 600;
    color: #495057;
    font-size: 16px;
}

.dl-horizontal dd {
    margin-left: 140px;
    font-size: 16px;
    font-weight: 500;
}

/* 按鈕樣式增強 */
.btn-lg {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #667eea;
    border-color: #667eea;
}

.btn-primary:hover {
    background-color: #5a6fd8;
    border-color: #5a6fd8;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Panel heading 按鈕樣式 */
.panel-heading {
    position: relative;
}

.panel-actions {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.panel-actions .btn {
    margin-left: 5px;
}

/* 道具設定 Modal 樣式 */
.modal-lg {
    width: 90%;
    max-width: 800px;
}

.modal-header .modal-title {
    color: #495057;
    font-weight: 600;
    font-size: 18px;
}

.modal-body .form-group label {
    font-weight: 600;
    color: #495057;
    font-size: 15px;
}

.modal-body .form-control {
    font-size: 14px;
}

.modal-body .section-title {
    font-size: 16px;
    font-weight: 600;
}

/* 模組區域樣式 */
.item-settings-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.server-items-section {
    background-color: #ffffff;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.modal-divider {
    margin: 30px 0;
    border-top: 2px solid #e9ecef;
}

/* 表格樣式優化 */
.items-table-container {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background-color: #ffffff;
}

.items-table {
    margin-bottom: 0;
    font-size: 14px;
}

.items-table thead {
    background-color: #667eea;
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.items-table thead th {
    border: none;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 15px 12px;
}

.items-table tbody td {
    vertical-align: middle;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    text-align: center;
}

.items-table tbody tr:hover {
    background-color: #f8f9fa;
}

.items-table tbody tr:last-child td {
    border-bottom: none;
}

.table-loading {
    padding: 40px !important;
    font-size: 16px;
}

.table-loading i {
    margin-right: 8px;
    color: #667eea;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-group-xs .btn {
        padding: 6px 10px;
        font-size: 14px;
    }
    
    .table tbody td {
        padding: 10px 6px;
        font-size: 14px;
    }
    
    .form-inline .form-group {
        margin-right: 10px;
        margin-bottom: 8px;
    }
    
    .form-inline .form-control {
        font-size: 16px;
    }
    
}
</style>

<script type="text/javascript">

// 階段管理變數
let currentStage = 1;
let stageData = {
    server: null,
    gameAccount: '',
    gameAccounts: [],
    items: []
};

// 派獎記錄ID
let currentLogId = null;

// 道具設定相關變數
let itemSettings = {
    itemCode: '',
    itemName: ''
};

// 道具管理相關變數
let serverItems = []; // 伺服器上的所有道具

// 等待 DOM 載入完成
$(document).ready(function () {
    // 載入伺服器列表
    loadServerList();
    
    // 綁定事件處理器
    bindEventHandlers();
    
    // 初始化表單
    initializeForm();
    
    // TODO: 載入道具設定將透過API實作
    
    // 初始化道具組
    initializeItemGroups();
});

// 載入伺服器列表
function loadServerList() {
    const serverSelect = $('#serverSelect');
    serverSelect.html('<option value="">載入中...</option>');
    
    // 從 API 載入伺服器資料
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { action: 'get_servers' },
        dataType: 'json',
        cache: false,
        xhrFields: {
            withCredentials: true
        },
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .done(function(response) {
        console.log('API Response:', response);
        if (response && response.success) {
            // 處理資料格式
            const servers = response.data;
            
            serverSelect.empty();
            serverSelect.append('<option value="">請選擇伺服器</option>');
            
            if (Array.isArray(servers) && servers.length > 0) {
                servers.forEach(function(server) {
                    serverSelect.append(`<option value="${server.id}">${server.name}</option>`);
                });
                
                // 如果只有一個伺服器，自動選擇
                if (servers.length === 1) {
                    serverSelect.val(servers[0].id);
                    stageData.server = {
                        id: servers[0].id,
                        name: servers[0].name
                    };
                    // 載入該伺服器的道具清單
                    loadServerItemsForSelect(servers[0].id);
                }
            } else {
                serverSelect.append('<option value="">沒有可用的伺服器</option>');
            }
        } else {
            serverSelect.html('<option value="">載入失敗</option>');
            showNotification('載入伺服器清單失敗：' + (response.message || '未知錯誤'), 'error');
        }
    })
    .fail(function(xhr, status, error) {
        console.log('API Error - Status:', status);
        console.log('API Error - Error:', error);
        console.log('API Error - Response:', xhr.responseText);
        
        serverSelect.html('<option value="">載入失敗</option>');
        showNotification('載入伺服器清單失敗：連線錯誤', 'error');
        console.error('Load servers error:', error);
    });
}


// 綁定事件處理器
function bindEventHandlers() {
    // 伺服器選擇
    $('#serverSelect').change(function() {
        const selectedValue = $(this).val();
        const selectedText = $(this).find('option:selected').text();
        
        if (selectedValue) {
            stageData.server = {
                id: selectedValue,
                name: selectedText
            };
            // 載入該伺服器的道具清單
            loadServerItemsForSelect(selectedValue);
        } else {
            stageData.server = null;
            serverItems = [];
            updateAllItemOptions();
        }
    });
    
    // 遊戲帳號輸入
    $('#gameAccount').on('input', function() {
        const rawInput = $(this).val().trim();
        stageData.gameAccount = rawInput;

        // 解析多個帳號
        if (rawInput.includes(',')) {
            stageData.gameAccounts = rawInput.split(',').map(acc => acc.trim()).filter(acc => acc.length > 0);
        } else {
            stageData.gameAccounts = rawInput ? [rawInput] : [];
        }
    });
    
    // 新增道具按鈕
    $('#addItemBtn').click(function() {
        addItemGroup();
    });
    
    // 動態綁定移除道具事件
    $(document).on('click', '.item-group-remove', function() {
        removeItemGroup($(this).closest('.item-group'));
    });
    
    // 動態綁定道具輸入事件
    $(document).on('change input', '.item-select, .item-quantity', function() {
        updateStageDataItems();
    });
    
    
    // 下一步按鈕
    $('#nextStageBtn').click(function() {
        if (validateStage1()) {
            goToStage2();
        }
    });
    
    // 上一步按鈕
    $('#prevStageBtn').click(function() {
        goToStage1();
    });
    
    // 最終送出按鈕
    $('#finalSubmitBtn').click(function() {
        handleFinalSubmit();
    });
    
    // 查詢執行記錄按鈕
    $('#queryLogBtn').click(function() {
        queryExecutionLog();
    });
    
    // 測試連線按鈕
    $('#testConnectionBtn').click(function() {
        testGameServerConnection();
    });
    
    // 發送下一筆按鈕
    $('#sendNextBtn').click(function() {
        // 重置表單並回到第一階段
        resetForm();
        goToStage1();
        $('#sendNextBtn').hide();
        showNotification('已清空資料，請輸入下一筆派獎資訊', 'info');
    });
    
    // 重置按鈕
    $('#stage1Form').on('reset', function() {
        setTimeout(function() {
            resetStageData();
        }, 100);
    });
    
    // 道具設定按鈕
    $('#itemSettingsBtn').click(function() {
        openItemSettingsModal();
    });
    
    // 新增伺服器道具
    $('#addServerItemBtn').click(function() {
        addServerItem();
    });
    
    // 道具設定表單輸入監聽
    $('#itemCode').on('input', function() {
        itemSettings.itemCode = $(this).val().trim();
    });

    $('#itemName').on('input', function() {
        itemSettings.itemName = $(this).val().trim();
    });
}


// 初始化表單
function initializeForm() {
    // 初始化第一階段
    currentStage = 1;
    updateStageIndicators();
}

// 重置階段資料
function resetStageData() {
    stageData = {
        server: null,
        gameAccount: '',
        gameAccounts: [],
        items: []
    };
}

// 更新階段指示器
function updateStageIndicators() {
    $('.stage-item').removeClass('active completed');
    
    for (let i = 1; i <= 2; i++) {
        const indicator = $(`#stage${i}-indicator`);
        if (i < currentStage) {
            indicator.addClass('completed');
        } else if (i === currentStage) {
            indicator.addClass('active');
        }
    }
}

// 驗證第一階段
function validateStage1() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查伺服器選擇
    if (!stageData.server) {
        errorMessages.push('請選擇伺服器');
        $('#serverSelect').focus();
        isValid = false;
    }
    
    // 檢查遊戲帳號
    if (!stageData.gameAccount || (stageData.gameAccounts && stageData.gameAccounts.length === 0)) {
        errorMessages.push('請輸入遊戲帳號');
        if (isValid) $('#gameAccount').focus();
        isValid = false;
    }
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 進入第二階段
function goToStage2() {
    // 隱藏第一階段，顯示第二階段
    $('#stage1').hide();
    $('#stage2').show();
    currentStage = 2;
    updateStageIndicators();
    
    // 更新確認資訊
    updateConfirmationDisplay();
}

// 返回第一階段
function goToStage1() {
    $('#stage2').hide();
    $('#stage1').show();
    currentStage = 1;
    updateStageIndicators();
}

// 更新確認顯示
function updateConfirmationDisplay() {
    $('#confirmServer').text(stageData.server ? stageData.server.name : '-');

    // 顯示遊戲帳號（支援多個帳號）
    if (stageData.gameAccounts && stageData.gameAccounts.length > 0) {
        if (stageData.gameAccounts.length === 1) {
            $('#confirmGameAccount').text(stageData.gameAccounts[0]);
        } else {
            $('#confirmGameAccount').html(`
                <strong>${stageData.gameAccounts.length} 個帳號：</strong><br>
                ${stageData.gameAccounts.join('、')}
            `);
        }
    } else {
        $('#confirmGameAccount').text(stageData.gameAccount || '-');
    }
    
    // 顯示選擇的道具
    if (stageData.items.length > 0) {
        let itemsText = '';
        stageData.items.forEach((item, index) => {
            if (index > 0) itemsText += '、';
            // 顯示道具編號，如果有道具名稱則加括號顯示
            let itemDisplayName = item.itemCode || '未知道具';

            // 如果有道具名稱，則加括號顯示道具名稱
            if (item.itemName &&
                item.itemName !== item.itemCode &&
                item.itemName !== '請選擇道具' &&
                item.itemName.trim() !== '') {
                itemDisplayName += ` (${item.itemName})`;
            }

            itemsText += `${itemDisplayName} x${item.quantity}`;
        });
        $('#confirmItems').text(itemsText);
    } else {
        $('#confirmItems').text('尚未選擇道具');
    }
}

// 驗證表單
function validateForm() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查必填欄位
    const recipient = $('#recipient').val().trim();
    const giftType = $('#giftType').val();
    
    if (!recipient) {
        errorMessages.push('請輸入收件人');
        $('#recipient').focus();
        isValid = false;
    }
    
    if (!giftType) {
        errorMessages.push('請選擇禮物類型');
        if (isValid) $('#giftType').focus();
        isValid = false;
    }
    
    // 檢查是否選擇了圖片
    if (selectedImages.length === 0) {
        errorMessages.push('請至少選擇一張圖片');
        isValid = false;
    }
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 處理最終送出
function handleFinalSubmit() {
    // 準備帳號顯示文字
    let accountText;
    if (stageData.gameAccounts && stageData.gameAccounts.length > 1) {
        accountText = `${stageData.gameAccounts.length} 個帳號：${stageData.gameAccounts.join('、')}`;
    } else {
        accountText = stageData.gameAccount;
    }

    // 顯示確認對話框
    const confirmText = `確定要送出禮物嗎？\n\n` +
        `伺服器：${stageData.server.name}\n` +
        `遊戲帳號：${accountText}\n` +
        `選擇道具：${stageData.items.length} 項`;

    if (confirm(confirmText)) {
        submitGiftData();
    }
}

// 送出禮物資料
function submitGiftData() {
    // 顯示載入中
    const submitBtn = $('#finalSubmitBtn');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fa fa-spinner fa-spin"></i> 檢測連線中...').prop('disabled', true);
    
    // 恢復按鈕狀態的函數
    function restoreButton() {
        submitBtn.html(originalText).prop('disabled', false);
    }
    
    // 處理連線測試成功的回調
    function handleConnectionTestSuccess(response) {
        // 檢查連線測試結果
        if (!response.success) {
            showNotification('連線測試失敗：' + response.message, 'error');
            restoreButton();
            return;
        }
        
        const data = response.data;
        
        // 檢查是否有錯誤
        if (!data.connection.success) {
            const errorMsg = '無法連接遊戲伺服器：' + (data.connection.error || '未知錯誤');
            handleConnectionError(errorMsg);
            return;
        }
        
        if (!data.settings.configured) {
            const errorMsg = '派獎設定不完整，請先到伺服器設定頁面完成派獎設定';
            handleConnectionError(errorMsg);
            return;
        }
        
        if (!data.table_check.success) {
            const errorMsg = '資料表檢查失敗：' + (data.table_check.error || '未知錯誤');
            handleConnectionError(errorMsg);
            return;
        }
        
        if (!data.fields_check.success) {
            const errorMsg = '欄位檢查失敗：' + (data.fields_check.error || '未知錯誤');
            handleConnectionError(errorMsg);
            return;
        }
        
        // 連線測試通過，開始送出禮物
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> 送出中...');
        
        // 準備資料
        const giftData = {
            action: 'send_gift',
            server_id: stageData.server.id,
            server_name: stageData.server.name,
            game_account: stageData.gameAccount,
            game_accounts: stageData.gameAccounts && stageData.gameAccounts.length > 0 ? JSON.stringify(stageData.gameAccounts) : null,
            items: JSON.stringify(stageData.items)
        };
        
        // 發送到後端API
        $.ajax({
            url: 'api/gift_api.php',
            method: 'POST',
            data: giftData,
            dataType: 'json',
            timeout: 20000 // 20秒超時
        })
        .done(function(response) {
            if (response.success) {
                // 保存log ID
                currentLogId = response.data.log_id;

                // 隱藏送出按鈕，顯示查詢按鈕和發送下一筆按鈕
                $('#finalSubmitBtn').hide();
                $('#queryLogBtn').show();
                $('#sendNextBtn').show();

                // 顯示成功訊息
                showNotification(response.message || '禮物送出成功！', 'success');

                // 準備帳號顯示文字
                let accountText;
                if (stageData.gameAccounts && stageData.gameAccounts.length > 1) {
                    accountText = `${stageData.gameAccounts.length} 個帳號：${stageData.gameAccounts.join('、')}`;
                } else {
                    accountText = stageData.gameAccount;
                }

                // 準備結果資訊
                let resultInfo = '';
                if (response.data.success_count && response.data.total_count) {
                    resultInfo = `\n派獎結果：${response.data.success_count}/${response.data.total_count} 個帳號成功`;
                    if (response.data.error_messages && response.data.error_messages.length > 0) {
                        resultInfo += `\n錯誤訊息：\n${response.data.error_messages.join('\n')}`;
                    }
                }

                // 顯示詳細資訊
                alert('禮物送出成功！\n\n' +
                      `伺服器：${stageData.server.name}\n` +
                      `遊戲帳號：${accountText}\n` +
                      `道具數量：${stageData.items.length} 項\n` +
                      `記錄編號：${response.data.log_id}` +
                      resultInfo + '\n\n' +
                      `可點擊「查詢執行記錄」按鈕查看詳細的SQL執行資訊`);

                restoreButton();
            } else {
                showNotification('禮物送出失敗：' + (response.message || '未知錯誤'), 'error');
                restoreButton();
            }
        })
        .fail(function(xhr, status, error) {
            let errorMessage = '送出失敗：';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage += xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage += '請求超時';
            } else {
                errorMessage += '連線錯誤';
            }
            
            showNotification(errorMessage, 'error');
            restoreButton();
        });
    }
    
    // 處理連線相關錯誤
    function handleConnectionError(errorMessage) {
        showNotification('送出失敗：' + errorMessage, 'error');
        
        // 詢問是否要查看詳細測試結果
        if (confirm('送出失敗：' + errorMessage + '\n\n是否要查看詳細的連線測試結果？')) {
            testGameServerConnection(true); // 顯示測試連線Modal
        }
        
        restoreButton();
    }
    
    // 先執行背景連線測試
    testGameServerConnection(false)
        .done(handleConnectionTestSuccess)
        .fail(function(xhr, status, error) {
            let errorMessage = '連線測試失敗：';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage += xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage += '連線超時';
            } else {
                errorMessage += '連線錯誤';
            }
            
            handleConnectionError(errorMessage);
        });
}

// 記錄派送資料
function recordDelivery(giftData) {
    // 新增派送記錄
    const deliveryRecord = {
        id: Date.now(), // 簡單的ID生成
        ...giftData,
        status: '已送出',
        created_at: new Date().toLocaleString('zh-TW')
    };
    
    // TODO: 送出到 API 保存派送記錄
    console.log('派送記錄:', deliveryRecord);
}

// 重置表單
function resetForm() {
    // 清空表單資料
    $('#serverSelect').val('');
    $('#gameAccount').val('');
    
    // 重置階段資料
    resetStageData();
    
    // 重置log ID和按鈕狀態
    currentLogId = null;
    $('#finalSubmitBtn').show();
    $('#queryLogBtn').hide();
    $('#sendNextBtn').hide();
    
    // 重新初始化道具組
    initializeItemGroups();
    
    // 回到第一階段
    goToStage1();
}

// 取得道具類型文字
function getItemTypeText(itemType) {
    const itemTypeText = {
        'points': '點數',
        'coupon': '優惠券',
        'item': '實體道具'
    };
    return itemTypeText[itemType] || itemType;
}

// 顯示派送記錄（可選功能）
function showDeliveryRecords() {
    // TODO: 從 API 載入派送記錄
    alert('此功能將透過 API 實作');
}

// 開啟道具設定 Modal
function openItemSettingsModal() {
    // 檢查是否已選擇伺服器
    const selectedServerId = $('#serverSelect').val();
    if (!selectedServerId) {
        // 如果沒有選擇伺服器，自動focus到伺服器選擇欄位
        $('#serverSelect').focus();
        showNotification('請先選擇伺服器', 'warning');
        return;
    }
    
    $('#itemSettingsModal').modal('show');
    
    // 載入現有設定到表單
    $('#itemCode').val(itemSettings.itemCode);
    $('#itemName').val(itemSettings.itemName);
    
    // 載入伺服器道具清單
    loadServerItems();
}


// 驗證道具設定表單
function validateItemSettings() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查必填欄位 - 只有道具編號是必填的
    if (!itemSettings.itemCode) {
        errorMessages.push('請輸入道具編號');
        $('#itemCode').focus();
        isValid = false;
    }
    
    // 道具名稱為非必填，移除驗證
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 新增伺服器道具
function addServerItem() {
    // 驗證表單
    if (!validateItemSettings()) {
        return;
    }
    
    // 檢查是否選擇了伺服器
    if (!stageData.server || !stageData.server.id) {
        alert('請先選擇伺服器');
        return;
    }
    
    const addBtn = $('#addServerItemBtn');
    const originalText = addBtn.html();
    addBtn.html('<i class="fa fa-spinner fa-spin"></i> 新增中...').prop('disabled', true);
    
    // 儲存到 API
    $.ajax({
        url: 'api/gift_api.php',
        method: 'POST',
        data: {
            action: 'add_server_item',
            server_id: stageData.server.id,
            item_code: itemSettings.itemCode,
            item_name: itemSettings.itemName
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            // 重新載入伺服器道具清單（用於選擇）
            loadServerItemsForSelect(stageData.server.id);
            
            // 重新載入伺服器道具清單（用於顯示）
            loadServerItems();
            
            // 顯示成功訊息
            showNotification('道具新增成功！', 'success');
            
            // 清空表單
            $('#itemCode').val('');
            $('#itemName').val('');
            itemSettings.itemCode = '';
            itemSettings.itemName = '';
        } else {
            showNotification('道具新增失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        showNotification('道具新增失敗：連線錯誤', 'error');
        console.error('Add server item error:', error);
    })
    .always(function() {
        addBtn.html(originalText).prop('disabled', false);
    });
}


// 載入伺服器道具清單（用於選擇選項）
function loadServerItemsForSelect(serverId) {
    if (!serverId) {
        serverItems = [];
        updateAllItemOptions();
        return;
    }
    
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { 
            action: 'get_server_items',
            server_id: serverId 
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            serverItems = response.data.map(item => ({
                id: item.id,
                itemCode: item.item_code,
                itemName: item.item_name
            }));
            updateAllItemOptions();
        } else {
            serverItems = [];
            updateAllItemOptions();
            showNotification('載入道具清單失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        serverItems = [];
        updateAllItemOptions();
        showNotification('載入道具清單失敗：連線錯誤', 'error');
        console.error('Load server items error:', error);
    });
}

// 載入已儲存的道具設定
function loadSavedItemSettings() {
    // 這個函數在選擇伺服器時會被調用
    itemSettings = {
        itemCode: '',
        itemName: ''
    };
}

// 初始化道具組（預設5組）
function initializeItemGroups() {
    const container = $('#itemsContainer');
    container.empty();
    
    // 建立5個預設道具組
    for (let i = 0; i < 5; i++) {
        addItemGroup();
    }
}

// 新增道具組
function addItemGroup() {
    const container = $('#itemsContainer');
    const groupIndex = container.children('.item-group').length;
    
    const itemGroup = $(`
        <div class="item-group" data-index="${groupIndex}">
            <div class="item-group-header">
                <h6 class="item-group-title">道具 ${groupIndex + 1}</h6>
                <span class="item-group-remove" title="移除此道具">
                    <i class="fa fa-times"></i>
                </span>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>選擇道具</label>
                        <select class="form-control item-select" data-index="${groupIndex}">
                            <option value="">請選擇道具</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>數量</label>
                        <input type="number" class="form-control item-quantity" data-index="${groupIndex}" value="1" min="1">
                    </div>
                </div>
            </div>
        </div>
    `);
    
    container.append(itemGroup);
    
    // 載入道具選項
    updateItemOptions(itemGroup.find('.item-select'));
    
    // 更新移除按鈕狀態
    updateRemoveButtons();
}

// 移除道具組
function removeItemGroup(itemGroup) {
    const container = $('#itemsContainer');
    const totalGroups = container.children('.item-group').length;
    
    // 至少保留一組
    if (totalGroups > 1) {
        itemGroup.remove();
        updateItemIndices();
        updateStageDataItems();
        updateRemoveButtons();
    }
}

// 更新道具組索引
function updateItemIndices() {
    $('#itemsContainer .item-group').each(function(index) {
        $(this).attr('data-index', index);
        $(this).find('.item-group-title').text(`道具 ${index + 1}`);
        $(this).find('.item-select, .item-quantity').attr('data-index', index);
    });
}

// 更新移除按鈕狀態
function updateRemoveButtons() {
    const container = $('#itemsContainer');
    const totalGroups = container.children('.item-group').length;
    
    if (totalGroups <= 1) {
        $('.item-group-remove').hide();
    } else {
        $('.item-group-remove').show();
    }
}

// 更新道具選項
function updateItemOptions(selectElement) {
    selectElement.empty();
    selectElement.append('<option value="">請選擇道具</option>');
    
    serverItems.forEach(function(item) {
        // 優先顯示道具編號，如果有道具名稱則一併顯示
        let displayText = item.itemCode;
        if (item.itemName && item.itemName !== item.itemCode) {
            displayText += ` (${item.itemName})`;
        }
        selectElement.append(`<option value="${item.itemCode}">${displayText}</option>`);
    });
}

// 更新所有道具選項
function updateAllItemOptions() {
    $('.item-select').each(function() {
        const currentValue = $(this).val();
        updateItemOptions($(this));
        $(this).val(currentValue);
    });
}

// 更新階段資料中的道具
function updateStageDataItems() {
    const items = [];
    
    $('#itemsContainer .item-group').each(function() {
        const itemSelect = $(this).find('.item-select');
        const itemQuantity = $(this).find('.item-quantity');
        
        const itemValue = itemSelect.val();
        const quantity = parseInt(itemQuantity.val()) || 1;
        
        if (itemValue) {
            const itemDisplayText = itemSelect.find('option:selected').text();
            // 找到對應的道具資料
            const selectedItem = serverItems.find(item =>
                item.itemCode === itemValue
            );
            items.push({
                itemCode: itemValue,
                itemName: selectedItem ? selectedItem.itemName : '',
                displayText: itemDisplayText,
                quantity: quantity
            });
        }
    });
    
    stageData.items = items;
}

// 載入伺服器道具清單
function loadServerItems() {
    if (!stageData.server || !stageData.server.id) {
        const tableBody = $('#serverItemsTableBody');
        tableBody.html('<tr><td colspan="3" class="text-center text-muted">請先選擇伺服器</td></tr>');
        return;
    }
    
    const tableBody = $('#serverItemsTableBody');
    tableBody.html('<tr><td colspan="3" class="text-center"><i class="fa fa-spinner fa-spin"></i> 載入中...</td></tr>');
    
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { 
            action: 'get_server_items',
            server_id: stageData.server.id 
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            const items = response.data;
            
            if (items.length === 0) {
                tableBody.html('<tr><td colspan="3" class="text-center text-muted">目前沒有設定任何道具</td></tr>');
            } else {
                let html = '';
                items.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.item_code}</td>
                            <td>${item.item_name || ''}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-danger" onclick="removeServerItem(${item.id})" title="刪除道具">
                                    <i class="fa fa-trash"></i> 刪除
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tableBody.html(html);
            }
        } else {
            tableBody.html('<tr><td colspan="3" class="text-center text-danger">載入失敗：' + response.message + '</td></tr>');
        }
    })
    .fail(function(xhr, status, error) {
        tableBody.html('<tr><td colspan="3" class="text-center text-danger">載入失敗：連線錯誤</td></tr>');
        console.error('Load server items error:', error);
    });
}

// 查詢執行記錄
function queryExecutionLog() {
    if (!currentLogId) {
        showNotification('沒有可查詢的記錄', 'error');
        return;
    }
    
    // 顯示載入中
    const queryBtn = $('#queryLogBtn');
    const originalText = queryBtn.html();
    queryBtn.html('<i class="fa fa-spinner fa-spin"></i> 查詢中...').prop('disabled', true);
    
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { 
            action: 'get_gift_execution_log',
            log_id: currentLogId
        },
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        }
    })
    .done(function(response) {
        if (response.success) {
            displayExecutionLogModal(response.data);
        } else {
            showNotification('查詢執行記錄失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        showNotification('查詢執行記錄失敗：連線錯誤', 'error');
        console.error('Query execution log error:', error);
    })
    .always(function() {
        queryBtn.html(originalText).prop('disabled', false);
    });
}

// 顯示執行記錄Modal
function displayExecutionLogModal(data) {
    let modalContent = `
        <div class="modal fade" id="executionLogModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">派獎執行記錄 #${data.log.id}</h4>
                    </div>
                    <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                        <h5>基本資訊</h5>
                        <table class="table table-bordered">
                            <tr><td><strong>伺服器</strong></td><td>${data.log.server_full_name} (${data.log.server_name})</td></tr>
                            <tr><td><strong>遊戲帳號</strong></td><td>${data.log.game_account}</td></tr>
                            <tr><td><strong>道具總數</strong></td><td>${data.log.total_items}</td></tr>
                            <tr><td><strong>執行狀態</strong></td><td>${data.log.status}</td></tr>
                            <tr><td><strong>操作人員</strong></td><td>${data.log.operator_name}</td></tr>
                            <tr><td><strong>建立時間</strong></td><td>${data.log.created_at}</td></tr>
                        </table>
                        
                        <h5>資料庫連線資訊</h5>
                        <table class="table table-bordered">
                            <tr><td><strong>資料庫位址</strong></td><td>${data.server_info.db_ip || 'Not configured'}</td></tr>
                            <tr><td><strong>資料庫端口</strong></td><td>${data.server_info.db_port || 'Not configured'}</td></tr>
                            <tr><td><strong>資料庫名稱</strong></td><td>${data.server_info.db_name || 'Not configured'}</td></tr>
                            <tr><td><strong>資料庫用戶</strong></td><td>${data.server_info.db_user || 'Not configured'}</td></tr>
                        </table>
                        
                        <h5>派獎設定</h5>
                        <table class="table table-bordered">
                            <tr><td><strong>目標資料表</strong></td><td>${data.summary.target_table}</td></tr>
                            <tr><td><strong>帳號欄位</strong></td><td>${data.summary.account_field}</td></tr>
                        </table>
    `;
    
    if (data.dynamic_fields && data.dynamic_fields.length > 0) {
        modalContent += `
                        <h6>動態欄位</h6>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>欄位名稱</th><th>欄位值</th></tr></thead>
                            <tbody>
        `;
        data.dynamic_fields.forEach(field => {
            modalContent += `<tr><td>${field.field_name}</td><td>${field.field_value}</td></tr>`;
        });
        modalContent += `</tbody></table>`;
    }
    
    modalContent += `
                        <h5>執行SQL (共${data.summary.total_sqls}條)</h5>
    `;
    
    if (data.execution_sqls && data.execution_sqls.length > 0) {
        data.execution_sqls.forEach((sqlData, index) => {
            modalContent += `
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h6>SQL #${index + 1}: ${sqlData.description}</h6>
                            </div>
                            <div class="panel-body">
                                <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;">${sqlData.sql}</pre>
                            </div>
                        </div>
            `;
        });
    } else {
        modalContent += '<p class="text-warning">未設定派獎參數，無法生成執行SQL</p>';
    }
    
    modalContent += `
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">關閉</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 移除舊的modal
    $('#executionLogModal').remove();
    
    // 加入新的modal
    $('body').append(modalContent);
    
    // 顯示modal
    $('#executionLogModal').modal('show');
}

// 工具函數：顯示通知
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 
                      'alert-info';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade in" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    // 自動移除通知
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}

// 移除伺服器道具
function removeServerItem(itemId) {
    if (confirm('確定要移除此道具嗎？')) {
        $.ajax({
            url: 'api/gift_api.php',
            method: 'POST',
            data: {
                action: 'delete_server_item',
                item_id: itemId
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                showNotification('道具移除成功！', 'success');
                
                // 重新載入伺服器道具清單（用於選擇）
                if (stageData.server && stageData.server.id) {
                    loadServerItemsForSelect(stageData.server.id);
                }
                
                // 重新載入伺服器道具清單（用於顯示）
                loadServerItems();
            } else {
                showNotification('道具移除失敗：' + response.message, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            showNotification('道具移除失敗：連線錯誤', 'error');
            console.error('Remove server item error:', error);
        });
    }
}

// 測試遊戲伺服器連線
function testGameServerConnection(showModal = true) {
    const serverId = stageData.server ? stageData.server.id : null;
    
    if (!serverId) {
        showNotification('請先選擇伺服器', 'error');
        return Promise.reject('請先選擇伺服器');
    }
    
    // 顯示載入中
    const testBtn = $('#testConnectionBtn');
    const originalText = testBtn.html();
    if (showModal) {
        testBtn.html('<i class="fa fa-spinner fa-spin"></i> 測試中...').prop('disabled', true);
    }
    
    // 準備要送出的道具資料，用來檢查是否需要驗證道具名稱欄位
    const itemsToSend = stageData.items || [];
    const gameAccount = stageData.gameAccount || null;

    return $.ajax({
        url: 'api/gift_api.php',
        type: 'POST',
        data: {
            action: 'test_game_server_connection',
            server_id: serverId,
            items: itemsToSend,
            game_account: gameAccount,
            quick_test: !showModal // 背景測試時使用快速模式
        },
        dataType: 'json',
        timeout: 15000 // 15秒超時
    })
    .done(function(response) {
        if (response.success) {
            if (showModal) {
                displayConnectionTestModal(response.data);
            }
        } else {
            if (showModal) {
                showNotification('連線測試失敗：' + response.message, 'error');
            }
        }
    })
    .fail(function(xhr, status, error) {
        if (showModal) {
            if (status === 'timeout') {
                showNotification('連線測試失敗：連線超時', 'error');
            } else {
                showNotification('連線測試失敗：連線錯誤', 'error');
            }
        }
        console.error('Connection test error:', error);
    })
    .always(function() {
        if (showModal) {
            testBtn.html(originalText).prop('disabled', false);
        }
    });
}

// 顯示連線測試結果Modal
function displayConnectionTestModal(data) {
    const getStatusBadge = (success) => {
        return success ? '<span class="label label-success">成功</span>' : '<span class="label label-danger">失敗</span>';
    };
    
    let modalContent = `
        <div class="modal fade" id="connectionTestModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">遊戲伺服器連線測試結果</h4>
                    </div>
                    <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                        <h5>伺服器資訊</h5>
                        <table class="table table-bordered">
                            <tr><td><strong>伺服器ID</strong></td><td>${data.server_info.id}</td></tr>
                            <tr><td><strong>伺服器名稱</strong></td><td>${data.server_info.name}</td></tr>
                            <tr><td><strong>主機位址</strong></td><td>${data.server_info.host}</td></tr>
                            <tr><td><strong>埠號</strong></td><td>${data.server_info.port}</td></tr>
                            <tr><td><strong>資料庫名稱</strong></td><td>${data.server_info.database}</td></tr>
                            <tr><td><strong>使用者名稱</strong></td><td>${data.server_info.user}</td></tr>
                        </table>
                        
                        <h5>連線狀態</h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>資料庫連線</strong></td>
                                <td>${getStatusBadge(data.connection.success)}</td>
                            </tr>
                            ${data.connection.error ? `<tr><td colspan="2"><small class="text-danger">${data.connection.error}</small></td></tr>` : ''}
                        </table>
                        
                        <h5>派獎設定檢查</h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>設定完整性</strong></td>
                                <td>${getStatusBadge(data.settings.configured)}</td>
                            </tr>
                            <tr><td><strong>資料表名稱</strong></td><td>${data.settings.table_name || '<span class="text-muted">未設定</span>'}</td></tr>
                            <tr><td><strong>帳號欄位</strong></td><td>${data.settings.account_field || '<span class="text-muted">未設定</span>'}</td></tr>
                            <tr><td><strong>道具編號欄位</strong></td><td>${data.settings.item_field || '<span class="text-muted">未設定（預設：item_id）</span>'}</td></tr>
                            <tr><td><strong>道具名稱欄位</strong></td><td>${data.settings.item_name_field || '<span class="text-muted">未設定</span>'}</td></tr>
                            <tr><td><strong>數量欄位</strong></td><td>${data.settings.quantity_field || '<span class="text-muted">未設定（預設：quantity）</span>'}</td></tr>
                        </table>`;
                        
    if (data.connection.success && data.settings.configured) {
        modalContent += `
                        <h5>資料表與欄位檢查</h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>資料表存在</strong></td>
                                <td>${getStatusBadge(data.table_check.success)}</td>
                            </tr>
                            ${data.table_check.error ? `<tr><td colspan="2"><small class="text-danger">${data.table_check.error}</small></td></tr>` : ''}
                            <tr>
                                <td><strong>必要欄位存在</strong></td>
                                <td>${getStatusBadge(data.fields_check.success)}</td>
                            </tr>
                            ${data.fields_check.error ? `<tr><td colspan="2"><small class="text-danger">${data.fields_check.error}</small></td></tr>` : ''}
                        </table>`;
    }
    
    if (data.database_info && !data.database_info.error) {
        modalContent += `
                        <h5>資料庫版本資訊</h5>
                        <table class="table table-bordered">
                            <tr><td><strong>MySQL版本</strong></td><td>${data.database_info.version}</td></tr>
                            <tr><td><strong>字元編碼</strong></td><td>${data.database_info.charset}</td></tr>
                        </table>`;
    }

    // 顯示測試 SQL 語句
    if (data.test_sqls) {
        modalContent += `
                        <h5>測試用 SQL 語句 <small class="text-muted">（可複製以下語句到資料庫執行測試）</small></h5>`;

        if (data.test_sqls.error) {
            modalContent += `
                        <div class="alert alert-warning">
                            <strong>注意：</strong>${data.test_sqls.error}
                        </div>`;

            if (data.test_sqls.basic_example) {
                modalContent += `
                        <div class="panel panel-info">
                            <div class="panel-heading">基本 SQL 範例</div>
                            <div class="panel-body">
                                <p class="text-muted">${data.test_sqls.basic_example.description}</p>`;

                data.test_sqls.basic_example.example_sql.forEach(function(sql, index) {
                    modalContent += `
                                <div class="form-group">
                                    <label>範例 ${index + 1}:</label>
                                    <textarea class="form-control" rows="2" readonly style="font-family: monospace; font-size: 12px;">${sql}</textarea>
                                </div>`;
                });

                modalContent += `
                                <div class="alert alert-info" style="margin-top: 10px;">
                                    <strong>設定步驟：</strong>
                                    <ol style="margin: 5px 0 0 20px;">`;

                data.test_sqls.steps.forEach(function(step) {
                    modalContent += `<li>${step}</li>`;
                });

                modalContent += `
                                    </ol>
                                </div>
                            </div>
                        </div>`;
            }
        } else if (data.test_sqls.test_cases && data.test_sqls.test_cases.length > 0) {
            if (data.test_sqls.warning) {
                modalContent += `
                        <div class="alert alert-warning">
                            <strong>${data.test_sqls.warning.message}</strong>
                            <ul style="margin-top: 10px;">`;
                data.test_sqls.warning.suggestions.forEach(function(suggestion) {
                    modalContent += `<li>${suggestion}</li>`;
                });
                modalContent += `</ul>
                        </div>`;
            }

            // 顯示每個測試案例的 SQL
            data.test_sqls.test_cases.forEach(function(testCase, caseIndex) {
                const isActualData = testCase.is_actual_data === true;
                const panelClass = isActualData ? 'panel-primary' : 'panel-default';
                const badgeClass = isActualData ? 'label-primary' : 'label-default';
                const hasRealAccount = testCase.test_account && !testCase.test_account.includes('[') && !testCase.test_account.includes('請輸入');

                modalContent += `
                        <div class="panel ${panelClass}">
                            <div class="panel-heading">
                                <strong>${testCase.case_description}</strong>
                                ${isActualData ? '<span class="label label-primary">目前選擇的道具</span>' : '<span class="label label-default">範例</span>'}
                                ${!isActualData || !hasRealAccount ? `<small class="text-muted">（帳號: ${testCase.test_account}）</small>` : `<small class="text-success">（遊戲帳號: ${testCase.test_account}）</small>`}
                            </div>
                            <div class="panel-body">
                                ${isActualData && hasRealAccount ? '<p class="text-success"><i class="fa fa-check-circle"></i> 以下是根據您目前選擇的道具和遊戲帳號生成的 SQL 語句，可直接執行：</p>' : ''}
                                ${isActualData && !hasRealAccount ? '<p class="text-info"><i class="fa fa-info-circle"></i> 以下是根據您目前選擇的道具生成的 SQL 語句，請先輸入遊戲帳號再重新測試連線：</p>' : ''}`;

                testCase.sqls.forEach(function(sqlItem, sqlIndex) {
                    modalContent += `
                                <div class="form-group">
                                    <label>${sqlItem.description}</label>
                                    <textarea class="form-control" rows="3" readonly style="font-family: monospace; font-size: 11px; background-color: ${isActualData ? '#f0f8ff' : '#f8f8f8'};">${sqlItem.sql}</textarea>
                                </div>`;
                });

                modalContent += `
                            </div>
                        </div>`;
            });

            // 顯示驗證 SQL
            if (data.test_sqls.verification_sqls && data.test_sqls.verification_sqls.length > 0) {
                const hasActualData = data.test_sqls.test_cases && data.test_sqls.test_cases.some(tc => tc.is_actual_data === true);
                const hasRealAccount = data.test_sqls.test_cases && data.test_sqls.test_cases.some(tc => tc.is_actual_data === true && tc.test_account && !tc.test_account.includes('[') && !tc.test_account.includes('請輸入'));
                modalContent += `
                        <div class="panel panel-info">
                            <div class="panel-heading">${hasActualData ? '檢查道具派發結果 SQL' : '驗證與統計 SQL'}</div>
                            <div class="panel-body">
                                ${hasActualData && hasRealAccount ? '<p class="text-success">執行上述 INSERT 語句後，可使用以下 SQL 檢查派發結果：</p>' : ''}
                                ${hasActualData && !hasRealAccount ? '<p class="text-muted">執行 INSERT 語句後，可使用以下 SQL 檢查派發結果（請先輸入遊戲帳號）：</p>' : ''}`;

                let currentSqlGroup = '';
                data.test_sqls.verification_sqls.forEach(function(sql, index) {
                    const trimmedSql = sql.trim();
                    if (trimmedSql === '') {
                        return; // 跳過空行
                    }

                    if (trimmedSql.startsWith('--')) {
                        if (!trimmedSql.startsWith('-- DELETE')) {
                            currentSqlGroup = trimmedSql.replace('--', '').trim();
                            modalContent += `<h6 class="text-info">${currentSqlGroup}</h6>`;
                        } else {
                            modalContent += `
                                <div class="form-group">
                                    <label class="text-danger">${trimmedSql.replace('--', '').trim()}</label>
                                    <textarea class="form-control" rows="1" readonly style="font-family: monospace; font-size: 11px; background-color: #fff5f5;">${data.test_sqls.verification_sqls[index + 1] || ''}</textarea>
                                </div>`;
                        }
                    } else if (trimmedSql.length > 0 && !trimmedSql.startsWith('--')) {
                        modalContent += `
                                <div class="form-group">
                                    <textarea class="form-control" rows="2" readonly style="font-family: monospace; font-size: 11px; background-color: #f0f8ff;">${sql}</textarea>
                                </div>`;
                    }
                });

                modalContent += `
                            </div>
                        </div>`;
            }

            // 顯示 SQL 統計資訊
            if (data.test_sqls.summary) {
                const hasActualData = data.test_sqls.test_cases && data.test_sqls.test_cases.some(tc => tc.is_actual_data === true);
                modalContent += `
                        <div class="alert alert-info">
                            <strong>${hasActualData ? '道具 SQL 統計' : 'SQL 統計'}：</strong>
                            ${hasActualData ?
                                `您選擇的道具數量 ${data.test_sqls.summary.total_test_sqls} 個，生成 INSERT 語句 ${data.test_sqls.summary.total_test_sqls} 條` :
                                `測試案例 ${data.test_sqls.summary.total_test_cases} 個，INSERT 語句 ${data.test_sqls.summary.total_test_sqls} 條`
                            }${data.test_sqls.summary.verification_sqls_count > 0 ? `，檢查語句 ${data.test_sqls.summary.verification_sqls_count} 條` : ''}
                        </div>`;
            }
        }
    }

    modalContent += `
                        <div class="alert ${data.connection.success && (!data.settings.configured || (data.table_check.success && data.fields_check.success)) ? 'alert-success' : 'alert-warning'}" style="margin-top: 20px;">
                            <strong>總結：</strong>
                            ${data.connection.success ? 
                                (!data.settings.configured ? '資料庫連線正常，但派獎設定尚未完成。請到伺服器設定頁面完成派獎設定。' :
                                 (data.table_check.success && data.fields_check.success ? '所有檢查項目都通過！可以正常執行派獎。' :
                                  '資料庫連線正常，但資料表或欄位設定有問題，請檢查派獎設定。')) :
                                '無法連接到遊戲伺服器，請檢查伺服器設定或網路連線。'}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">關閉</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 移除舊的modal
    $('#connectionTestModal').remove();
    
    // 加入新的modal
    $('body').append(modalContent);
    
    // 顯示modal
    $('#connectionTestModal').modal('show');
}

// ===== 派獎記錄相關功能 =====

// 派獎記錄管理變數
let recordsData = {
    currentPage: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 0,
    filters: {
        server_id: '',
        game_account: '',
        status: ''
    }
};

// 初始化派獎記錄功能
$(document).ready(function() {
    initializeGiftRecords();
});

function initializeGiftRecords() {
    // 載入派獎記錄
    loadGiftRecords();

    // 載入伺服器選項到篩選器
    loadServersToFilter();

    // 綁定事件處理器
    bindGiftRecordsEvents();
}

function bindGiftRecordsEvents() {
    // 重新整理按鈕
    $('#refreshRecordsBtn').click(function() {
        loadGiftRecords();
    });

    // 搜尋表單
    $('#recordsSearchForm').submit(function(e) {
        e.preventDefault();

        // 更新篩選條件
        recordsData.filters.server_id = $('#filterServer').val();
        recordsData.filters.game_account = $('#filterAccount').val().trim();
        recordsData.filters.status = $('#filterStatus').val();

        // 重置頁碼並載入
        recordsData.currentPage = 1;
        loadGiftRecords();
    });

    // 清除篩選
    $('#clearFiltersBtn').click(function() {
        $('#recordsSearchForm')[0].reset();
        recordsData.filters = {
            server_id: '',
            game_account: '',
            status: ''
        };
        recordsData.currentPage = 1;
        loadGiftRecords();
    });
}

function loadServersToFilter() {
    const filterSelect = $('#filterServer');

    // 使用現有的伺服器列表
    if (typeof serverItems !== 'undefined') {
        // 如果已經載入伺服器列表，直接使用
        populateServerFilter();
    } else {
        // 從 API 載入伺服器資料
        $.ajax({
            url: 'api/gift_api.php',
            method: 'GET',
            data: { action: 'get_servers' },
            dataType: 'json'
        })
        .done(function(response) {
            if (response && response.success && response.data) {
                const servers = response.data;
                filterSelect.find('option:not(:first)').remove();

                servers.forEach(function(server) {
                    filterSelect.append(`<option value="${server.id}">${server.name}</option>`);
                });
            }
        })
        .fail(function() {
            console.error('Failed to load servers for filter');
        });
    }
}

function populateServerFilter() {
    const filterSelect = $('#filterServer');
    const serverSelect = $('#serverSelect');

    filterSelect.find('option:not(:first)').remove();
    serverSelect.find('option:not(:first)').each(function() {
        const value = $(this).val();
        const text = $(this).text();
        if (value) {
            filterSelect.append(`<option value="${value}">${text}</option>`);
        }
    });
}

function loadGiftRecords(page = null) {
    if (page) {
        recordsData.currentPage = page;
    }

    // 顯示載入狀態
    showRecordsLoading();

    // 準備 API 參數
    const params = {
        action: 'get_gift_logs',
        page: recordsData.currentPage,
        limit: recordsData.itemsPerPage
    };

    // 添加篩選條件
    if (recordsData.filters.server_id) {
        params.server_id = recordsData.filters.server_id;
    }
    if (recordsData.filters.game_account) {
        params.game_account = recordsData.filters.game_account;
    }
    if (recordsData.filters.status) {
        params.status = recordsData.filters.status;
    }

    // 發送 API 請求
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        }
    })
    .done(function(response) {
        if (response && response.success) {
            displayGiftRecords(response.data);
        } else {
            showRecordsError('載入派獎記錄失敗：' + (response.message || '未知錯誤'));
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Load gift records error:', error);
        showRecordsError('載入派獎記錄失敗：連線錯誤');
    });
}

function showRecordsLoading() {
    const tableBody = $('#recordsTableBody');
    tableBody.html(`
        <tr>
            <td colspan="8" class="text-center">
                <div class="loading-spinner">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>載入派獎記錄中...</p>
                </div>
            </td>
        </tr>
    `);

    // 隱藏統計資訊和分頁
    $('#recordsStats').hide();
    $('#recordsPagination').empty();
}

function showRecordsError(message) {
    const tableBody = $('#recordsTableBody');
    tableBody.html(`
        <tr>
            <td colspan="8" class="text-center error-message">
                <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                <p class="text-danger">${message}</p>
                <button type="button" class="btn btn-sm btn-default" onclick="loadGiftRecords()">
                    <i class="fa fa-refresh"></i> 重新載入
                </button>
            </td>
        </tr>
    `);
}

function displayGiftRecords(data) {
    const { logs, pagination } = data;
    const tableBody = $('#recordsTableBody');

    // 更新分頁資訊
    recordsData.totalItems = pagination.total_items;
    recordsData.totalPages = pagination.total_pages;

    // 顯示統計資訊
    updateRecordsStats(pagination);

    // 如果沒有記錄
    if (!logs || logs.length === 0) {
        tableBody.html(`
            <tr>
                <td colspan="8" class="text-center no-data">
                    <i class="fa fa-inbox fa-3x text-muted"></i>
                    <p class="text-muted">目前沒有派獎記錄</p>
                </td>
            </tr>
        `);
        return;
    }

    // 生成表格內容
    let html = '';
    logs.forEach(function(log) {
        html += generateRecordRow(log);
    });

    tableBody.html(html);

    // 更新分頁
    updateRecordsPagination();
}

function generateRecordRow(log) {
    // 處理道具資訊
    let itemsDisplay = '';
    if (log.items && Array.isArray(log.items)) {
        const itemNames = log.items.map(item => {
            let display = item.itemCode || '未知道具';
            if (item.itemName && item.itemName !== item.itemCode) {
                display = `${item.itemName} (${item.itemCode})`;
            }
            return `${display} x${item.quantity}`;
        });
        itemsDisplay = itemNames.join('<br>');
    } else {
        itemsDisplay = `共 ${log.total_items} 項道具`;
    }

    // 處理狀態
    const statusBadge = getStatusBadge(log.status);

    // 處理時間
    const createdTime = formatDateTime(log.created_at);

    // 處理操作者和IP
    const operatorInfo = log.operator_name || '未知';
    const operatorIp = log.operator_ip || '未記錄';

    return `
        <tr data-log-id="${log.id}">
            <td class="text-center">
                <strong>#${log.id}</strong>
            </td>
            <td>
                <span class="server-name" title="伺服器ID: ${log.server_id}">
                    ${log.server_name || '未知伺服器'}
                </span>
            </td>
            <td>
                <span class="game-account">${log.game_account}</span>
            </td>
            <td>
                <small class="items-info">${itemsDisplay}</small>
            </td>
            <td class="text-center">
                ${statusBadge}
            </td>
            <td class="text-center">
                <small>${operatorInfo}</small>
            </td>
            <td class="text-center">
                <code class="operator-ip">${operatorIp}</code>
            </td>
            <td class="text-center">
                <small>${createdTime}</small>
            </td>
        </tr>
    `;
}

function getStatusBadge(status) {
    switch (status) {
        case 'completed':
            return '<span class="label label-success">已完成</span>';
        case 'failed':
            return '<span class="label label-danger">失敗</span>';
        case 'pending':
            return '<span class="label label-warning">處理中</span>';
        default:
            return '<span class="label label-info">' + (status || '未知') + '</span>';
    }
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '未知時間';

    try {
        const date = new Date(dateTimeStr);
        return date.toLocaleDateString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    } catch (e) {
        return dateTimeStr;
    }
}

function updateRecordsStats(pagination) {
    const stats = $('#recordsStats');
    const { current_page, total_pages, total_items, items_per_page } = pagination;

    const start = (current_page - 1) * items_per_page + 1;
    const end = Math.min(current_page * items_per_page, total_items);

    const statsText = `共找到 ${total_items} 筆記錄，顯示第 ${start} - ${end} 筆 (第 ${current_page} 頁，共 ${total_pages} 頁)`;

    stats.text(statsText).show();
}

function updateRecordsPagination() {
    const pagination = $('#recordsPagination');

    if (recordsData.totalPages <= 1) {
        pagination.empty();
        return;
    }

    let html = '';

    // 上一頁
    const prevDisabled = recordsData.currentPage === 1 ? 'disabled' : '';
    html += `
        <li class="${prevDisabled}">
            <a href="#" onclick="loadGiftRecords(${recordsData.currentPage - 1})" ${prevDisabled ? 'aria-disabled="true"' : ''}>
                <i class="fa fa-chevron-left"></i> 上一頁
            </a>
        </li>
    `;

    // 頁碼
    const startPage = Math.max(1, recordsData.currentPage - 2);
    const endPage = Math.min(recordsData.totalPages, recordsData.currentPage + 2);

    if (startPage > 1) {
        html += `<li><a href="#" onclick="loadGiftRecords(1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="disabled"><a href="#">...</a></li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === recordsData.currentPage ? 'active' : '';
        html += `<li class="${activeClass}"><a href="#" onclick="loadGiftRecords(${i})">${i}</a></li>`;
    }

    if (endPage < recordsData.totalPages) {
        if (endPage < recordsData.totalPages - 1) {
            html += `<li class="disabled"><a href="#">...</a></li>`;
        }
        html += `<li><a href="#" onclick="loadGiftRecords(${recordsData.totalPages})">${recordsData.totalPages}</a></li>`;
    }

    // 下一頁
    const nextDisabled = recordsData.currentPage === recordsData.totalPages ? 'disabled' : '';
    html += `
        <li class="${nextDisabled}">
            <a href="#" onclick="loadGiftRecords(${recordsData.currentPage + 1})" ${nextDisabled ? 'aria-disabled="true"' : ''}>
                下一頁 <i class="fa fa-chevron-right"></i>
            </a>
        </li>
    `;

    pagination.html(html);
}

</script>

