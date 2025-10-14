<?php
include("include.php");
check_login();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Funds Update Server Code</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 200px;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-preview {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(79, 172, 254, 0.4);
        }

        .btn-execute {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn-execute:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(56, 239, 125, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #11998e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .result {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .result.active {
            display: block;
        }

        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .result h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }

        .summary {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item .label {
            font-weight: 600;
        }

        .summary-item .value {
            color: #11998e;
            font-weight: 700;
        }

        .details {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 6px;
        }

        .record-detail {
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #11998e;
        }

        .record-detail.not-found {
            border-left-color: #dc3545;
        }

        .record-detail.updated {
            border-left-color: #28a745;
        }

        .record-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 8px;
            font-size: 13px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .info-label {
            color: #6c757d;
            font-weight: 600;
        }

        .info-value {
            font-family: 'Courier New', monospace;
            color: #212529;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }

        .status-badge.updated,
        .status-badge.update {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.not-found,
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }

        .arrow {
            color: #11998e;
            font-weight: bold;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bank Funds Update Server Code</h1>
            <p>更新 bank_funds 表的 server_code 欄位（從 servers.id 更新為 servers.auton）</p>
        </div>

        <div class="content">
            <div class="button-group">
                <button class="btn btn-preview" id="previewBtn">
                    預覽更新內容
                </button>
                <button class="btn btn-execute" id="executeBtn">
                    執行更新操作
                </button>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p id="loadingText">處理中...</p>
            </div>

            <div class="result" id="result"></div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const API_URL = '/myadm/api/bank_funds_update_server_code_api.php';

            function showLoading(text) {
                $('#loading').addClass('active');
                $('#loadingText').text(text);
                $('#result').removeClass('active');
                $('#previewBtn, #executeBtn').prop('disabled', true);
            }

            function hideLoading() {
                $('#loading').removeClass('active');
                $('#previewBtn, #executeBtn').prop('disabled', false);
            }

            function showResult(success, html) {
                const $result = $('#result');
                $result.removeClass('success error').addClass(success ? 'success' : 'error');
                $result.html(html);
                $result.addClass('active');
            }

            function formatSummary(data) {
                let html = '<div class="summary">';
                for (let key in data) {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const value = data[key];
                    html += `
                        <div class="summary-item">
                            <span class="label">${label}:</span>
                            <span class="value">${value}</span>
                        </div>
                    `;
                }
                html += '</div>';
                return html;
            }

            function formatDetails(details) {
                let html = '<div class="details">';
                details.forEach(record => {
                    const statusClass = record.action === 'not_found' ? 'not-found' : 'updated';
                    const statusBadge = record.action === 'not_found' ? 'not-found' : 'update';
                    const statusText = record.action === 'not_found' ? '❌ Server Not Found' : '✓ Will Update';

                    html += `
                        <div class="record-detail ${statusClass}">
                            <div>
                                <strong>Bank Fund ID: ${record.bank_fund_id}</strong>
                                <span class="status-badge ${statusBadge}">${statusText}</span>
                            </div>
                            <div class="record-info">
                                <div class="info-item">
                                    <span class="info-label">Payment Type:</span>
                                    <span class="info-value">${record.third_party_payment}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Merchant ID:</span>
                                    <span class="info-value">${record.merchant_id || 'N/A'}</span>
                                </div>
                    `;

                    if (record.action !== 'not_found') {
                        html += `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">Server Code:</span>
                                    <span class="info-value">
                                        ${record.current_server_code}
                                        <span class="arrow">→</span>
                                        ${record.new_server_code}
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Server Name:</span>
                                    <span class="info-value">${record.server_name || 'N/A'}</span>
                                </div>
                        `;
                    } else {
                        html += `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">Current Server Code:</span>
                                    <span class="info-value">${record.current_server_code}</span>
                                </div>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span style="color: #dc3545;">${record.warning}</span>
                                </div>
                        `;
                    }

                    html += `
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                return html;
            }

            function formatExecuteDetails(details) {
                let html = '<div class="details">';
                details.forEach(record => {
                    const statusClass = record.status === 'not_found' ? 'not-found' : 'updated';
                    const statusBadge = record.status === 'not_found' ? 'error' : 'updated';
                    const statusText = record.status === 'not_found' ? '❌ Error' : '✓ Updated';

                    html += `
                        <div class="record-detail ${statusClass}">
                            <div>
                                <strong>Bank Fund ID: ${record.bank_fund_id}</strong>
                                <span class="status-badge ${statusBadge}">${statusText}</span>
                            </div>
                            <div class="record-info">
                                <div class="info-item">
                                    <span class="info-label">Payment Type:</span>
                                    <span class="info-value">${record.third_party_payment}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Merchant ID:</span>
                                    <span class="info-value">${record.merchant_id || 'N/A'}</span>
                                </div>
                    `;

                    if (record.status !== 'not_found') {
                        html += `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">Server Code:</span>
                                    <span class="info-value">
                                        ${record.old_server_code}
                                        <span class="arrow">→</span>
                                        ${record.new_server_code}
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Server Name:</span>
                                    <span class="info-value">${record.server_name || 'N/A'}</span>
                                </div>
                        `;
                    } else {
                        html += `
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span class="info-label">Server Code:</span>
                                    <span class="info-value">${record.server_code}</span>
                                </div>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span style="color: #dc3545;">${record.error}</span>
                                </div>
                        `;
                    }

                    html += `
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                return html;
            }

            $('#previewBtn').click(function() {
                showLoading('載入預覽資料中...');

                $.ajax({
                    url: API_URL,
                    method: 'POST',
                    data: { action: 'preview' },
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            const summary = response.data.summary;
                            let html = `<h3>📋 ${response.message}</h3>`;
                            html += formatSummary({
                                'Total Records': summary.total_records,
                                'Will Update': summary.will_update,
                                'Not Found': summary.not_found,
                                'No Change Needed': summary.no_change_needed
                            });

                            if (response.data.details && response.data.details.length > 0) {
                                html += '<h4 style="margin: 15px 0 10px 0;">預覽詳細內容:</h4>';
                                html += formatDetails(response.data.details);
                            }

                            showResult(true, html);
                        } else {
                            showResult(false, `<h3>✗ 預覽失敗</h3><p>${response.message}</p>`);
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        let errorMsg = '未知錯誤';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch (e) {
                            errorMsg = xhr.responseText || errorMsg;
                        }
                        showResult(false, `<h3>✗ 預覽失敗</h3><p>${errorMsg}</p>`);
                    }
                });
            });

            $('#executeBtn').click(function() {
                if (!confirm('確定要執行更新操作嗎？\n\n此操作會將 bank_funds 表的 server_code 從 servers.id 更新為 servers.auton。')) {
                    return;
                }

                showLoading('執行更新操作中...');

                $.ajax({
                    url: API_URL,
                    method: 'POST',
                    data: { action: 'execute' },
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            const summary = response.data.summary;
                            let html = `<h3>✓ ${response.message}</h3>`;
                            html += formatSummary({
                                'Total Records': summary.total_records,
                                'Updated': summary.updated,
                                'Not Found': summary.not_found,
                                'No Change Needed': summary.no_change_needed
                            });

                            if (response.data.details && response.data.details.length > 0) {
                                html += '<h4 style="margin: 15px 0 10px 0;">詳細資訊:</h4>';
                                html += formatExecuteDetails(response.data.details);
                            }

                            showResult(true, html);
                        } else {
                            showResult(false, `<h3>✗ 執行失敗</h3><p>${response.message}</p>`);
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        let errorMsg = '未知錯誤';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch (e) {
                            errorMsg = xhr.responseText || errorMsg;
                        }
                        showResult(false, `<h3>✗ 執行失敗</h3><p>${errorMsg}</p>`);
                    }
                });
            });
        });
    </script>
</body>
</html>
