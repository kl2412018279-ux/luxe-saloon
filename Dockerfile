# Use official PHP with Apache
FROM php:8.2-apache

# Install mysqli extension for database connection
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Copy all your application files to Apache's web directory
COPY spasystem/ /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache
RUN echo "<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
