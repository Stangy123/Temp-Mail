FROM php:8.2-apache

RUN a2enmod rewrite headers

WORKDIR /var/www/html
COPY . /var/www/html

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
