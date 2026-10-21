ARG PHP_VERSION=8.5
# FrankenPHP 1.11 is EOL and its php8.5 tag no longer receives PHP updates; the php8.5 suffix floats with patch releases.
ARG FRANKENPHP_VERSION=1.13.1
ARG COMPOSER_VERSION=2.9
ARG NODE_VERSION=24

FROM composer:${COMPOSER_VERSION} AS composer

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS base

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC
ARG APP_DIR=/app

ENV DEBIAN_FRONTEND=noninteractive \
    TERM=xterm-color \
    OCTANE_SERVER=frankenphp \
    APP_ENV=production \
    TZ=${TZ} \
    USER=octane \
    ROOT=${APP_DIR} \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    XDG_CONFIG_HOME=/tmp/xdg-config \
    XDG_DATA_HOME=/tmp/xdg-data

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

RUN apt-get update; \
    apt-get upgrade -yqq; \
    apt-get install -yqq --no-install-recommends --show-progress \
    apt-utils \
    curl \
    git \
    ca-certificates \
    # Install PHP extensions (included with dunglas/frankenphp)
    && install-php-extensions \
    pgsql \
    pdo_pgsql \
    pdo_sqlite \
    opcache \
    redis \
    pcntl \
    zip \
    gd \
    && apt-get -y autoremove \
    && apt-get clean \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm /var/log/lastlog

RUN userdel --remove --force www-data \
    && groupadd --force -g ${WWWGROUP} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

# Remove file capabilities so the binary works with allowPrivilegeEscalation: false (no_new_privs)
# Not needed since we bind to port 8080, not 80/443
RUN setcap -r /usr/local/bin/frankenphp

COPY --link --from=composer /usr/bin/composer /usr/bin/composer

COPY --link deploy/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini

COPY --link composer.json composer.lock ./

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

FROM node:${NODE_VERSION}-slim AS npm_build

ENV ROOT=/app

WORKDIR ${ROOT}

COPY --link package.json package-lock.json ./
COPY --link embed/package.json embed/package-lock.json ./embed/

RUN npm ci --frozen-lockfile
RUN cd embed && npm ci --frozen-lockfile

COPY --link . .

RUN npm run build
RUN cd embed && npm run build

FROM base AS app

COPY --link . .
COPY --link --from=npm_build ${ROOT}/public/build public/build
COPY --link --from=npm_build ${ROOT}/public/embed public/embed
COPY --link --chmod=755 deploy/entrypoint.sh /usr/local/bin/bulla-entrypoint

RUN mkdir -p \
    storage/app/{private,public} \
    storage/framework/{sessions,views,cache/data} \
    storage/logs \
    bootstrap/cache \
    database

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --classmap-authoritative \
    --no-interaction \
    --no-ansi \
    --no-dev \
    && composer clear-cache \
    && chmod -R u=rwX,go=rX ${ROOT} \
    && chown -R ${WWWUSER}:${WWWGROUP} storage bootstrap/cache database \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache database

USER ${USER}

ENTRYPOINT ["bulla-entrypoint"]

CMD ["sh", "-c", ": \"${APP_KEY:?Set APP_KEY to a persistent application key}\" && php artisan optimize && php artisan migrate --force && php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8080 --log-level=info --caddyfile=./deploy/Caddyfile.octane --max-requests=100"]
