ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM govcms8dev/php

RUN apk add --update clamav clamav-libunrar \
    && freshclam

COPY --from=cli /app /app
COPY .docker/sanitize.sh /app/sanitize.sh

RUN /app/sanitize.sh \
  && rm -rf /app/sanitize.sh
