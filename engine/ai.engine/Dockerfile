FROM php:8.2-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY . /app

RUN docker-php-ext-install -j"$(nproc)" curl

CMD ["php", "-v"]
