ARG CLI_IMAGE
ARG LAGOON_IMAGE_VERSION
FROM ${CLI_IMAGE} as cli

FROM uselagoon/php-8.1-fpm:${LAGOON_IMAGE_VERSION}

RUN apk add --no-cache --update clamav clamav-libunrar --repository http://dl-cdn.alpinelinux.org/alpine/edge/main/ \
    && freshclam

COPY .docker/images/php/00-govcms.ini /usr/local/etc/php/conf.d/
COPY --from=cli /app /app
COPY .docker/sanitize.sh /app/sanitize.sh

RUN mkdir -p /usr/share/ca-certificates/letsencrypt \
  && curl -o /usr/share/ca-certificates/letsencrypt/lets-encrypt-r3.crt https://letsencrypt.org/certs/lets-encrypt-r3.pem \
  && echo -e "\nletsencrypt/lets-encrypt-r3.crt" >> /etc/ca-certificates.conf \
  && update-ca-certificates

RUN /app/sanitize.sh \
  && rm -rf /app/sanitize.sh
