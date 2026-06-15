# Use official PHP with Apache
FROM php:8.2-apache

# Install mysqli extension for database connection
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Copy all your application files to Apache's web directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache to use mod_rewrite
RUN echo "<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/sites-available/000-default.conf

# Expose port 80 (Apache default)
EXPOSE 80

# Use the default Apache port (Render will map 10000 to 80)
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
