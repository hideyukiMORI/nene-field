# NeNe Field — PHP 8.4 Apache backend (dev/runtime).
# Mirrors the NENE2 framework image (vendor/hideyukimori/nene2/docker/php/Dockerfile):
# only public_html/ is the Apache document root; source, vendor, and tests stay
# outside the web root.
FROM php:8.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && printf '<Directory /var/www/html/public_html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/nene-field.conf \
    && a2enconf nene-field \
    && sed -ri -e 's!/var/www/html!/var/www/html/public_html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public_html!g' /etc/apache2/apache2.conf \
    && sed -i 's/^ServerTokens .*/ServerTokens Prod/' /etc/apache2/conf-available/security.conf \
    && sed -i 's/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
    && printf 'Header always unset X-Powered-By\n' \
       > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening \
    && printf 'output_buffering = 4096\ndisplay_errors = Off\nlog_errors = On\nexpose_php = Off\n' \
       > /usr/local/etc/php/conf.d/nene-field-security.ini

WORKDIR /var/www/html
