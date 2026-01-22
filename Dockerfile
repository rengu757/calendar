# 1. Започваме с PHP + Apache
FROM php:8.2-apache

# 2. Инсталираме системните библиотеки
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip

# 3. Включваме mod_rewrite
RUN a2enmod rewrite

# 4. === ЯДРЕНАТА ОПЦИЯ ===
# Изтриваме старата конфигурация и създаваме нова директно тук.
# FallbackResource /index.php е командата, която оправя всичко.
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride None\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# 5. Копираме кода
COPY . /var/www/html

# 6. Инсталираме Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# 7. Инсталираме зависимостите
RUN composer install --optimize-autoloader --no-scripts

# 8. Оправяме правата (много важно за Render)
RUN chown -R www-data:www-data /var/www/html
