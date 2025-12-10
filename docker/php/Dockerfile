# Imagem base
FROM php:8.2-fpm

WORKDIR /var/www

# Remover html default
RUN rm -rf /var/www/html

# Instalar dependências do sistema e extensões PHP numa única camada
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

# Redis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar aplicação
COPY . /var/www

# Criar diretórios para logs e socket
RUN mkdir -p /var/run/supervisor /var/log/supervisord /var/www/storage/logs \
    && chmod -R 777 /var/run/supervisor /var/log/supervisord /var/www/storage


# Copiar configuração do supervisor
COPY supervisord.conf /etc/supervisor/supervisord.conf

# Copiar o cacert.pem para dentro do container
COPY cacert.pem /usr/local/share/ca-certificates/cacert.pem
RUN update-ca-certificates


# Copiar seu php.ini customizado (opcional)
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

EXPOSE 9000

# Iniciar supervisor (gestor do php-fpm + queue worker)
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
