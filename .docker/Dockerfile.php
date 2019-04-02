ARG CLI_IMAGE
ARG LAGOON_IMAGE_VERSION_PHP
ARG PHP_IMAGE_VERSION
FROM ${CLI_IMAGE} as cli

FROM amazeeio/php:${PHP_IMAGE_VERSION}-fpm${LAGOON_IMAGE_VERSION_PHP}

RUN apk add gmp gmp-dev \
    && docker-php-ext-install gmp \
    && docker-php-ext-configure gmp

RUN apk add --update clamav clamav-libunrar \
    && freshclam

COPY --from=cli /app /app
COPY .docker/sanitize.sh /app/sanitize.sh

RUN /app/sanitize.sh \
  && rm -rf /app/sanitize.sh
