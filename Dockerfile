ARG PHP_VERSION=8.4
ARG COMPOSER_VERSION=2

# ==============================================================================
# Base stage - System packages and PHP extensions
# ==============================================================================
FROM php:${PHP_VERSION}-fpm-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    dumb-init \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    zip \
    unzip \
  && docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    opcache \
    pdo_pgsql \
    zip

COPY --from=composer:${COMPOSER_VERSION} /usr/bin/composer /usr/bin/composer

# ==============================================================================
# Dependencies stage - Install all Composer packages (including dev)
# ==============================================================================
FROM base AS dependencies

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer \
  composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ==============================================================================
# Production dependencies stage - Strip dev packages
# ==============================================================================
FROM base AS prod-deps

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY --from=dependencies /var/www/html/vendor ./vendor

RUN --mount=type=cache,target=/root/.composer \
  composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# ==============================================================================
# Runtime stage - Final production image
# ==============================================================================
FROM base AS runtime

WORKDIR /var/www/html

COPY --from=prod-deps --chown=www-data:www-data /var/www/html/vendor ./vendor

COPY --chown=www-data:www-data . .

RUN composer dump-autoload --optimize \
  && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ARG PORT=8080
ENV PORT=${PORT}

EXPOSE ${PORT}

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD curl -f http://localhost:${PORT}/up 2>/dev/null || exit 1

ENTRYPOINT ["dumb-init", "--"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
