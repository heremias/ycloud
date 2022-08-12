FROM php:8.1.8-apache as stage1

# Setting doc root
ARG docroot=/var/www/drupal/docroot
ENV APACHE_DOCUMENT_ROOT $docroot
RUN echo "Setting docroot to: $APACHE_DOCUMENT_ROOT"
RUN sed -ri -e 's!/var/www/html!/var/www/drupal/docroot!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/drupal/docroot!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# install the PHP extensions we need
RUN set -ex; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite; \
	fi; \

	if ! command -v gpg > /dev/null; then \
		apt-get update; \
		apt-get install -y --no-install-recommends \
		gnupg2 \
		dirmngr \
		; \
		rm -rf /var/lib/apt/lists/*; \
  	fi ; \

	\
	savedAptMark="$(apt-mark showmanual)"; \
	\
	apt-get update; \
	apt-get install -y --no-install-recommends \
    autoconf \
    build-essential \
	bash-completion \
    apt-utils \
	zlib1g-dev \
    libzip-dev \
    unzip \
    zip \
    libmagick++-dev \
    libmagickwand-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
	default-mysql-client \
    sqlite3 \
    sqlitebrowser \
    libsqlite3-dev \
    libsqlite3-0 \
	git \
	wget; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --enable-gd; \
    docker-php-ext-install gd opcache zip pdo_sqlite pdo_mysql; \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
	apt-mark auto '.*' > /dev/null; \
	apt-mark manual $savedAptMark; \
	ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
		| awk '/=>/ { print $3 }' \
		| sort -u \
		| xargs -r dpkg-query -S \
		| cut -d: -f1 \
		| sort -u \
		| xargs -rt apt-mark manual; \
	\
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get -y install sudo

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# set recommended PHP.ini settings


# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini



RUN . ~/.profile

#Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin -- --filename=composer
#RUN php -r "readfile('http://files.drush.org/drush.phar');" > drush && chmod +x drush && mv drush /usr/bin/
# Create directories for Drupal
RUN curl -OL https://github.com/drush-ops/drush-launcher/releases/latest/download/drush.phar
RUN chmod +x drush.phar
RUN mv drush.phar /usr/local/bin/drush
RUN mkdir -p /tmp/drupal && chown www-data:www-data /tmp/drupal
RUN chown www-data:www-data /var/www --recursive
WORKDIR /var/www
#RUN composer require drush/drush
# Config
ENV DOCROOT=/var/www/drupal/docroot
COPY drupal /var/www/drupal
#COPY drupal /var/www/drupal
#RUN chmod 777 build.sh

COPY .htaccess /var/www

# node & yarn
RUN apt-get update && apt-get install -y nodejs npm
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN npm i -g yarn

#Run base Drupal build with drush and composer
#RUN ./build.sh

#EXPOSE 80
