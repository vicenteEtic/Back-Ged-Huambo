# Imagem base
FROM php:8.2-fpm

WORKDIR /var/www

# Remover html default
RUN rm -rf /var/www/html

# Instalar dependências do sistema
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
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP
RUN docker-php-ext-install zip pdo_mysql mbstring exif bcmath

# GD com suporte
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Redis
RUN mkdir -p /usr/src/php/ext/redis \
    && curl -fsSL https://pecl.php.net/get/redis | tar xvz -C "/usr/src/php/ext/redis" --strip 1 \
    && docker-php-ext-install redis

# PCNTL
RUN docker-php-ext-install pcntl

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copiar aplicação
COPY . /var/www

# Criar diretórios para logs e socket
RUN mkdir -p /var/run/supervisor && \
    mkdir -p /var/log/supervisor && \
    mkdir -p /var/www/storage/logs && \
    chmod -R 777 /var/www/storage && \
    chmod -R 777 /var/run/supervisor && \
    chmod -R 777 /var/log/supervisor

# Copiar configuração do supervisor
COPY supervisord.conf /etc/supervisor/supervisord.conf

EXPOSE 9000

# Iniciar supervisor (gestor do php-fpm + queue worker)
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
