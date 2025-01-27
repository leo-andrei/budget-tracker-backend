FROM php:8.1-apache

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    gnupg dirmngr curl && \
    mkdir -p /root/.gnupg && chmod 700 /root/.gnupg && \
    gpg --no-default-keyring --keyring /usr/share/keyrings/debian-archive-keyring.gpg --keyserver keyserver.ubuntu.com --recv-keys \
    112695A0E562B32A \
    54404762BBB6E853 \
    BDE6D2B9216EC7A8 && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN apt-get update --allow-unauthenticated && \
    apt-get install -y --allow-unauthenticated --no-install-recommends \
    cron \
    vim \
    locales \
    coreutils \
    apt-utils \
    git \
    libicu-dev \
    g++ \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libxslt-dev && \
    apt-get clean

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls \
    && mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring zip gd xsl \
    && a2enmod rewrite

RUN pecl install apcu \
    && docker-php-ext-enable apcu

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions \
    && sync \
    && install-php-extensions amqp

WORKDIR /var/www
