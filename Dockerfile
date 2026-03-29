FROM php:8.2-apache

# Installer les dépendances système et l'extension mysqli nécessaire
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli gd

# Activer le module de réécriture d'Apache (nécessaire pour le .htaccess)
RUN a2enmod rewrite

# Configuration du dossier de travail
WORKDIR /var/www/html

# Ajuster les permissions pour Apache sur le dossier uploads
RUN mkdir -p uploads && chown -R www-data:www-data uploads

# Ajouter le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
