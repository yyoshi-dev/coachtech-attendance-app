FROM php:8.4-fpm

COPY php.ini /usr/local/etc/php/

RUN apt update \
    && apt install -y default-mysql-client zlib1g-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer self-update

WORKDIR /var/www

# ホストと同じ UID/GID のユーザーを作成
ARG UID=1000
ARG GID=1000
# GIDを指定してappgroudグループを作成し、UIDを指定してappuserユーザーを作成
# -mはホームディレクトリを作るオプション
RUN groupadd -g ${GID} appgroup \
    && useradd -m -u ${UID} -g ${GID} appuser
# Dockerfileの処理やコンテナ実行時のデフォルトユーザーをappuserに切替
USER appuser