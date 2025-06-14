<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\ApiCore\ApiException;
use Google\Client;

//cache 
use Illuminate\Support\Facades\Cache;

class GoogleApiService
{
    protected $analyticsClient;
    protected $adsClient;
    protected $searchConsoleClient;
    protected $adsCustomerId; // 存儲轉換後的 customer_id
    protected $userConfig = [];
    
    public function __construct()
    {
        // 初始化 customer_id，移除破折號並轉換為整數
        $this->adsCustomerId = (int) preg_replace('/[^0-9]/', '', config('google-api.ads.customer_id'));
        
        $this->initAnalyticsClient();
        $this->initSearchConsoleClient();
    }

    public function setUserConfig($config)
    {
        $this->userConfig = $config;
        
        // 更新 customer_id
        if (!empty($config['google_ads_customer_id'])) {
            $this->adsCustomerId = (int) preg_replace('/[^0-9]/', '', $config['google_ads_customer_id']);
        } else {
            $this->adsCustomerId = (int) preg_replace('/[^0-9]/', '', config('google-api.ads.customer_id'));
        }
        
        // 重新初始化客戶端
        $this->initAnalyticsClient();
        $this->initSearchConsoleClient();
    }
    
    protected function initAnalyticsClient()
    {
        $credentials = config('google-api.analytics.credentials_path');
        if (file_exists($credentials)) {
            $this->analyticsClient = new BetaAnalyticsDataClient([
                'credentials' => $credentials
            ]);
        }
    }
    
    
    protected function initSearchConsoleClient()
    {
        $client = new Client();
        $credentials = config('google-api.search_console.credentials_path');
        if (file_exists($credentials)) {
            $client->setAuthConfig($credentials);
            $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
            $this->searchConsoleClient = $client;
        }
    }
    
    public function getAnalyticsData($metrics, $startDate, $endDate)
    {
        if (!$this->analyticsClient) {
            return ['error' => 'Google Analytics client is not initialized.'];
        }

        try {
            // 使用用戶設定的 view_id 或默認值
            $viewId = !empty($this->userConfig['google_analytics_view_id']) 
                ? $this->userConfig['google_analytics_view_id'] 
                : config('google-api.analytics.view_id');
                
            $response = $this->analyticsClient->runReport([
                'property' => 'properties/' . $viewId,
                'dateRanges' => [new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])],
                'metrics' => array_map(function($metric) {
                    return new Metric(['name' => $metric]);
                }, $metrics)
            ]);
            
