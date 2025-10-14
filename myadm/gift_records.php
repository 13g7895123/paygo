<?include("include.php");

check_login_share();

top_html();
?>

<section id="middle">
    <div class="container-fluid">
        <!-- 派獎記錄區域 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-history"></i> 派獎記錄
                        </h3>
                    </div>
                    <div class="panel-body">
                        <!-- 搜尋篩選區域 -->
                        <div class="search-filters">
                            <form class="form-inline" id="recordsSearchForm">
                                <div class="form-group">
                                    <label for="filterServer">伺服器：</label>
                                    <select class="form-control" id="filterServer" name="server_id">
                                        <option value="">全部伺服器</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filterAccount">遊戲帳號：</label>
                                    <input type="text" class="form-control" id="filterAccount" name="game_account" placeholder="輸入遊戲帳號">
                                </div>
                                <div class="form-group">
                                    <label for="filterStatus">狀態：</label>
                                    <select class="form-control" id="filterStatus" name="status">
                                        <option value="">全部狀態</option>
                                        <option value="completed">已完成</option>
                                        <option value="failed">失敗</option>
                                        <option value="pending">處理中</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> 搜尋
                                </button>
                                <button type="button" class="btn btn-default" id="clearFiltersBtn">
                                    <i class="fa fa-times"></i> 清除
                                </button>
                                <button type="button" class="btn btn-default" id="refreshRecordsBtn" title="重新整理">
                                    <i class="fa fa-refresh"></i> 重新整理
                                </button>
                            </form>
                        </div>

                        <!-- 資料統計 -->
                        <div id="recordsStats" class="search-stats" style="display: none;">
                            載入中...
                        </div>

                        <!-- 記錄表格 -->
                        <div class="table-responsive">
                            <table class="table table-hover records-table" id="recordsTable">
                                <thead>
                                    <tr>
                                        <th width="8%">記錄ID</th>
                                        <th width="12%">伺服器</th>
                                        <th width="15%">遊戲帳號</th>
                                        <th width="20%">道具資訊</th>
                                        <th width="8%">狀態</th>
                                        <th width="12%">派發者</th>
                                        <th width="12%">操作IP</th>
                                        <th width="13%">派發時間</th>
                                    </tr>
                                </thead>
                                <tbody id="recordsTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="loading-spinner">
                                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                <p>載入派獎記錄中...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分頁 -->
                        <nav aria-label="派獎記錄分頁">
                            <ul class="pagination" id="recordsPagination">
                                <!-- 分頁將通過 JavaScript 動態產生 -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /MIDDLE -->

<style>
/* 搜尋篩選區域樣式 */
.search-filters {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.search-filters .form-group {
    margin-right: 15px;
    margin-bottom: 10px;
}

.search-filters label {
    font-weight: 600;
    margin-right: 5px;
    margin-bottom: 0;
    line-height: 34px;
}

.search-filters .form-control {
    margin-right: 10px;
}

.search-filters .btn {
    margin-right: 10px;
}

/* 記錄表格樣式 */
.records-table {
    margin-bottom: 20px;
    font-size: 14px;
}

.records-table th {
    background-color: #667eea;
    color: white;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    border: none;
    padding: 12px 8px;
}

.records-table td {
    vertical-align: middle;
    padding: 10px 8px;
    border-top: 1px solid #dee2e6;
}

.records-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* 狀態樣式 */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    min-width: 60px;
}

.status-success {
    background-color: #d4edda;
    color: #155724;
}

.status-failed {
    background-color: #f8d7da;
    color: #721c24;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

/* 道具資訊樣式 */
.item-info {
    font-size: 12px;
}

.item-info .item-row {
    margin-bottom: 2px;
}

.item-info .item-code {
    font-family: monospace;
    background-color: #f1f3f4;
    padding: 1px 4px;
    border-radius: 3px;
}

/* 載入中動畫 */
.loading-spinner {
    padding: 40px 20px;
}

.loading-spinner i {
    color: #667eea;
    margin-bottom: 10px;
}

.loading-spinner p {
    color: #6c757d;
    font-size: 16px;
    margin: 0;
}

/* 空資料樣式 */
.no-data {
    padding: 60px 20px;
}

.no-data i {
    margin-bottom: 20px;
}

.no-data p {
    font-size: 16px;
    margin: 0;
}

/* 錯誤樣式 */
.error-message {
    padding: 40px 20px;
    text-align: center;
}

.error-message i {
    color: #dc3545;
    font-size: 48px;
    margin-bottom: 20px;
}

.error-message h4 {
    color: #dc3545;
    margin-bottom: 10px;
}

.error-message p {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

/* 按鈕樣式 */
.btn {
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #667eea;
    border-color: #667eea;
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

/* 搜尋結果統計 */
.search-stats {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 15px;
    font-size: 16px;
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

/* 分頁樣式 */
.pagination {
    justify-content: center;
    margin-top: 20px;
}

.pagination li a {
    color: #667eea;
    border-color: #dee2e6;
}

.pagination li.active a {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
}

.pagination li a:hover {
    background-color: #f8f9fa;
    border-color: #667eea;
    color: #5a6fd8;
}

.pagination li.disabled a {
    color: #6c757d;
    cursor: not-allowed;
}
</style>

<?
down_html();
?>

<script>
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
    // 搜尋表單提交
    $('#recordsSearchForm').on('submit', function(e) {
        e.preventDefault();

        // 更新篩選條件
        recordsData.filters.server_id = $('#filterServer').val();
        recordsData.filters.game_account = $('#filterAccount').val().trim();
        recordsData.filters.status = $('#filterStatus').val();

        // 重置到第一頁
        recordsData.currentPage = 1;

        // 重新載入記錄
        loadGiftRecords();
    });

    // 清除篩選按鈕
    $('#clearFiltersBtn').on('click', function() {
        $('#recordsSearchForm')[0].reset();
        recordsData.filters = {
            server_id: '',
            game_account: '',
            status: ''
        };
        recordsData.currentPage = 1;
        loadGiftRecords();
    });

    // 重新整理按鈕
    $('#refreshRecordsBtn').on('click', function() {
        loadGiftRecords();
    });
}

// 載入伺服器到篩選器
function loadServersToFilter() {
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'get_servers'
        }
    })
    .done(function(response) {
        if (response && response.success) {
            const serverSelect = $('#filterServer');

            response.data.forEach(function(server) {
                serverSelect.append(`<option value="${server.id}">${server.name}</option>`);
            });
        }
    })
    .fail(function(xhr, status, error) {
        console.error('載入伺服器清單失敗:', error);
    });
}

