FROM php:8.2-apache

# Install ekstensi sistem yang dibutuhkan
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP (PDO MySQL, ZIP, dan GD untuk image processing)
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Aktifkan modul rewrite Apache untuk .htaccess
RUN a2enmod rewrite

# Set working directory ke document root Apache
WORKDIR /var/www/html

# Copy semua file aplikasi ke dalam container
COPY . /var/www/html/

# Jalankan composer install
RUN composer install --no-dev --optimize-autoloader
# Buat folder uploads jika belum ada dan berikan akses write ke Apache (www-data)
RUN mkdir -p /var/www/html/assets/uploads/avatars \
    && mkdir -p /var/www/html/assets/uploads/dokumen \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

# Expose port 80
EXPOSE 80
