# ==============================================================================
# Stage 1: builder — PHP 8.3 CLI + Node 22 para compilar assets y dependencias
# ==============================================================================
FROM php:8.3-cli-alpine AS builder

RUN apk add --no-cache bash curl git unzip nodejs npm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Extensiones PHP mínimas para el build
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && apk del .build-deps

WORKDIR /app

# Dependencias PHP (cacheadas si composer.json no cambia)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Dependencias Node (cacheadas si package.json no cambia)
COPY package.json package-lock.json ./
RUN npm ci

# Código fuente completo
COPY . .

# Generar APP_KEY temporal para que artisan arranque, luego compilar frontend
RUN cp .env.example .env \
    && sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env \
    && echo "DB_DATABASE=/tmp/build.sqlite" >> .env \
    && touch /tmp/build.sqlite \
    && php artisan key:generate --force \
    && npm run build \
    && composer dump-autoload --no-dev --optimize

# ==============================================================================
# Stage 2: production — PHP-FPM para servir la aplicación Laravel
# ==============================================================================
FROM php:8.3-fpm-alpine AS production

LABEL org.opencontainers.image.title="GeoPricer App" \
      org.opencontainers.image.description="Laravel 12 + InertiaJS + Vue 3"

# Librerías de runtime
RUN apk add --no-cache \
    bash curl libpng libjpeg-turbo freetype libzip icu-libs oniguruma mysql-client

# Extensiones PHP de producción
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql gd zip bcmath intl exif pcntl opcache mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

# Copiar la aplicación completa desde el builder
COPY --from=builder --chown=www-data:www-data /app ./

# Eliminar el .env del build (tenía DB_HOST=127.0.0.1).
# En producción la config viene de las variables de entorno del contenedor.
RUN rm -f .env

# Configuraciones PHP y PHP-FPM
COPY docker/php/php.ini   /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf  /usr/local/etc/php-fpm.d/www.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permisos de directorios escribibles
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ==============================================================================
# Stage 3: nginx — Sirve assets estáticos y hace proxy a PHP-FPM
# ==============================================================================
FROM nginx:1.27-alpine AS nginx-prod

LABEL org.opencontainers.image.title="GeoPricer Nginx"

COPY --from=builder /app/public       /var/www/html/public
COPY docker/nginx/default.conf        /etc/nginx/conf.d/default.conf

# El volumen storage_public se montará aquí en runtime
RUN mkdir -p /var/www/html/public/storage

EXPOSE 80