            return $this->formatAnalyticsResponse($response);
        } catch (ApiException $e) {
            \Log::error('Google Analytics API Exception: ' . $e->getMessage());
            return [];
        }
    }
    
    protected function formatAnalyticsResponse($response)
    {
        $result = [];
        foreach ($response->getRows() as $row) {
            $metrics = $row->getMetricValues();
            foreach ($metrics as $index => $metric) {
                $result[$index] = $metric->getValue();
            }
        }
        return $result;
    }

    
    /**
     * 使用Supermetrics API獲取廣告數據
     * 
     * @param string $startDate 開始日期 (YYYY-MM-DD)
     * @param string $endDate 結束日期 (YYYY-MM-DD)
     * @param int|null $campaignId 可選的廣告活動ID
     * @return array 廣告數據或錯誤信息
     */
    public function getSupermetricsAdsData($startDate, $endDate, $campaignId = null)
    {
        // Supermetrics API URL
        $baseUrl = 'https://api.supermetrics.com/enterprise/v2/query/data/json';
        $adsCustomerId = $this->adsCustomerId; // 獲取轉換後的 customer_id

        // 檢查快取是否存在且在有效期內
        $cacheKey = "supermetrics_data_{$adsCustomerId}_{$startDate}_{$endDate}_" . ($campaignId ?? 'all');
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        // 獲取Facebook廣告數據
        $fadData = $this->getFacebookAdsData($baseUrl, $startDate, $endDate, $campaignId);
        
        // 獲取Google廣告數據
        $gadData = $this->getGoogleAdsData($baseUrl, $startDate, $endDate, $campaignId);

        // 組合結果數據
        $result = [
            'fad_spend' => $fadData['spend'] ?? 0,
            'fad_revenue' => $fadData['revenue'] ?? 0,
            'fad_transactions' => $fadData['transactions'] ?? 0,
            'gad_spend' => $gadData['spend'] ?? 0,
            'gad_revenue' => $gadData['revenue'] ?? 0,
            'gad_transactions' => $gadData['transactions'] ?? 0
        ];

        // 儲存到快取，有效期1小時
        Cache::put($cacheKey, $result, now()->addHour());
        return [
            'fad_spend' => $fadData['spend'] ?? 0,
            'fad_revenue' => $fadData['revenue'] ?? 0,
            'fad_transactions' => $fadData['transactions'] ?? 0,
            'gad_spend' => $gadData['spend'] ?? 0,
            'gad_revenue' => $gadData['revenue'] ?? 0,
            'gad_transactions' => $gadData['transactions'] ?? 0
        ];
    }
    
    /**
     * 獲取Facebook廣告數據
     * 
     * @param string $baseUrl Supermetrics API基礎URL
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @param int|null $campaignId 可選的廣告活動ID
     * @return array 包含spend, revenue, transactions的數組
     */
    protected function getFacebookAdsData($baseUrl, $startDate, $endDate, $campaignId = null)
    {
        // 構建Facebook廣告查詢參數
        $params = [
            'ds_id' => 'FA', // Facebook Ads數據源
            'ds_accounts' => !empty($this->userConfig['facebook_ads_account']) ? $this->userConfig['facebook_ads_account'] : 'act_572673447777761', // 從用戶提供的URL獲取
            'ds_user' => !empty($this->userConfig['facebook_ads_user']) ? $this->userConfig['facebook_ads_user'] : '10204420100136695', // 從用戶提供的URL獲取
            'date_range_type' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fields' => 'spend,impressions,actions,action_values', // Facebook廣告指標
            'max_rows' => 1000,
            'api_key' => !empty($this->userConfig['supermetrics_api_key']) ? $this->userConfig['supermetrics_api_key'] : 'api_SxaKf6JKjgvfLnvMSoJGPe0MS0Af5FSrOlWAYPDgkS0Iw3dS8eWtkGKFWNlUIsFf_i0raMzrcAEcc76zlBZr8RUVpuCHtRIW7Zgh' // 從用戶提供的URL獲取
        ];
        
        // 如果提供了廣告活動ID，添加到查詢中
        if ($campaignId) {
            $params['filter'] = 'campaign_id==' . $campaignId;
        }
        
        try {
            // 發送API請求
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $baseUrl, [
                'query' => $params
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            // 處理結果
            $spend = 0;
            $revenue = 0;
            $transactions = 0;
            
            // 遍歷結果
            if (isset($data['data']) && is_array($data['data'])) {
                // 根據新的API回傳格式處理資料
                $dataRows = array_slice($data['data'], 1); // 跳過標題行
                
                foreach ($dataRows as $row) {
                    // 檢查是否為陣列並且有足夠的元素
                    if (!is_array($row) || count($row) < 4) {
                        continue;
                    }
                    
                    // 直接從數組中獲取數值
                    $spend += floatval($row[0] ?? 0); // Cost
                    $transactions += floatval($row[2] ?? 0); // Actions
                    $revenue += floatval($row[3] ?? 0); // Total action value
                }
            }
            
            return [
                'spend' => $spend,
                'revenue' => $revenue,
                'transactions' => $transactions
            ];
        } catch (\Exception $e) {
            \Log::error('getFacebookAdsData error: ' . $e->getMessage());
            return ['spend' => 0, 'revenue' => 0, 'transactions' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 獲取Google廣告數據
     * 
     * @param string $baseUrl Supermetrics API基礎URL
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @param int|null $campaignId 可選的廣告活動ID
     * @return array 包含spend, revenue, transactions的數組
     */
    protected function getGoogleAdsData($baseUrl, $startDate, $endDate, $campaignId = null)
    {
        // 構建Google廣告查詢參數
        $params = [
            'ds_id' => 'AW', // Google Ads數據源
            'ds_accounts' => !empty($this->userConfig['google_ads_customer_id']) ? $this->userConfig['google_ads_customer_id'] : config('google-api.ads.customer_id'),
            'ds_user' => !empty($this->userConfig['supermetrics_user']) ? $this->userConfig['supermetrics_user'] : env('SUPERMETRICS_USER', ''), 
            'date_range_type' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fields' => 'Cost,Conversions,ConversionValue,AdvertisingChannelType',
            'max_rows' => 10000,
            'api_key' => !empty($this->userConfig['supermetrics_api_key']) ? $this->userConfig['supermetrics_api_key'] : env('SUPERMETRICS_API_KEY', 'api_SxaKf6JKjgvfLnvMSoJGPe0MS0Af5FSrOlWAYPDgkS0Iw3dS8eWtkGKFWNlUIsFf_i0raMzrcAEcc76zlBZr8RUVpuCHtRIW7Zgh')
        ];
        
        // 如果提供了廣告活動ID，添加到查詢中
        if ($campaignId) {
            $params['filter'] = 'CampaignId==' . $campaignId;
        }
        
        try {
            // 發送API請求
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $baseUrl, [
                'query' => $params
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            // 處理結果
            $spend = 0;
            $revenue = 0;
            $transactions = 0;
            
            // 遍歷結果
            if (isset($data['data']) && is_array($data['data'])) {
                // 跳過標題行
                $dataRows = array_slice($data['data'], 1);
                
                foreach ($dataRows as $row) {
                    // 檢查是否為陣列並且有足夠的元素
                    if (!is_array($row) || count($row) < 4) {
                        continue;
                    }
                    
                    $cost = floatval($row[1] ?? 0);
                    $conversions = floatval($row[2] ?? 0);
                    $conversionsValue = floatval($row[3] ?? 0);
                    
                    $spend += $cost;
                    $revenue += $conversionsValue;
                    $transactions += $conversions;
                }
            }
            
            return [
                'spend' => $spend,
                'revenue' => $revenue,
                'transactions' => $transactions
            ];
        } catch (\Exception $e) {
            \Log::error('getGoogleAdsData error: ' . $e->getMessage());
            return ['spend' => 0, 'revenue' => 0, 'transactions' => 0, 'error' => $e->getMessage()];
        }
    }

    public function getSearchConsoleData($startDate, $endDate)
    {
        if (!$this->searchConsoleClient) {
            return ['error' => 'Search Console client is not initialized'];
        }

        try {
            $searchanalytics = new \Google_Service_Webmasters($this->searchConsoleClient);
            $request = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions(['query']);
            
            // 使用用戶設定的 site_url 或默認值
            $siteUrl = !empty($this->userConfig['google_search_console_site_url']) 
                ? $this->userConfig['google_search_console_site_url'] 
                : config('google-api.search_console.site_url');
                
            $response = $searchanalytics->searchanalytics->query(
                $siteUrl,
                $request
            );

            $results = [];
            foreach ($response->getRows() as $row) {
                $results[] = [
                    'query' => $row->getKeys()[0],
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition()
                ];
            }

            return $results;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 取得到達頁面銷售概況
     * @param array $metrics
     * @param array $dimensions
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getLandingPageMetrics($metrics, $dimensions, $startDate, $endDate)
    {
        if (!$this->analyticsClient) {
            return ['error' => 'Google Analytics client is not initialized. Please check your credentials file at ' . config('google-api.analytics.credentials_path')];
        }

        try {
            // 使用用戶設定的 view_id 或默認值
            $viewId = !empty($this->userConfig['google_analytics_view_id']) 
                ? $this->userConfig['google_analytics_view_id'] 
                : config('google-api.analytics.view_id');
                
            $response = $this->analyticsClient->runReport([
                'property' => 'properties/' . $viewId,
                'dateRanges' => [new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])],
                'metrics' => array_map(function($metric) {
                    return new Metric(['name' => $metric]);
                }, $metrics),
                'dimensions' => array_map(function($dimension) {
                    return new \Google\Analytics\Data\V1beta\Dimension(['name' => $dimension]);
                }, $dimensions)
            ]);

            $results = [];
            foreach ($response->getRows() as $row) {
                $rowData = [];
                foreach ($dimensions as $i => $dimension) {
                    $rowData['page'] = $row->getDimensionValues()[$i]->getValue();
                }
                foreach ($metrics as $j => $metric) {
                    if ($metric === 'newUsers') {
                        $rowData['new_users'] = (int)$row->getMetricValues()[$j]->getValue();
                    } elseif ($metric === 'cartToViewRate') {
                        $rowData['cart_to_view_rate'] = (float)$row->getMetricValues()[$j]->getValue();
                    } elseif ($metric === 'eventValue') {
                        $rowData['traffic_value'] = (float)$row->getMetricValues()[$j]->getValue();
                    } else {
                        $rowData[$metric] = $row->getMetricValues()[$j]->getValue();
                    }
                }
                $results[] = $rowData;
            }
            return $results;
        } catch (ApiException $e) {
            \Log::error('Google Analytics API Exception: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 根據指定維度與指標查詢 Google Analytics 資料
     * @param array $metrics
     * @param array $dimensions
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAnalyticsDataWithDimensions($metrics, $dimensions, $startDate, $endDate)
    {
        if (!$this->analyticsClient) {
            return ['error' => 'Google Analytics client is not initialized. Please check your credentials file at ' . config('google-api.analytics.credentials_path')];
        }
        try {
            // 使用用戶設定的 view_id 或默認值
            $viewId = !empty($this->userConfig['google_analytics_view_id']) 
                ? $this->userConfig['google_analytics_view_id'] 
                : config('google-api.analytics.view_id');
                
            $response = $this->analyticsClient->runReport([
                'property' => 'properties/' . $viewId,
                'dateRanges' => [new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])],
                'metrics' => array_map(function($metric) {
                    return new Metric(['name' => $metric]);
                }, $metrics),
                'dimensions' => array_map(function($dimension) {
                    return new \Google\Analytics\Data\V1beta\Dimension(['name' => $dimension]);
                }, $dimensions)
            ]);
            return $this->formatAnalyticsResponseWithDimensions($response, $dimensions);
        } catch (ApiException $e) {
            \Log::error('Google Analytics API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 格式化帶有維度的 Analytics 回應
     * @param $response
     * @param array $dimensions
     * @return array
     */
    protected function formatAnalyticsResponseWithDimensions($response, $dimensions)
    {
        $result = [];
        foreach ($response->getRows() as $row) {
            $rowData = [];
            foreach ($dimensions as $i => $dimension) {
                $rowData[$dimension] = $row->getDimensionValues()[$i]->getValue();
            }
            foreach ($row->getMetricValues() as $j => $metric) {
                $rowData['metric_' . $j] = $metric->getValue();
            }
            $result[] = $rowData;
        }
        return $result;
    }
}