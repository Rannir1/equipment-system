FROM php:8.2-fpm

# Install extensions + nginx
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd mysqli \
    && rm -rf /var/lib/apt/lists/*

# PHP config
RUN echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Asia/Jerusalem" >> /usr/local/etc/php/conf.d/custom.ini

# Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

WORKDIR /var/www/html

EXPOSE 80

CMD ["/start.sh"]
