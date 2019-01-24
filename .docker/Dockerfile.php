ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM govcms8dev/php:1.0.0-beta1

# Temporary override until lagoon PR is available in upstream image.
# https://github.com/amazeeio/lagoon/issues/787
ENV PHP_MAX_INPUT_VARS=2000

RUN apk add --update clamav clamav-libunrar \
    && freshclam

COPY --from=cli /app /app
COPY .docker/sanitize.sh /app/sanitize.sh

RUN /app/sanitize.sh \
  && rm -rf /app/sanitize.sh
