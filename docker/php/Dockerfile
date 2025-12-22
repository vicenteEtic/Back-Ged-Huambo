FROM php:8.2-fpm

WORKDIR /var/www

RUN rm -rf /var/www/html

RUN apt-get update && apt-get install -y \
        build-essential \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        locales \
        zip \
        jpegoptim optipng pngquant gifsicle \
        vim \
        unzip \
        git \
        curl \
        libzip-dev \
        apt-utils \
        libonig-dev \
        supervisor \
        procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip pdo_mysql mbstring exif bcmath gd pcntl \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install redis \
    && docker-php-ext-enable redis

    RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"


COPY . /var/www

RUN mkdir -p /var/run/supervisor /var/log/supervisord /var/www/storage/logs \
    && chmod -R 777 /var/run/supervisor /var/log/supervisord /var/www/storage

COPY docker/php/supervisord.conf /etc/supervisor/supervisord.conf

# SSL
COPY cacert.pem /usr/local/share/ca-certificates/cacert.pem
RUN update-ca-certificates

# Copiar cacert e atualizar certificados do sistema
COPY cacert.pem /usr/local/share/ca-certificates/cacert.crt
RUN update-ca-certificates

# PHP ini custom
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN apt-get update && apt-get install -y ca-certificates \
    && update-ca-certificates
    RUN mkdir -p /var/log/supervisord \
    && chown -R www-data:www-data /var/log/supervisord


EXPOSE 9000


CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
