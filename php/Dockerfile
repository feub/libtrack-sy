FROM php:8.3-apache

# Install necessary extensions and dependencies
RUN apt-get update \
    && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Copy custom php.ini file to the container
COPY custom-php.ini /usr/local/etc/php/php.ini

# Enable necessary Apache modules
RUN a2enmod rewrite && a2enmod ssl && a2enmod socache_shmcb

WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN mkdir -p /var/www/public/covers && \
    chown -R www-data:www-data /var/www/public/covers && \
    chmod -R 775 /var/www/public/covers