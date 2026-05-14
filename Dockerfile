# Stage 1: Builder
FROM debian:bookworm-slim AS builder

SHELL ["/bin/bash", "-c"]
WORKDIR /app

ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y curl gnupg tzdata unzip git

RUN curl -sL https://deb.nodesource.com/setup_20.x | bash \
    && apt-get install -y nodejs php php-fpm php-zip nginx

COPY . .
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer update && composer install

# Stage 2: Final Image
FROM php:7.4-apache-bullseye

COPY --from=builder /app/config /var/www/config
COPY --from=builder /app/web /var/www/html/
COPY --from=builder /app/node_modules /var/www/node_modules
COPY --from=builder /app/src /var/www/src
COPY --from=builder /app/vendor /var/www/vendor

CMD ["apache2-foreground"]