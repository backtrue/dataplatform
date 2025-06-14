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
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">三成本五指標概覽</h2>
                <div id="overviewMetrics" class="grid grid-cols-3 gap-4"></div>
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

        function renderMetric(container, label, value, formatter = formatNumber, color = 'bg-white') {
            const div = document.createElement('div');
            div.className =
                `rounded-lg shadow-md p-3 flex flex-col items-center justify-center transition-transform duration-200 hover:scale-105 ${color}`;
            let displayValue;
            if (typeof value === 'string') {
                value = parseFloat(value);
            }
            if (typeof value !== 'number' || isNaN(value)) {
                displayValue = '0';
            } else {
                displayValue = formatter(value);
            }

            // 根據數字長度動態調整字體大小
            let fontSizeClass = 'text-2xl';
            if (displayValue.length > 7) {
                fontSizeClass = 'text-lg';
            } else if (displayValue.length > 5) {
                fontSizeClass = 'text-xl';
            }

            div.innerHTML = `
            <span class="text-gray-500 text-sm mb-1 tracking-wide">${label}</span>
            <span class="font-bold ${fontSizeClass} text-gray-800">${displayValue}</span>
        `;
            container.appendChild(div);
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
                    renderMetric(overview, '總收益', data.overview.total_revenue || 0, formatCurrency, 'bg-blue-50');
                    renderMetric(overview, '流量', data.overview.traffic || 0, formatNumber, 'bg-green-50');
                    renderMetric(overview, '轉換率', data.overview.conversion_rate || 0, formatPercentage, 'bg-yellow-50');
                    renderMetric(overview, '平均購買收益', data.overview.avg_order_value || 0, formatCurrency,
                    'bg-purple-50');
                    renderMetric(overview, '訂單獲取成本', data.overview.cost_per_acquisition || 0, formatCurrency,
                        'bg-pink-50');
                    renderMetric(overview, '名單獲取成本', data.overview.cost_per_lead || 0, formatCurrency, 'bg-indigo-50');
                    renderMetric(overview, '流量獲取成本', data.overview.cost_per_traffic || 0, formatCurrency,
                        'bg-orange-50');
                    renderMetric(overview, '總曝光', data.overview.impressions || 0, formatNumber, 'bg-gray-50');

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
