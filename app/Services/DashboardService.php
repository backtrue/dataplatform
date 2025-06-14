<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class DashboardService
{
    protected $googleApiService;

    public function __construct(GoogleApiService $googleApiService)
    {
        $this->googleApiService = $googleApiService;

        // 設置當前用戶的API設定
        if (Auth::check()) {
            $user = Auth::user();
            $this->googleApiService->setUserConfig([
                'google_analytics_view_id' => $user->google_analytics_view_id,
                'google_ads_customer_id' => $user->google_ads_customer_id,
                'supermetrics_user' => env('SUPERMETRICS_USER', ''),
                'supermetrics_api_key' => env('SUPERMETRICS_API_KEY', ''),
                'google_search_console_site_url' => $user->google_search_console_site_url,
            ]);
        }
    }

    public function getOverviewMetrics($startDate, $endDate)
    {
        $metrics = [
            'totalRevenue' => 'totalRevenue', // 修正為正確的 GA4 指標名稱
            'sessions' => 'sessions',
            'conversions' => 'conversions',
            'transactions' => 'transactions',
            'newUsers' => 'newUsers', // 改用 newUsers 替代 leads
            'screenPageViews' => 'screenPageViews' // 使用 screenPageViews 替代無效的 impressions
        ];

        try {
            $data = $this->googleApiService->getAnalyticsData(array_values($metrics), $startDate, $endDate);

            // $data[0]: totalRevenue, $data[1]: sessions, $data[2]: conversions, $data[3]: transactions, $data[4]: newUsers, $data[5]: screenPageViews
            
            // 獲取廣告花費數據
            $adsData = $this->googleApiService->getSupermetricsAdsData($startDate, $endDate);
            $fadSpend = $adsData['fad_spend'] ?? 0;
            $gadSpend = $adsData['gad_spend'] ?? 0;
            $totalAdSpend = $fadSpend + $gadSpend;
            
            // 獲取首次購買者數據
            $customerData = $this->getCustomerOrderRatioMetrics($startDate, $endDate);
            $firstTimeBuyers = $customerData['first_time_buyers'] ?? 0;
            
            return [
                'total_revenue' => $data[0] ?? 0,
                'traffic' => $data[1] ?? 0,
                'conversion_rate' => $this->calculateRate($data[2] ?? 0, $data[1] ?? 0) * 100,
                'avg_order_value' => $this->calculateRate($data[0] ?? 0, $data[3] ?? 0),
                'cost_per_acquisition' => $this->calculateRate($totalAdSpend, $data[3] ?? 0), // 訂單獲取成本 = (GAD花費 + FAD花費) / Purchases
                'cost_per_lead' => $this->calculateRate($totalAdSpend, $firstTimeBuyers), // 名單獲取成本 = (GAD花費 + FAD花費) / first_time_buyers
                'cost_per_traffic' => $this->calculateRate($totalAdSpend, $data[1] ?? 0), // 流量獲取成本 = (GAD花費 + FAD花費) / sessions
                'impressions' => $data[5] ?? 0 // 改用頁面瀏覽次數作為曝光指標
            ];
        } catch (\Exception $e) {
            \Log::error('getOverviewMetrics error: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'traffic' => 0,
                'conversion_rate' => 0,
                'avg_order_value' => 0,
                'cost_per_acquisition' => 0,
                'cost_per_lead' => 0,
                'cost_per_traffic' => 0,
                'impressions' => 0
            ];
        }
    }

    // 計算比率，避免除以零的錯誤
    private function calculateRate($numerator, $denominator)
    {
        return ($denominator > 0) ? ($numerator / $denominator) : 0;
    }

    public function getAdvertisingMetrics($startDate, $endDate)
    {
        try {
            // 從Supermetrics API獲取廣告數據
            $adsData = $this->googleApiService->getSupermetricsAdsData($startDate, $endDate);
            return [
                'fad_ad_spend' => $adsData['fad_spend'] ?? 0,
                'gad_ad_spend' => $adsData['gad_spend'] ?? 0,
                'fad_ad_revenue' => $adsData['fad_revenue'] ?? 0,
                'gad_ad_revenue' => $adsData['gad_revenue'] ?? 0,
                'fad_roas' => ($adsData['fad_spend'] ?? 0) > 0 ? ($adsData['fad_revenue'] ?? 0) / $adsData['fad_spend'] : 0,
                'gad_roas' => ($adsData['gad_spend'] ?? 0) > 0 ? ($adsData['gad_revenue'] ?? 0) / $adsData['gad_spend'] : 0,
                'fad_cost_per_transaction' => ($adsData['fad_transactions'] ?? 0) > 0 ? ($adsData['fad_spend'] ?? 0) / $adsData['fad_transactions'] : 0
            ];
        } catch (\Throwable $th) {
            \Log::error('getAdvertisingMetrics error: ' . $th->getMessage());
            return [
                'fad_ad_spend' => 0,
                'gad_ad_spend' => 0,
                'fad_ad_revenue' => 0,
                'gad_ad_revenue' => 0,
                'fad_roas' => 0,
                'gad_roas' => 0,
                'fad_cost_per_transaction' => 0
            ];
        }
    }

    public function getSearchConsoleMetrics($startDate, $endDate)
    {
        // 從Search Console API獲取搜尋數據
        $data = $this->googleApiService->getSearchConsoleData($startDate, $endDate);

        return $data;
    }

    public function getConversionMetrics($startDate, $endDate)
    {
        $metrics = [
            'newUsers',
            'bounceRate',
            'eventValue',
            'transactions',
            'purchaserRate' // 修正為正確的 GA4 指標名稱
        ];

        try {
            $data = $this->googleApiService->getAnalyticsData($metrics, $startDate, $endDate);

            return [
                'new_users' => $data[0] ?? 0,
                'bounce_rate' => $data[1] ?? 0,
                'traffic_value' => $data[2] ?? 0, // pageValue
                'returning_customer_orders' => $data[3] ?? 0,
                'returning_customer_rate' => $data[4] ?? 0
            ];
        } catch (\Exception $e) {
            \Log::error('getConversionMetrics error: ' . $e->getMessage());
            return [
                'new_users' => 0,
                'bounce_rate' => 0,
                'traffic_value' => 0,
                'returning_customer_orders' => 0,
                'returning_customer_rate' => 0
            ];
        }
    }

    public function getCustomerOrderRatioMetrics($startDate, $endDate)
    {
        // 使用 GA4 正確的指標名稱
        $metrics = [
            'transactions',            // 總訂單數
            'purchaseToViewRate',      // 購買轉換率
            'userEngagementDuration',  // 使用者參與時間，用於計算轉換率
            'conversions'              // 轉換次數
        ];

        try {
            $data = $this->googleApiService->getAnalyticsData($metrics, $startDate, $endDate);

            // 計算相關比率
            $totalOrders = $data[0] ?? 0;
            $purchaseRate = $data[1] ?? 0;

            // 根據購買轉換率估算首次購買者數量
            $estimatedFirstTimeBuyers = round($totalOrders * $purchaseRate);
            $returningCustomerOrders = $totalOrders - $estimatedFirstTimeBuyers;
            $returningCustomerRate = $totalOrders > 0 ? ($returningCustomerOrders / $totalOrders) * 100 : 0;

            // 計算新客立即轉換率，確保數組索引存在
            $engagementDuration = $data[2] ?? 0;
            $conversions = $data[3] ?? 0;
            $newCustomerConversionRate = $engagementDuration > 0 ? ($conversions / $engagementDuration) * 100 : 0;

            return [
                'returning_customer_orders' => $returningCustomerOrders,
                'returning_customer_rate' => $returningCustomerRate,
                'first_time_buyers' => $estimatedFirstTimeBuyers,
                'new_customer_instant_conversion_rate' => $newCustomerConversionRate
            ];
        } catch (\Exception $e) {
            \Log::error('getCustomerOrderRatioMetrics error: ' . $e->getMessage());
            return [
                'returning_customer_orders' => 0,
                'returning_customer_rate' => 0,
                'first_time_buyers' => 0,
                'new_customer_instant_conversion_rate' => 0
            ];
        }
    }

    public function getLandingPageMetrics($startDate, $endDate)
    {
        $metrics = [
            'sessions', // 工作階段數
            'bounceRate', // 跳出率
            'totalRevenue', // 收益
            'transactions' // 訂單數
        ];
        $dimensions = ['pagePath'];

        try {
            $data = $this->googleApiService->getLandingPageMetrics($metrics, $dimensions, $startDate, $endDate);

            // 檢查 $data 是否為陣列，如果是字串（可能是錯誤訊息）則轉換為陣列
            if (!is_array($data)) {
                \Log::error('getLandingPageMetrics returned non-array data: ' . json_encode($data));
                return [];
            }

            // 計算每個頁面的轉換率
            $data = array_map(function($page) {
                // 檢查頁面資料是否有效且有 sessions
                // 檢查 $page 是否為陣列且 sessions 是否存在和有效
                if (!is_array($page) || !isset($page['sessions']) || !is_numeric($page['sessions']) || $page['sessions'] <= 0) {
                    if (!is_array($page)) {
                        $page = [];
                    }
                    $page['conversionRate'] = 0;
                    return $page;
                }

                // 計算轉換率
                $page['conversionRate'] = isset($page['transactions']) 
                    ? ($page['transactions'] / $page['sessions']) * 100 
                    : 0;
                
                return $page;
            }, $data);
            return $data;
        } catch (\Exception $e) {
            \Log::error('getLandingPageMetrics error: ' . $e->getMessage());
            return [];
        }
    }

    // 已不再使用此方法，改為直接在 getOverviewMetrics 中計算
    protected function calculateCostPerAcquisition($transactions)
    {
        $adsData = $this->googleApiService->getSupermetricsAdsData(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        return $transactions > 0 ? ($adsData['spend'] ?? 0) / $transactions : 0;
    }

    // 取得廣告花費總和 (GAD + FAD)
    protected function getAdSpend($startDate, $endDate)
    {
        $adsData = $this->googleApiService->getSupermetricsAdsData($startDate, $endDate);
        return $adsData['spend'] ?? 0;
    }

    protected function calculateROAS($adsData)
    {
        return isset($adsData['spend']) && $adsData['spend'] > 0
            ? ($adsData['revenue'] ?? 0) / $adsData['spend']
            : 0;
    }

    protected function calculateCostPerTransaction($adsData)
    {
        return isset($adsData['transactions']) && $adsData['transactions'] > 0
            ? ($adsData['spend'] ?? 0) / $adsData['transactions']
            : 0;
    }

    // 取得每日流量及轉換率資料
    public function getDailyTrafficAndConversionRate($startDate, $endDate)
    {
        try {
            // 以日期為維度，取得每日 sessions 及 conversions
            $metrics = ['sessions', 'conversions'];
            $dimensions = ['date'];
            $data = $this->googleApiService->getAnalyticsDataWithDimensions($metrics, $dimensions, $startDate, $endDate);
            // 預期 $data 格式：[['date' => '2024-06-01', 'sessions' => 100, 'conversions' => 5], ...]
            $result = [];
            foreach ($data as $row) {

                if (!isset($row['date'])) {
                    continue;
                }

                $sessions = $row['metric_0'] ?? 0; //sessions
                $conversions = $row['metric_1'] ?? 0; //conversions
                $conversion_rate = $sessions > 0 ? bcdiv($conversions, $sessions, 2) : 0;
                $result[] = [
                    'date' => $row['date'],
                    'sessions' => $sessions,
                    'conversion_rate' => $conversion_rate
                ];
            }
            return $result;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
