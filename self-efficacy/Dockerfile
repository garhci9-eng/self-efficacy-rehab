FROM php:8.2-apache

# PHP 확장 설치
RUN apt-get update && apt-get install -y \
    zip unzip curl git nodejs npm \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Composer 설치
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache 설정
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# 의존성 복사 및 설치
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY package.json ./
RUN npm install

# 소스 복사
COPY . .

# 프론트엔드 빌드
RUN npm run build

# 권한 설정
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
