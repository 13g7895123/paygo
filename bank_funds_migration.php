<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Funds Migration Tool</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .btn-execute {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-execute:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-test {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(245, 87, 108, 0.4);
        }

        .btn-preview {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(79, 172, 254, 0.4);
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
            border-top: 4px solid #667eea;
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
            color: #667eea;
            font-weight: 700;
        }

        .details {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 6px;
        }

        .server-detail {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }

        .server-detail h4 {
            margin-bottom: 10px;
            color: #667eea;
        }

        .record-item {
            padding: 8px 12px;
            margin: 5px 0;
            background: white;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.inserted,
        .status-badge.match {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.skipped,
        .status-badge.skip {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.missing,
        .status-badge.mismatch {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.insert {
            background: #cfe2ff;
            color: #084298;
        }

        .json-viewer {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }

        .json-viewer pre {
            margin: 0;
        }

        .check-detail {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 13px;
        }

        .check-detail .payment-type {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .check-detail .data-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .check-detail .data-label {
            color: #6c757d;
        }

        .check-detail .data-value {
            font-family: 'Courier New', monospace;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bank Funds Migration Tool</h1>
            <p>從 servers 表遷移支付設定資料到 bank_funds 表</p>
        </div>

        <div class="content">
            <div class="button-group">
                <button class="btn btn-preview" id="previewBtn">
                    預覽遷移內容
                </button>
                <button class="btn btn-execute" id="executeBtn">
                    執行資料遷移
                </button>
                <button class="btn btn-test" id="testBtn">
                    測試資料完整性
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
            const API_URL = '/myadm/api/bank_funds_migration_api.php';

            function showLoading(text) {
                $('#loading').addClass('active');
                $('#loadingText').text(text);
                $('#result').removeClass('active');
                $('#previewBtn, #executeBtn, #testBtn').prop('disabled', true);
            }

            function hideLoading() {
                $('#loading').removeClass('active');
                $('#previewBtn, #executeBtn, #testBtn').prop('disabled', false);
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

            function formatExecuteDetails(details) {
                let html = '<div class="details">';
                details.forEach(server => {
                    html += `
                        <div class="server-detail">
                            <h4>Server Code: ${server.server_code}</h4>
                    `;
                    server.records.forEach(record => {
                        html += `
                            <div class="record-item">
                                <span>
                                    <strong>${record.payment_type}</strong>
                                    ${record.merchant_id ? ` - ${record.merchant_id}` : ''}
                                </span>
                                <span class="status-badge ${record.status}">
                                    ${record.status}
                                    ${record.reason ? ` (${record.reason})` : ''}
                                </span>
                            </div>
                        `;
                    });
                    html += '</div>';
                });
                html += '</div>';
                return html;
            }

            function formatTestDetails(details) {
                let html = '<div class="details">';
                details.forEach(server => {
                    html += `
                        <div class="server-detail">
                            <h4>Server Code: ${server.server_code}</h4>
                    `;
                    server.checks.forEach(check => {
                        html += `
                            <div class="check-detail">
                                <div class="payment-type">
                                    ${check.payment_type}
                                    <span class="status-badge ${check.status}">${check.status}</span>
                                </div>
                        `;

                        if (check.status === 'missing') {
                            html += `<p style="color: #721c24; margin-top: 5px;">❌ 資料未找到</p>`;
                        } else if (check.status === 'mismatch') {
                            html += `<p style="color: #856404; margin-top: 5px;">⚠️ 資料不一致</p>`;
                            html += `<div style="margin-top: 8px;">`;
                            for (let key in check.expected) {
                                html += `
                                    <div class="data-row">
                                        <span class="data-label">${key}:</span>
                                        <span class="data-value">
                                            Expected: ${check.expected[key]}<br>
                                            Found: ${check.found[key]}
                                        </span>
                                    </div>
                                `;
                            }
                            html += `</div>`;
                        } else {
                            html += `<p style="color: #155724; margin-top: 5px;">✓ 資料一致</p>`;
                        }

                        html += '</div>';
                    });
                    html += '</div>';
                });
                html += '</div>';
                return html;
            }

            function formatPreviewDetails(details) {
                let html = '<div class="details">';
                details.forEach(server => {
                    html += `
                        <div class="server-detail">
                            <h4>Server Code: ${server.server_code} - ${server.server_name || 'N/A'}</h4>
                    `;
                    server.actions.forEach(action => {
                        const actionIcon = action.action === 'insert' ? '➕' : '⏭️';
                        const actionText = action.action === 'insert' ? '將新增' : '已存在(跳過)';

                        html += `
                            <div class="check-detail">
                                <div class="payment-type">
                                    ${action.payment_type}
                                    <span class="status-badge ${action.action}">${actionIcon} ${actionText}</span>
                                </div>
                                <div style="margin-top: 8px; font-size: 12px;">
                        `;

                        for (let key in action.data) {
                            if (key === 'server_code' || key === 'third_party_payment') continue;
                            const value = action.data[key];
                            if (value) {
                                html += `
                                    <div class="data-row">
                                        <span class="data-label">${key}:</span>
                                        <span class="data-value">${value}</span>
                                    </div>
                                `;
                            }
                        }

                        html += `
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
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
                                'Total Servers': summary.total_servers,
                                'Will Insert': summary.will_insert,
                                'Already Exists': summary.already_exists,
                                'Total Operations': summary.total_operations
                            });

                            if (response.data.details && response.data.details.length > 0) {
                                html += '<h4 style="margin: 15px 0 10px 0;">預覽詳細內容:</h4>';
                                html += formatPreviewDetails(response.data.details);
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
                if (!confirm('確定要執行資料遷移嗎？\n\n此操作會將 servers 表的支付設定遷移到 bank_funds 表。')) {
                    return;
                }

                showLoading('執行資料遷移中...');

                $.ajax({
                    url: API_URL,
                    method: 'POST',
                    data: { action: 'execute' },
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            let html = `<h3>✓ ${response.message}</h3>`;
                            html += formatSummary({
                                'Total Servers': response.data.total_servers,
                                'Records Inserted': response.data.records_inserted,
                                'Records Skipped': response.data.records_skipped
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

            $('#testBtn').click(function() {
                showLoading('測試資料完整性中...');

                $.ajax({
                    url: API_URL,
                    method: 'POST',
                    data: { action: 'test' },
                    dataType: 'json',
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            const summary = response.data.summary;
                            const isSuccess = summary.test_passed;

                            let html = `<h3>${isSuccess ? '✓' : '✗'} ${response.message}</h3>`;
                            html += formatSummary({
                                'Total Expected': summary.total_expected,
                                'Total Found': summary.total_found,
                                'Total Missing': summary.total_missing,
                                'Total Mismatch': summary.total_mismatch,
                                'Test Passed': summary.test_passed ? 'Yes' : 'No'
                            });

                            if (response.data.details && response.data.details.length > 0) {
                                html += '<h4 style="margin: 15px 0 10px 0;">詳細檢查結果:</h4>';
                                html += formatTestDetails(response.data.details);
                            }

                            showResult(isSuccess, html);
                        } else {
                            showResult(false, `<h3>✗ 測試失敗</h3><p>${response.message}</p>`);
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
                        showResult(false, `<h3>✗ 測試失敗</h3><p>${errorMsg}</p>`);
                    }
                });
            });
        });
    </script>
</body>
</html>
