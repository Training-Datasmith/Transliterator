FROM php:7.2-cli@sha256:42ffbc0798e4449bbd1e14fc4dcb87774aa1ad1900a09ef6a965bc0880aa2161

RUN set -eux; \
    printf '%s\n' \
        'Acquire::Check-Valid-Until "false";' \
        > /etc/apt/apt.conf.d/99no-check-valid-until; \
    printf '%s\n' \
        'deb https://archive.debian.org/debian buster main' \
        'deb https://archive.debian.org/debian-security buster/updates main' \
        > /etc/apt/sources.list; \
    apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libcurl4-openssl-dev; \
    docker-php-ext-install -j"$(nproc)" mbstring curl; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app
