# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=8.1
ARG CADDY_VERSION=2

FROM node:lts AS node
WORKDIR /build
COPY ./assets ./assets
COPY ./package.json ./package-lock.json ./webpack.config.js ./
RUN npm install
RUN npm run build

# "php" stage
FROM php:${PHP_VERSION}-fpm-alpine AS symfony_php_caddy

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		fcgi \
		file \
		gettext \
		git \
		caddy \
	;

ARG APCU_VERSION=5.1.21
RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		zlib-dev \
	; \
	\
	docker-php-ext-configure zip; \
	docker-php-ext-install -j$(nproc) \
		intl \
		zip \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .phpexts-rundeps $runDeps; \
	\
	apk del .build-deps


RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php/conf.d/symfony.prod.ini $PHP_INI_DIR/conf.d/symfony.ini

COPY docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

VOLUME /var/run/php

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
