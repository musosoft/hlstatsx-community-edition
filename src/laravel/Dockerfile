FROM node:24.19.0-alpine3.24 AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.5.12-cli-alpine3.24

RUN set -eux; \
    apk add --no-cache libzip; \
    apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS libzip-dev; \
    docker-php-ext-install pdo_mysql zip; \
    apk del .build-dependencies; \
    apk add --no-cache curl; \
    curl -fsSLo /tmp/composer.phar https://getcomposer.org/download/2.10.3/composer.phar; \
    echo '7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6  /tmp/composer.phar' | sha256sum -c -; \
    install -m 0755 /tmp/composer.phar /usr/local/bin/composer; \
    rm /tmp/composer.phar

WORKDIR /app
COPY . /app
COPY --from=assets /app/public/build /app/public/build
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod +x artisan

EXPOSE 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
