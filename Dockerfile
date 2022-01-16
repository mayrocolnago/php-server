FROM php:7.4-apache

RUN apt-get update -y && apt-get upgrade -y && apt-get install -y cron curl git software-properties-common
RUN apt-get install -y --no-install-recommends libpq-dev libzip-dev libmcrypt-dev libssl-dev libxml2-dev libcurl4-openssl-dev libfreetype6-dev libonig-dev pkg-config libmagickwand-dev libtool gnupg2
RUN apt-get install -y ssh libssh2-1-dev libssh2-1 make gcc telnet snmp libsnmp-dev expect

RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd
RUN docker-php-ext-configure mysqli --with-mysqli=mysqlnd
RUN docker-php-ext-install curl mbstring mysqli pdo pdo_pgsql pdo_mysql
RUN docker-php-ext-install snmp
RUN docker-php-ext-install sockets
RUN docker-php-ext-install zip
RUN docker-php-ext-install simplexml xml xmlrpc xmlwriter
RUN docker-php-ext-install iconv
RUN docker-php-ext-install opcache
RUN docker-php-ext-install gd
RUN a2enmod rewrite && a2enmod ssl && a2enmod vhost_alias
RUN apt-get install -y python3-certbot-apache

RUN curl -o /tmp/imagick.tgz https://pecl.php.net/get/imagick-3.4.4.tgz && mkdir -p /tmp/imagick && tar xvzf /tmp/imagick.tgz -C /tmp/imagick
RUN /bin/sh -c "cd /tmp/imagick/imagick-3.4.4 && phpize && ./configure && make && make install"

COPY ./data/crontab /etc/cron.d/cronjobws
RUN touch /var/log/cron.log  1> /dev/null 2> /dev/null
RUN chmod 0644 /etc/cron.d/cronjobws 1> /dev/null 2> /dev/null
RUN crontab /etc/cron.d/cronjobws  1> /dev/null 2> /dev/null
CMD /bin/sh -c "cron" && apache2-foreground
