FROM thecodingmachine/php:7.4.27-v4-apache-node16
ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV TZ=Europe/Berlin
USER root
RUN usermod -a -G www-data docker
COPY package.json /var/www/html
COPY package-lock.json /var/www/html
COPY webpack.config.js /var/www/html


RUN npm install
COPY assets /var/www/html/assets
COPY public /var/www/html/public
RUN mkdir -m 777 -p public/build
RUN npm run build
RUN rm -rf node_modules/

COPY . /var/www/html

RUN composer install
RUN chmod -R 775 public/build
RUN mkdir -p var/cache
RUN chown -R www-data:www-data var
RUN chmod -R 777 var
RUN chown -R www-data:www-data public/uploads/
RUN chmod -R 775 public/uploads/
USER docker