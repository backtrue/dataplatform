<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增用戶 - 數位行銷指標追蹤系統</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- 用戶信息和登出按鈕 -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-700">歡迎，<span class="font-semibold">{{ Auth::user()->name }}</span></p>
                    <p class="text-gray-500 text-sm">{{ Auth::user()->email }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition duration-200">
                        登出
                    </button>
                </form>
            </div>
        </div>
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">新增用戶</h1>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">返回用戶列表</a>
        </div>

        <!-- 用戶創建表單 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    
                    <!-- 錯誤提示 -->
                    @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- 基本信息 -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">基本信息</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">用戶名稱</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">密碼</label>
                                <input type="password" name="password" id="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 mb-2">
                                <button type="button" id="generate-password" class="bg-gray-200 text-gray-700 px-3 py-2 rounded hover:bg-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">自動產生</button>
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">確認密碼</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- API 配置 -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">API 配置</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="google_analytics_view_id" class="block text-sm font-medium text-gray-700 mb-1">Google Analytics View ID</label>
                                <input type="text" name="google_analytics_view_id" id="google_analytics_view_id" value="{{ old('google_analytics_view_id') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="google_ads_customer_id" class="block text-sm font-medium text-gray-700 mb-1">Google Ads Customer ID</label>
                                <input type="text" name="google_ads_customer_id" id="google_ads_customer_id" value="{{ old('google_ads_customer_id') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="google_search_console_site_url" class="block text-sm font-medium text-gray-700 mb-1">Google Search Console Site URL</label>
                                <input type="text" name="google_search_console_site_url" id="google_search_console_site_url" value="{{ old('google_search_console_site_url') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">創建用戶</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generatePasswordBtn = document.getElementById('generate-password');
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            
            generatePasswordBtn.addEventListener('click', function() {
                // 生成12位隨機密碼，包含大小寫字母、數字和特殊字符
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
                let password = '';
                
                for (let i = 0; i < 12; i++) {
                    const randomIndex = Math.floor(Math.random() * chars.length);
                    password += chars[randomIndex];
                }
                
                // 填入密碼欄位
                passwordInput.value = password;
                passwordInput.type = 'text'; // 暫時顯示密碼
                passwordConfirmationInput.value = password;
                passwordConfirmationInput.type = 'text'; // 暫時顯示密碼
                
                // 3秒後隱藏密碼
                setTimeout(function() {
                    passwordInput.type = 'password';
                    passwordConfirmationInput.type = 'password';
                }, 3000);
            });
        });
    </script>
</body>
</html>