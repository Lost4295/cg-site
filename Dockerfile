# Image de production : nginx + php-fpm dans le même conteneur, sans root.
# Compatible avec la Pod Security "restricted" (UID 33, port 8080,
# système de fichiers en lecture seule : seuls /tmp, var/ et public/img sont
# écrits, via des volumes montés par Kubernetes).
# PHP 8.5 et pas 8.6 : lcobucci/jwt (requis par lexik/jwt-authentication-bundle)
# n'accepte que PHP ~8.2 à ~8.5 dans composer.lock
FROM php:8.5-fpm

# pdo et opcache sont intégrés à PHP 8.5 : seules les autres extensions sont compilées
RUN apt-get update -o Acquire::Retries=3 \
 && apt-get install -y --no-install-recommends \
    nginx unzip git \
    zlib1g-dev libzip-dev \
    libwebp-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j1 pdo_mysql gd zip intl \
 && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/* \
 # nginx écrit ses logs sur la sortie du conteneur
 && ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY conf/nginx.conf /etc/nginx/nginx.conf
COPY conf/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY conf/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker-entrypoint.sh /docker-entrypoint.sh

ENV APP_ENV=prod \
    APP_DEBUG=0

WORKDIR /var/www/html

# Dépendances d'abord, pour profiter du cache de build
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --no-progress

COPY . .
# assets:install copie les assets des bundles (EasyAdmin) dans public/bundles.
# La console a besoin que les variables existent pour démarrer : valeurs factices,
# limitées à cette commande (pas dans l'image). Le cache de prod est reconstruit
# au démarrage du pod (initContainer), avec les vraies variables.
RUN composer dump-autoload --no-dev --classmap-authoritative \
 && APP_SECRET=build \
    DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0.32" \
    MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0" \
    MAILER_DSN="null://null" \
    CLIENT_ID=build CLIENT_SECRET=build REDIRECT_URI=https://localhost/ DISCORD_WEBHOOK=https://localhost/ \
    DEFAULT_URI=https://localhost JWT_PASSPHRASE=build \
    JWT_SECRET_KEY=/nonexistent/private.pem JWT_PUBLIC_KEY=/nonexistent/public.pem \
    php bin/console assets:install public --no-debug \
 && rm -rf var/* public/img \
 # public/img : PVC des uploads ; config/jwt : secret cg-site-jwt (clés JWT)
 && mkdir -p public/img config/jwt \
 && chmod +x /docker-entrypoint.sh

USER 33:33
EXPOSE 8080
ENTRYPOINT ["/docker-entrypoint.sh"]
