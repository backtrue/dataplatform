<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;
    
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    
    public function getMetrics(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));
        
        return response()->json([
            'overview' => $this->dashboardService->getOverviewMetrics($startDate, $endDate),
            'advertising' => $this->dashboardService->getAdvertisingMetrics($startDate, $endDate),
            'search_console' => $this->dashboardService->getSearchConsoleMetrics($startDate, $endDate),
            'conversion' => $this->dashboardService->getConversionMetrics($startDate, $endDate),
            'landing_page' => $this->dashboardService->getLandingPageMetrics($startDate, $endDate),
            'customer_order_ratio' => $this->dashboardService->getCustomerOrderRatioMetrics($startDate, $endDate)
        ]);
    }
    
    public function dailyTrafficAndConversion(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $data = $this->dashboardService->getDailyTrafficAndConversionRate($startDate, $endDate);
        return response()->json($data);
    }
}