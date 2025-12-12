FROM php:8.2-fpm AS base

ENV PATH=$PATH:/usr/local/bin
RUN apt-get update -o Acquire::Retries=3 \
 && apt-get install -y --no-install-recommends \
    curl unzip git nginx \
    zlib1g-dev libzip-dev \
    libwebp-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql opcache gd zip intl \
 && pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer \
 && mkdir -p /var/log/nginx/80 /var/log/nginx/443 \
 && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

COPY ./conf/website.conf /etc/nginx/sites-available/website.conf

COPY ./deployweb.sh /deployweb.sh
RUN chmod +x /deployweb.sh
WORKDIR /var/www/html/
COPY . /var/www/html/
RUN /deployweb.sh
COPY ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh
ENTRYPOINT [ "/docker-entrypoint.sh" ]
##ENTRYPOINT [ "ls","-alh","/var/www/html/public" ]
