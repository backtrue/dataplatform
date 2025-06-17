#!/bin/bash

# 進入專案目錄
cd /path/to/your/project

# 從 Git 拉取最新代碼
git pull origin main

# 安裝 Composer 依賴
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 安裝 NPM 依賴並編譯資源
npm ci
npm run build

# 設置文件權限
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/
chmod -R 775 public/storage

# 遷移資料庫
php artisan migrate --force

# 清除緩存
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 優化應用
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 重新啟動 PHP-FPM
sudo systemctl restart php8.1-fpm

# 重新啟動 Nginx
sudo systemctl restart nginx

echo "Deployment completed successfully!"
