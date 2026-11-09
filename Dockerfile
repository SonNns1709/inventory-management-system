# syntax=docker/dockerfile:1
#
# Dockerfile nhiều tầng (multi-stage build) — ứng với mục "II.1 Dockerfile (multi-stage build)" ở Chương 4 của báo cáo
# (tệp Word nhận được chỉ có tiêu đề mục này trong mục lục, chưa có nội dung nên các tầng dưới đây theo thông lệ).
#   assets : dựng CSS/JS (Vite + Tailwind) bằng Node
#   vendor : cài thư viện PHP production bằng Composer
#   app    : ảnh chạy thật (PHP 8.4 + Apache) chỉ chứa mã nguồn, vendor và public/build
#
# Dựng ảnh production:   docker build --target app -t inventory-management .
# Môi trường phát triển: dùng docker-compose.yml (gắn thư mục mã nguồn vào container).

# ---------- Tầng 1: tài nguyên giao diện ----------
FROM node:22-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json* .npmrc ./
RUN if [ -f package-lock.json ]; then npm ci --ignore-scripts; else npm install --ignore-scripts; fi

# Tailwind quét Blade/Livewire/PHP để biết cần sinh những class nào
COPY vite.config.js ./
COPY resources ./resources
COPY app ./app
RUN npm run build

# ---------- Tầng 2: thư viện PHP ----------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock* ./
# --no-scripts: chưa có mã nguồn nên chưa chạy được artisan; dump-autoload sẽ chạy lại ở tầng app
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --ignore-platform-reqs

# ---------- Tầng 3: ảnh chạy ----------
FROM php:8.4-apache AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip curl \
    && docker-php-ext-install pdo_mysql bcmath intl zip opcache pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
