FROM php:8.4-fpm-alpine

# ==============================================================================
# 1. System Runtime Dependencies & Utilities
# ==============================================================================
RUN apk add --no-cache \
    git \
    curl \
    bash \
    libpq \
    libzip \
    icu-libs \
    oniguruma

# ==============================================================================
# 2. Build Tools, Native Extensions & PECL Extensions Installation
# ==============================================================================
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        zip \
        bcmath \
        intl \
        opcache \
        pcntl \
    && pecl install redis \
    && pecl install xdebug \
    && docker-php-ext-enable redis xdebug \
    && apk del .build-deps

# ==============================================================================
# 3. Global Binaries (Composer)
# ==============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ==============================================================================
# 4. Working Directory & Runtime Settings
# ==============================================================================
WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]