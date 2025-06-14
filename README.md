## 🛠 前置需求

請先確認你已安裝以下工具：

- PHP >= 8.1
- Composer
- 資料庫（如 MySQL）
- Node.js 與 npm（用於前端建置）
- Laravel CLI（非必要，但可加速流程）

---

## 🪜 安裝與啟動步驟

### 第 1 步：安裝 Composer（若尚未安裝）

```bash
# macOS / Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows：請前往官網下載安裝器
# https://getcomposer.org/download/


開發環境啟動步驟：

1. 請將env.txt重新命名為.env
2. 將.env的以下設定改為您環境的開發設定
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bvg-dashboard
DB_USERNAME=root
DB_PASSWORD=root1234

3. 執行指令
composer install
php artisan migrate

4. 啟動專案
php artisan serve
