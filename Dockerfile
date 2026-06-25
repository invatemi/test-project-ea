FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    supervisor \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php.ini /usr/local/etc/php/conf.d/99-memory.ini
COPY docker/supervisord.conf /var/www/html/docker/supervisord.conf
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /etc/supervisor/conf.d /var/log/supervisor

ENTRYPOINT ["entrypoint.sh"]
