FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so this layer is cached until composer.json changes.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chmod +x docker/entrypoint.sh

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
