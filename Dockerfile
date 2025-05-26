# Gunakan image PHP resmi dengan Apache
FROM php:8.2-apache

# Instal ekstensi PHP yang dibutuhkan (mysqli atau pdo_mysql)
RUN docker-php-ext-install pdo_mysql

# Salin file aplikasi ke direktori web server di dalam container
COPY . /var/www/html/

# (Opsional) Atur izin jika diperlukan
# RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (port default Apache)
EXPOSE 80