ARG CLI_IMAGE
ARG LAGOON_IMAGE_VERSION
FROM ${CLI_IMAGE} as cli

FROM uselagoon/php-8.4-fpm:${LAGOON_IMAGE_VERSION}

COPY .docker/images/php/01-govcms.ini /usr/local/etc/php/conf.d/
COPY .docker/images/php/02-govcms-opcache.ini /usr/local/etc/php/conf.d/
COPY .docker/images/php/99-httpav-id.conf /usr/local/etc/php-fpm.d/99-httpav-id.conf
COPY --from=cli /app /app
COPY .docker/sanitize.sh /app/sanitize.sh

RUN /app/sanitize.sh \
  && rm -rf /app/sanitize.sh
