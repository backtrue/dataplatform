<?php

return [
    'analytics' => [
        'view_id' => env('GOOGLE_ANALYTICS_VIEW_ID'),
        'credentials_path' => storage_path('app/private/google_service_account.json'),
    ],
    
    'ads' => [
        // 'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        // 'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        // 'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        // 'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        // 'manager_id' => env('GOOGLE_ADS_MANAGER_ID')
    ],
    
    'search_console' => [
        'site_url' => env('GOOGLE_SEARCH_CONSOLE_SITE_URL'),
        'credentials_path' => storage_path('app/private/google_service_account.json'),
    ],
];