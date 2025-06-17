<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>數位行銷指標追蹤系統</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">
    <!-- 全屏Loading畫面 -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-5 rounded-lg shadow-lg text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-500 border-solid mx-auto mb-4"></div>
            <p class="text-lg font-semibold text-gray-700">數據加載中，請稍候...</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- 用戶信息和登出按鈕 -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-700">歡迎，<span class="font-semibold">{{ Auth::user()->name }}</span></p>
                    <p class="text-gray-500 text-sm">{{ Auth::user()->email }}</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('user.profile') }}"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-200">個人資料</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition duration-200">
                            登出
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <h1 class="text-3xl font-bold mb-8">數位行銷指標追蹤系統</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- 日期選擇器 -->
            <div class="col-span-full bg-white p-4 rounded-lg shadow">
                <div class="flex space-x-4">
                    <input type="date" id="startDate" class="border rounded px-3 py-2">
                    <input type="date" id="endDate" class="border rounded px-3 py-2">
                    <button onclick="updateDashboard()"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">更新數據</button>
                </div>
            </div>

            <!-- 三成本五指標概覽 -->
            <div class="bg-white p-6 rounded-lg shadow col-span-full">
                <h2 class="text-xl font-semibold mb-6">三成本五指標概覽</h2>
                <div id="overviewMetrics" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4"></div>
            </div>

            <!-- 廣告效益 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">廣告效益</h2>
                <div id="advertisingMetrics" class="grid grid-cols-3 gap-4"></div>
            </div>

            <!-- 轉換行為 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">轉換行為</h2>
                <div id="conversionMetrics" class="grid grid-cols-3 gap-4"></div>
            </div>

            <!-- 新客老客訂單佔比 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">新客老客訂單佔比</h2>
                <div id="customerOrderRatioMetrics" class="grid grid-cols-3 gap-4"></div>
            </div>

            <!-- 自然搜尋關鍵字 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">自然搜尋關鍵字</h2>
                <div id="searchConsoleMetrics" class="space-y-4"></div>
            </div>

            <!-- 到達頁面銷售概況 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">到達頁面銷售概況</h2>
                <div id="landingPageMetrics" class="space-y-4"></div>
            </div>

            <!-- 每日流量及轉換率概況 -->
            <div class="bg-white p-6 rounded-lg shadow col-span-full">
                <h2 class="text-xl font-semibold mb-4">每日流量及轉換率概況</h2>
                <canvas id="dailyTrafficConversionChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <script>
        // 顯示Loading畫面
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // 隱藏Loading畫面
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function formatNumber(number, decimals = 2) {
            // 如果是字串則轉為數字
            if (typeof number === 'string') {
                number = parseFloat(number);
            }
            if (typeof number !== 'number' || isNaN(number)) return '0';

            // 對於大數字使用縮寫格式
            if (number >= 1000000) {
                return (number / 1000000).toFixed(1) + 'M';
            } else if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'K';
            }

            return new Intl.NumberFormat('zh-TW', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        }

        function formatCurrency(number) {
            // 如果是字串則轉為數字
            if (typeof number === 'string') {
                number = parseFloat(number);
            }
            if (typeof number !== 'number' || isNaN(number)) return '0';
            return new Intl.NumberFormat('zh-TW', {
                style: 'currency',
                currency: 'TWD'
            }).format(number);
        }

        function formatPercentage(number) {
            // 如果是字串則轉為數字
            if (typeof number === 'string') {
                number = parseFloat(number);
            }
            if (typeof number !== 'number' || isNaN(number)) return '0%';
            return formatNumber(number) + '%';
        }

        function renderMetric(container, label, value, formatter = formatNumber, textColor = 'text-gray-800', bgColor = 'bg-white') {
            const card = document.createElement('div');
            card.className = 'p-4 rounded-lg border border-gray-100 hover:shadow-md transition-shadow duration-200';
            
            const iconHtml = getMetricIcon(label, textColor, bgColor);
            
            let displayValue = '0';
            if (typeof value === 'string') {
                value = parseFloat(value);
            }
            if (typeof value === 'number' && !isNaN(value)) {
                displayValue = formatter ? formatter(value) : value;
            }
            
            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <span class="text-sm font-medium text-gray-500">${label}</span>
                    ${iconHtml}
                </div>
                <div class="text-2xl font-bold ${textColor}">${displayValue}</div>
                <div class="mt-1 text-xs text-gray-400">
                    較上期 <span class="text-green-500">-</span>
                </div>
            `;
            container.appendChild(card);
        }
        
        function getMetricIcon(metricName, textColor, bgColor) {
            const icons = {
                '總收益': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')} ">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.564-.648-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                    </div>`,
                '流量': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>`,
                '轉換率': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>`,
                '平均購買收益': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.564-.648-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                    </div>`,
                '訂單獲取成本': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.564-.648-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                    </div>`,
                '名單獲取成本': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>`,
                '流量獲取成本': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6.5 6.326a6.52 6.52 0 01-1.5.174 6.487 6.487 0 01-5.011-2.36l.49-.816a4.015 4.015 0 003.521-1.989 4 4 0 00-.322-4.123 4.004 4.004 0 00-3.847 2.677 4.02 4.02 0 00.922 4.16l-.84.566a6.52 6.52 0 01-1.04-4.091 6.5 6.5 0 011.5-4.18 6.5 6.5 0 019.9 7.13l-.84-.564z" clip-rule="evenodd" />
                        </svg>
                    </div>`,
                '總曝光': `
                    <div class="p-2 rounded-lg ${bgColor} ${textColor.replace('text-', 'text-opacity-20 ')}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                    </div>`
            };
            
            return icons[metricName] || '';
        }

        function updateDashboard() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            // 顯示Loading畫面
            showLoading();

            fetch(`/api/dashboard/metrics?start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    // 清空所有容器
                    document.getElementById('overviewMetrics').innerHTML = '';
                    document.getElementById('advertisingMetrics').innerHTML = '';
                    document.getElementById('searchConsoleMetrics').innerHTML = '';
                    document.getElementById('conversionMetrics').innerHTML = '';

                    // 渲染三成本五指標
                    const overview = document.getElementById('overviewMetrics');
                    overview.innerHTML = '';
                    
                    // 確保數據存在，避免 undefined 錯誤
                    const overviewData = data.overview || {};
                    // 使用解構賦值簡化代碼
                    const {
                        total_revenue = 0,
                        traffic = 0,
                        conversion_rate = 0,
                        avg_order_value = 0,
                        cost_per_acquisition = 0,
                        cost_per_lead = 0,
                        cost_per_traffic = 0,
                        impressions = 0
                    } = overviewData;
                    
                    // 渲染指標卡片
                    renderMetric(overview, '總收益', total_revenue, formatCurrency, 'text-blue-600', 'bg-blue-50');
                    renderMetric(overview, '流量', traffic, formatNumber, 'text-green-600', 'bg-green-50');
                    renderMetric(overview, '轉換率', conversion_rate, formatPercentage, 'text-yellow-600', 'bg-yellow-50');
                    renderMetric(overview, '平均購買收益', avg_order_value, formatCurrency, 'text-purple-600', 'bg-purple-50');
                    renderMetric(overview, '訂單獲取成本', cost_per_acquisition, formatCurrency, 'text-pink-600', 'bg-pink-50');
                    renderMetric(overview, '名單獲取成本', cost_per_lead, formatCurrency, 'text-indigo-600', 'bg-indigo-50');
                    renderMetric(overview, '流量獲取成本', cost_per_traffic, formatCurrency, 'text-orange-600', 'bg-orange-50');
                    renderMetric(overview, '總曝光', impressions, formatNumber, 'text-gray-600', 'bg-gray-50');

                    // 渲染廣告效益
                    const advertising = document.getElementById('advertisingMetrics');
                    advertising.innerHTML = '';
                    renderMetric(advertising, 'FAD花費', data.advertising.fad_ad_spend, formatCurrency, 'bg-blue-100');
                    renderMetric(advertising, 'GAD花費', data.advertising.gad_ad_spend, formatCurrency, 'bg-green-100');
                    renderMetric(advertising, 'FAD收益', data.advertising.fad_ad_revenue, formatCurrency,
                        'bg-purple-100');
                    renderMetric(advertising, 'GAD收益', data.advertising.gad_ad_revenue, formatCurrency, 'bg-pink-100');
                    renderMetric(advertising, 'FAD ROAS', data.advertising.fad_roas, formatNumber, 'bg-yellow-100');
                    renderMetric(advertising, 'GAD ROAS', data.advertising.gad_roas, formatNumber, 'bg-orange-100');
                    renderMetric(advertising, 'FAD單次購買成本', data.advertising.fad_cost_per_transaction, formatCurrency,
                        'bg-gray-100');

                    // 渲染轉換行為
                    const conversion = document.getElementById('conversionMetrics');
                    conversion.innerHTML = '';
                    renderMetric(conversion, '新使用者人數', data.conversion.new_users, formatNumber, 'bg-blue-50');
                    renderMetric(conversion, '跳出率', data.conversion.bounce_rate, formatPercentage, 'bg-yellow-50');
                    renderMetric(conversion, '流量價值', data.conversion.traffic_value, formatCurrency, 'bg-green-50');
                    renderMetric(conversion, '老客訂單數', data.conversion.returning_customer_orders, formatNumber,
                        'bg-purple-50');
                    renderMetric(conversion, '老客比例', data.conversion.returning_customer_rate, formatPercentage,
                        'bg-pink-50');

                    // 渲染新客老客訂單佔比
                    const customerOrderRatio = document.getElementById('customerOrderRatioMetrics');
                    customerOrderRatio.innerHTML = '';
                    if (data.customer_order_ratio) {
                        renderMetric(customerOrderRatio, '老客訂單數', data.customer_order_ratio.returning_customer_orders,
                            formatNumber, 'bg-blue-100');
                        renderMetric(customerOrderRatio, '老客訂單比例', data.customer_order_ratio.returning_customer_rate,
                            formatPercentage, 'bg-yellow-100');
                        renderMetric(customerOrderRatio, '初次購買者人數', data.customer_order_ratio.first_time_buyers,
                            formatNumber, 'bg-green-100');
                        renderMetric(customerOrderRatio, '新客立即轉換率', data.customer_order_ratio
                            .new_customer_instant_conversion_rate, formatPercentage, 'bg-pink-100');
                    }

                    // 渲染搜尋關鍵字數據
                    const searchConsole = document.getElementById('searchConsoleMetrics');
                    searchConsole.innerHTML = '';
                    const searchConsolePageSize = 5;
                    let searchConsolePage = 1;

                    function renderSearchConsolePage(page) {
                        searchConsole.innerHTML = '';
                        const start = (page - 1) * searchConsolePageSize;
                        const end = start + searchConsolePageSize;
                        const keywords = data.search_console.slice(start, end);
                        keywords.forEach(keyword => {
                            const div = document.createElement('div');
                            div.className =
                                'rounded-lg shadow px-4 py-3 mb-2 bg-white hover:bg-blue-50 transition';
                            div.innerHTML = `
                            <div class="font-medium text-blue-700">${keyword.query}</div>
                            <div class="grid grid-cols-2 gap-2 text-sm mt-1">
                                <div>曝光次數: <span class="font-semibold">${keyword.impressions}</span></div>
                                <div>點擊次數: <span class="font-semibold">${keyword.clicks}</span></div>
                                <div>CTR: <span class="font-semibold">${formatPercentage(keyword.ctr)}</span></div>
                                <div>平均排名: <span class="font-semibold">${formatNumber(keyword.position, 1)}</span></div>
                            </div>
                        `;
                            searchConsole.appendChild(div);
                        });
                        renderSearchConsolePagination();
                    }

                    function renderSearchConsolePagination() {
                        let total = data.search_console.length;
                        let totalPages = Math.ceil(total / searchConsolePageSize);
                        let pagination = document.getElementById('searchConsolePagination');
                        if (!pagination) {
                            pagination = document.createElement('div');
                            pagination.id = 'searchConsolePagination';
                            searchConsole.parentNode.appendChild(pagination);
                        }
                        pagination.innerHTML = '';

                        // 添加上一頁按鈕
                        const prevBtn = document.createElement('button');
                        prevBtn.className = 'mx-1 px-2 py-1 rounded border ' + (searchConsolePage === 1 ?
                            'bg-gray-300 text-gray-500' : 'bg-white text-blue-500');
                        prevBtn.innerText = '上一頁';
                        prevBtn.disabled = searchConsolePage === 1;
                        prevBtn.onclick = () => {
                            if (searchConsolePage > 1) {
                                searchConsolePage--;
                                renderSearchConsolePage(searchConsolePage);
                            }
                        };
                        pagination.appendChild(prevBtn);

                        // 計算要顯示的頁碼範圍，限制最多顯示7個頁碼
                        let startPage = Math.max(1, searchConsolePage - 3);
                        let endPage = Math.min(totalPages, startPage + 6);
                        if (endPage - startPage < 6) {
                            startPage = Math.max(1, endPage - 6);
                        }

                        // 顯示頁碼按鈕
                        for (let i = startPage; i <= endPage; i++) {
                            const btn = document.createElement('button');
                            btn.className = 'mx-1 px-2 py-1 rounded border ' + (i === searchConsolePage ?
                                'bg-blue-500 text-white' : 'bg-white text-blue-500');
                            btn.innerText = i;
                            btn.onclick = () => {
                                searchConsolePage = i;
                                renderSearchConsolePage(i);
                            };
                            pagination.appendChild(btn);
                        }

                        // 添加下一頁按鈕
                        const nextBtn = document.createElement('button');
                        nextBtn.className = 'mx-1 px-2 py-1 rounded border ' + (searchConsolePage === totalPages ?
                            'bg-gray-300 text-gray-500' : 'bg-white text-blue-500');
                        nextBtn.innerText = '下一頁';
                        nextBtn.disabled = searchConsolePage === totalPages;
                        nextBtn.onclick = () => {
                            if (searchConsolePage < totalPages) {
                                searchConsolePage++;
                                renderSearchConsolePage(searchConsolePage);
                            }
                        };
                        pagination.appendChild(nextBtn);
                    }
                    renderSearchConsolePage(searchConsolePage);

                    // 渲染到達頁面銷售概況
                    const landingPage = document.getElementById('landingPageMetrics');
                    landingPage.innerHTML = '';
                    const landingPagePageSize = 5;
                    let landingPagePage = 1;

                    function renderLandingPagePage(page) {
                        landingPage.innerHTML = '';
                        if (!Array.isArray(data.landing_page)) return;
                        const start = (page - 1) * landingPagePageSize;
                        const end = start + landingPagePageSize;
                        const pages = data.landing_page.slice(start, end);
                        pages.forEach(item => {
                            const div = document.createElement('div');
                            div.className =
                                'rounded-lg shadow px-4 py-3 mb-2 bg-white hover:bg-green-50 transition';
                            div.innerHTML = `
                            <div class="font-medium text-green-700">${item.page}</div>
                            <div class="grid grid-cols-2 gap-2 text-sm mt-1">
                                <div>工作階段: <span class="font-semibold">${formatNumber(item.sessions)}</span></div>
                                <div>轉換率: <span class="font-semibold">${formatPercentage(item.conversion_rate)}</span></div>
                                <div>收益: <span class="font-semibold">${formatCurrency(item.totalRevenue)}</span></div>
                                <div>訂單數: <span class="font-semibold">${formatNumber(item.transactions)}</span></div>
                            </div>
                        `;
                            landingPage.appendChild(div);
                        });
                        renderLandingPagePagination();
                    }

                    function renderLandingPagePagination() {
                        if (!Array.isArray(data.landing_page)) return;
                        let total = data.landing_page.length;
                        let totalPages = Math.ceil(total / landingPagePageSize);
                        let pagination = document.getElementById('landingPagePagination');
                        if (!pagination) {
                            pagination = document.createElement('div');
                            pagination.id = 'landingPagePagination';
                            landingPage.parentNode.appendChild(pagination);
                        }
                        pagination.innerHTML = '';

                        // 添加上一頁按鈕
                        const prevBtn = document.createElement('button');
                        prevBtn.className = 'mx-1 px-2 py-1 rounded border ' + (landingPagePage === 1 ?
                            'bg-gray-300 text-gray-500' : 'bg-white text-green-500');
                        prevBtn.innerText = '上一頁';
                        prevBtn.disabled = landingPagePage === 1;
                        prevBtn.onclick = () => {
                            if (landingPagePage > 1) {
                                landingPagePage--;
                                renderLandingPagePage(landingPagePage);
                            }
                        };
                        pagination.appendChild(prevBtn);

                        // 計算要顯示的頁碼範圍
                        let startPage = Math.max(1, landingPagePage - 3);
                        let endPage = Math.min(totalPages, startPage + 6);
                        if (endPage - startPage < 6) {
                            startPage = Math.max(1, endPage - 6);
                        }

                        // 顯示頁碼按鈕
                        for (let i = startPage; i <= endPage; i++) {
                            const btn = document.createElement('button');
                            btn.className = 'mx-1 px-2 py-1 rounded border ' + (i === landingPagePage ?
                                'bg-green-500 text-white' : 'bg-white text-green-500');
                            btn.innerText = i;
                            btn.onclick = () => {
                                landingPagePage = i;
                                renderLandingPagePage(i);
                            };
                            pagination.appendChild(btn);
                        }

                        // 添加下一頁按鈕
                        const nextBtn = document.createElement('button');
                        nextBtn.className = 'mx-1 px-2 py-1 rounded border ' + (landingPagePage === totalPages ?
                            'bg-gray-300 text-gray-500' : 'bg-white text-green-500');
                        nextBtn.innerText = '下一頁';
                        nextBtn.disabled = landingPagePage === totalPages;
                        nextBtn.onclick = () => {
                            if (landingPagePage < totalPages) {
                                landingPagePage++;
                                renderLandingPagePage(landingPagePage);
                            }
                        };
                        pagination.appendChild(nextBtn);
                    }
                    renderLandingPagePage(landingPagePage);


                });

            // 渲染每日流量及轉換率概況
            fetch(`/api/dashboard/daily-traffic-conversion?start_date=${startDate}&end_date=${endDate}`)
                .then(res => res.json())
                .then(dailyData => {
                    renderDailyTrafficConversionChart(dailyData);

                    // 隱藏Loading畫面
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    // 發生錯誤時也要隱藏Loading畫面
                    hideLoading();
                });
        }

        let dailyTrafficConversionChartInstance = null;

        function renderDailyTrafficConversionChart(data) {
            const ctx = document.getElementById('dailyTrafficConversionChart').getContext('2d');
            const labels = data.map(item => item.date);
            const sessions = data.map(item => item.sessions);
            const conversionRates = data.map(item => item.conversion_rate);
            if (dailyTrafficConversionChartInstance) {
                dailyTrafficConversionChartInstance.destroy();
            }
            dailyTrafficConversionChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            type: 'bar',
                            label: '工作階段',
                            data: sessions,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: '轉換率(%)',
                            data: conversionRates,
                            borderColor: 'rgba(245, 158, 11, 1)',
                            backgroundColor: 'rgba(245, 158, 11, 0.2)',
                            fill: false,
                            yAxisID: 'y1',
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgba(245, 158, 11, 1)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    stacked: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: '工作階段'
                            },
                            beginAtZero: true
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '轉換率(%)'
                            },
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        // 設置預設日期範圍並初始化儀表板
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);

            document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];

            updateDashboard();
        });
    </script>

    <style>
        #searchConsolePagination,
        #landingPagePagination {
            margin-top: 10px;
        }
    </style>
</body>

</html>
