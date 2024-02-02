FROM ubuntu:22.04

ENV DEBIAN_FRONTEND noninteractive
ARG _UID=1000
ARG _GID=1000

RUN export DEBIAN_FRONTEND=${DEBIAN_FRONTEND}
RUN apt-get -qy update && \
    apt-get -qy upgrade && \
    apt-get -qy install software-properties-common ca-certificates curl gnupg unzip git wget mysql-client-8.0 jq

RUN add-apt-repository ppa:ondrej/php && \
    apt-get -qy update && \
    apt-get -qy install --fix-missing --no-install-recommends \
    php-sodium \
    php8.2-cli php8.2-readline php8.2-mysql php8.2-mbstring php8.2-redis php8.2-amqp php8.2-xml php8.2-intl \
    php8.2-zip php8.2-xdebug php8.2-opcache php8.2-curl php8.2-gd php8.2-imagick php8.2-swoole php8.2-mcrypt \
    \
    php8.3-cli php8.3-readline php8.3-mysql php8.3-mbstring php8.3-redis php8.3-amqp php8.3-xml php8.3-intl \
    php8.3-zip php8.3-xdebug php8.3-opcache php8.3-curl php8.3-gd php8.3-imagick php8.3-swoole && \
    rm -rf /var/lib/apt/lists/*
RUN /usr/bin/update-alternatives --set php /usr/bin/php8.2

RUN echo 'xdebug.mode=debug' >> /etc/php/8.2/cli/php.ini
RUN echo 'xdebug.client_host=host.docker.internal' >> /etc/php/8.2/cli/php.ini
RUN echo 'xdebug.client_port=9000' >> /etc/php/8.2/cli/php.ini

RUN echo 'xdebug.mode=debug' >> /etc/php/8.3/cli/php.ini
RUN echo 'xdebug.client_host=host.docker.internal' >> /etc/php/8.3/cli/php.ini
RUN echo 'xdebug.client_port=9000' >> /etc/php/8.3/cli/php.ini

RUN php -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', '/usr/local/bin/composer');"
RUN chmod 755 /usr/local/bin/composer

RUN adduser --uid ${_UID} --home /home/app --shell /bin/bash --gecos "TinyFramework TinyFramework,,," tinyframework --disabled-password
RUN adduser --gid ${_GID} tinyframework tinyframework
RUN mkdir -p /app /home/app && chown tinyframework:tinyframework /app /home/app
RUN echo "PS1='bash$ '" >> /etc/bash.bashrc
RUN echo "export PHP_IDE_CONFIG=serverName=tinyframework" >> /etc/bash.bashrc
RUN echo "alias phpx8.2=\"php8.2 -dxdebug.mode=debug -dxdebug.client_host=host.docker.internal -dxdebug.client_port=9003 -dxdebug.start_with_request=yes \$@\"" >> /etc/bash.bashrc
RUN echo "alias phpx8.3=\"php8.3 -dxdebug.mode=debug -dxdebug.client_host=host.docker.internal -dxdebug.client_port=9003 -dxdebug.start_with_request=yes \$@\"" >> /etc/bash.bashrc
RUN echo "alias phpx=\"php -dxdebug.mode=debug -dxdebug.client_host=host.docker.internal -dxdebug.client_port=9003 -dxdebug.start_with_request=yes \$@\"" >> /etc/bash.bashrc

COPY ./entrypoint.sh /bin/entrypoint.sh
RUN chmod +x /bin/entrypoint.sh

WORKDIR /app
USER tinyframework
RUN composer create-project --stability=dev --remove-vcs sgc-fireball/tinyframework-skeleton . --repository="{\"url\": \"https://github.com/sgc-fireball/tinyframework-skeleton.git\", \"type\": \"vcs\"}" master
RUN composer update
RUN mkdir -p /app/custom-plugins/tinyframework-opcache
RUN composer config repositories.tinyframework-opcache '{"type": "path","url": "custom-plugins/*","options": {"symlink": true}}' --file composer.json

CMD ["/bin/entrypoint.sh"]
