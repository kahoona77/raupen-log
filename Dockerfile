FROM node:lts AS node
WORKDIR /build
COPY ./assets ./assets
COPY ./package.json ./package-lock.json ./webpack.config.js ./
RUN npm install
RUN npm run build


FROM kahoona/php-caddy-alpine:latest AS symfony_php_caddy

COPY docker/php/conf.d/symfony.prod.ini $PHP_INI_DIR/conf.d/symfony.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
EXPOSE 80

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

WORKDIR /srv/app

COPY ./bin ./bin
COPY ./config ./config
COPY ./migrations ./migrations
COPY ./public ./public
COPY --from=node /build/public/build public/build
COPY ./src ./src
COPY ./templates ./templates
COPY ./translations ./translations
COPY ./.env ./.env
COPY ./composer.json ./composer.json
COPY ./composer.lock ./composer.lock
COPY ./symfony.lock ./symfony.lock

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer symfony:dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync
VOLUME /srv/app/var

ENTRYPOINT ["docker-entrypoint"]
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
