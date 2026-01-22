# 1. Използваме PHP 8.2 с Apache
FROM php:8.2-apache

# 2. Инсталираме системните библиотеки (вкл. zip и pgsql)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip

# 3. Включваме mod_rewrite модула на Apache
RUN a2enmod rewrite

# 4. Настройваме Apache да гледа в /public папката
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# === ТУК Е МАГИЯТА ===
# Тази команда разрешава на .htaccess файла да работи!
# Без нея Apache игнорира правилата за API пътищата.
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 5. Копираме кода
COPY . /var/www/html

# 6. Инсталираме Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# 7. Инсталираме библиотеките без скриптове (за да не гърми при build)
RUN composer install --optimize-autoloader --no-scripts

# 8. Оправяме правата на файловете
RUN chown -R www-data:www-data /var/www/html
