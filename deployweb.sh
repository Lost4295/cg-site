#!/bin/bash
ls /var/www/html
php /var/www/html/bin/console doctrine:database:create --if-not-exists
php /var/www/html/bin/console doctrine:schema:update --force
composer install
ln -sf /etc/nginx/sites-available/website.conf /etc/nginx/sites-enabled/website.conf
rm -f /etc/nginx/sites-enabled/default
chown -R www-data:www-data /var/www/html /var/log/nginx
ln -sf /dev/stdout /var/log/nginx/access.log 
ln -sf /dev/stderr /var/log/nginx/error.log