// 載入派獎記錄
function loadGiftRecords(page = null) {
    if (page !== null) {
        recordsData.currentPage = page;
    }

    showRecordsLoading();

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

    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        dataType: 'json',
        data: params
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
                <i class="fa fa-exclamation-triangle"></i>
                <h4>載入失敗</h4>
                <p>${message}</p>
            </td>
        </tr>
    `);

    $('#recordsStats').hide();
    $('#recordsPagination').empty();
}

function displayGiftRecords(data) {
    const { logs, pagination } = data;
    const tableBody = $('#recordsTableBody');

    // 更新分頁資訊
    recordsData.currentPage = pagination.current_page;
    recordsData.totalPages = pagination.total_pages;
    recordsData.totalItems = pagination.total_items;
    recordsData.itemsPerPage = pagination.items_per_page;

    // 檢查是否有資料
    if (!logs || logs.length === 0) {
        tableBody.html(`
            <tr>
                <td colspan="8" class="text-center no-data">
                    <i class="fa fa-inbox fa-3x text-muted"></i>
                    <p class="text-muted">目前沒有派獎記錄</p>
                </td>
            </tr>
        `);
        updateRecordsStats(pagination);
        $('#recordsPagination').empty();
        return;
    }

    // 生成表格內容
    let html = '';
    logs.forEach(function(log) {
        html += generateRecordRow(log);
    });

    tableBody.html(html);
    updateRecordsStats(pagination);
    updateRecordsPagination();
}

function generateRecordRow(log) {
    // 格式化狀態
    const statusMap = {
        'success': { class: 'status-success', text: '已完成' },
        'failed': { class: 'status-failed', text: '失敗' },
        'pending': { class: 'status-pending', text: '處理中' }
    };

    const statusInfo = statusMap[log.status] || { class: 'status-pending', text: log.status };

    // 格式化道具資訊
    let itemsHtml = '';
    if (log.items && Array.isArray(log.items)) {
        const maxItems = 3; // 最多顯示3個道具
        const displayItems = log.items.slice(0, maxItems);

        displayItems.forEach(function(item) {
            itemsHtml += `
                <div class="item-row">
                    <span class="item-code">${item.itemCode}</span>
                    ${item.itemName ? ` ${item.itemName}` : ''}
                    x${item.quantity}
                </div>
            `;
        });

        if (log.items.length > maxItems) {
            itemsHtml += `<div class="item-row text-muted">...還有 ${log.items.length - maxItems} 個道具</div>`;
        }
    }

    // 格式化時間
    const createTime = new Date(log.created_at).toLocaleString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });

    return `
        <tr>
            <td class="text-center">${log.id}</td>
            <td class="text-center">${log.server_name || '-'}</td>
            <td class="text-center">${log.game_account || '-'}</td>
            <td>
                <div class="item-info">
                    ${itemsHtml}
                </div>
            </td>
            <td class="text-center">
                <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
            </td>
            <td class="text-center">${log.operator_name || '-'}</td>
            <td class="text-center">${log.operator_ip || '-'}</td>
            <td class="text-center">${createTime}</td>
        </tr>
    `;
}

function updateRecordsStats(pagination) {
    const statsDiv = $('#recordsStats');

    if (pagination.total_items === 0) {
        statsDiv.hide();
        return;
    }

    const startItem = (pagination.current_page - 1) * pagination.items_per_page + 1;
    const endItem = Math.min(pagination.current_page * pagination.items_per_page, pagination.total_items);

    const statsText = `顯示第 ${startItem} - ${endItem} 筆，共 ${pagination.total_items} 筆記錄`;

    statsDiv.text(statsText).show();
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
    const maxVisiblePages = 5;
    let startPage = Math.max(1, recordsData.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(recordsData.totalPages, startPage + maxVisiblePages - 1);

    // 調整起始頁
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // 第一頁（如果需要）
    if (startPage > 1) {
        html += `<li><a href="#" onclick="loadGiftRecords(1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="disabled"><a href="#">...</a></li>`;
        }
    }

    // 頁碼範圍
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === recordsData.currentPage ? 'active' : '';
        html += `<li class="${activeClass}"><a href="#" onclick="loadGiftRecords(${i})">${i}</a></li>`;
    }

    // 最後一頁（如果需要）
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