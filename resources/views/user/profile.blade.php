<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資料 - 數位行銷指標追蹤系統</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">個人資料設定</h1>
            <a href="{{ route('dashboard') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">返回儀表板</a>
        </div>

        <!-- 個人資料表單 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <form action="{{ route('user.profile.update') }}" method="POST">
                    @csrf
                    
                    <!-- 基本信息 -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">基本信息</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">用戶名稱</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- 密碼修改 -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">修改密碼 (選填)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">新密碼</label>
                                <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">留空表示不修改密碼</p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">確認新密碼</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- API 配置信息顯示 -->
                    @if($user->google_analytics_view_id || $user->google_ads_customer_id || $user->supermetrics_user || $user->google_search_console_site_url)
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">API 配置信息</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($user->google_analytics_view_id)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics View ID</label>
                                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">{{ $user->google_analytics_view_id }}</div>
                            </div>
                            @endif
                            
                            @if($user->google_ads_customer_id)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Google Ads Customer ID</label>
                                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">{{ $user->google_ads_customer_id }}</div>
                            </div>
                            @endif
                            

                            
                            @if($user->google_search_console_site_url)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Google Search Console Site URL</label>
                                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">{{ $user->google_search_console_site_url }}</div>
                            </div>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-2">API 配置信息僅供查看，如需修改請聯繫管理員</p>
                    </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">更新個人資料</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>