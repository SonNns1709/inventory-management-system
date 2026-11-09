#!/bin/sh
# Khởi động container: chuẩn bị ứng dụng Laravel rồi chạy lệnh chính (apache2-foreground).
set -e

cd /var/www/html

# 1) Thư viện PHP (chỉ cần khi gắn thư mục mã nguồn lúc phát triển và chưa có vendor/)
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Chưa có vendor/ -> composer install"
    composer install --no-interaction --prefer-dist
fi

# 2) Tệp .env và APP_KEY (môi trường production truyền biến qua environment nên không cần .env)
if [ ! -f .env ] && [ -f .env.example ] && [ "${APP_ENV:-local}" != "production" ]; then
    cp .env.example .env
fi
if [ -f .env ] && [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# 3) Quyền ghi cho storage và cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# 3b) Môi trường phát triển gắn thư mục mã nguồn nên public/build của ảnh bị che mất: nhắc dựng giao diện nếu chưa có
if [ ! -f public/build/manifest.json ] && [ ! -f public/hot ]; then
    echo "[entrypoint] Chưa có public/build -> trang sẽ lỗi 'Vite manifest not found'."
    echo "[entrypoint] Chạy 'npm install && npm run build' trên máy bạn, hoặc 'docker compose --profile vite up -d'."
fi

# 4) Chờ MySQL sẵn sàng (tối đa ~60 giây)
if [ "${DB_CONNECTION:-mysql}" = "mysql" ] && [ -n "${DB_HOST:-}" ]; then
    tries=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "[entrypoint] Không kết nối được MySQL tại ${DB_HOST}"
            exit 1
        fi
        sleep 2
    done
fi

# 5) Migration / seeder theo biến môi trường (production nên chạy migrate có kiểm soát)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan storage:link >/dev/null 2>&1 || true

# 6) Bộ nhớ đệm cấu hình cho production
if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
