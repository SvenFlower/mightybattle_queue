FROM php:7.4-apache
RUN DEBIAN_FRONTEND=noninteractive \
    && apt-get update \
	&& apt-get install --no-install-recommends -y \
        ca-certificates \
        curl \
        libjpeg-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        openssl \
	    zip \
	    unzip
RUN docker-php-ext-configure \
        gd --with-jpeg

RUN docker-php-ext-install \
	    mysqli \
	    bcmath \
	    gd \
	    pdo_mysql \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /var/tmp/* /tmp/*

# Move Apache Document root to sub-directory `www` (PHP frameworks convention)
ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN DEBIAN_FRONTEND=noninteractive \
	&& curl -sS https://getcomposer.org/installer | php \
  	&& mv ./composer.phar /usr/local/bin/composer
#    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
#	&& a2enmod rewrite expires

WORKDIR /var/www
