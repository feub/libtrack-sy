FROM php:8.3-apache AS base

# Install necessary extensions and dependencies
RUN apt-get update \
    && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Enable necessary Apache modules
RUN a2enmod rewrite && a2enmod ssl && a2enmod socache_shmcb

WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Development stage
FROM base AS development

# Copy custom php.ini file to the container
COPY custom-php.ini /usr/local/etc/php/php.ini

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN mkdir -p /var/www/public/covers && \
    chown -R www-data:www-data /var/www/public/covers && \
    chmod -R 775 /var/www/public/covers

# Production stage
FROM base AS production

# Copy production php.ini configuration
COPY custom-php-prod.ini /usr/local/etc/php/php.ini

# Copy the Symfony application from the backend directory
COPY ../backend /var/www

# Create necessary directories and set permissions
RUN mkdir -p /var/www/var/cache /var/www/var/log /var/www/public/covers \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/var /var/www/public/covers

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist

# Set environment to production
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Clear and warm up cache for production
RUN php bin/console cache:clear --env=prod --no-debug \
    && php bin/console cache:warmup --env=prod --no-debug \
    && chown -R www-data:www-data /var/www/var

# Switch to www-data user for security
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80 443