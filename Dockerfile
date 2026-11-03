FROM php:8.2-apache

# PostgreSQL driver for PDO (needed to talk to Supabase)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Allow PUT/DELETE requests through Apache (the API uses them)
RUN a2enmod rewrite headers

# Render sends traffic to the port in $PORT (default 10000). Apache must listen on it.
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Hide PHP warnings from visitors (errors still go to the logs)
RUN echo "display_errors=Off\nlog_errors=On" > /usr/local/etc/php/conf.d/prod.ini

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000
