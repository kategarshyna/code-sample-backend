FROM php:7.4-fpm

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
    netcat \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install zip pdo_mysql intl

# Install Xdebug
RUN pecl install xdebug-3.1.6\
    && docker-php-ext-enable xdebug

COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Create a script to run both composer install and php-fpm
COPY .docker/bin/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]

EXPOSE 9000
