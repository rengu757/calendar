# Използваме готова PHP версия с Apache
FROM php:8.2-apache

# Инсталираме нужните библиотеки: добавихме libzip-dev за ZIP поддръжка!
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip

# Включваме mod_rewrite
RUN a2enmod rewrite

# Настройваме Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Копираме кода
COPY . /var/www/html

# Инсталираме Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Разрешаваме на Composer да работи като root
ENV COMPOSER_ALLOW_SUPERUSER=1

# --- ВАЖНАТА ПРОМЯНА ---
# 1. Махнахме --no-dev (за да имаш всички библиотеки)
# 2. Добавихме --no-scripts (за да НЕ гърми, опитвайки се да чисти кеша при build)
RUN composer install --optimize-autoloader --no-scripts

# Оправяме правата
RUN chown -R www-data:www-data /var/www/html