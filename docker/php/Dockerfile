FROM php:8.2-fpm

WORKDIR /var/www

# Remover html antigo
RUN rm -rf /var/www/html

# Instala dependências e extensões PHP
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

# Instala Redis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instala Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copia aplicação
COPY . /var/www

# Cria pastas necessárias e dá permissões
RUN mkdir -p /var/run/supervisor /var/log/supervisord /var/www/storage/logs \
    && chmod -R 777 /var/run/supervisor /var/log/supervisord /var/www/storage

# Configuração do Supervisor
COPY docker/php/supervisord.conf /etc/supervisor/supervisord.conf

# PHP ini custom
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
RUN mkdir -p /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage


# Expor porta do PHP-FPM
EXPOSE 9000

# Iniciar Supervisor
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
