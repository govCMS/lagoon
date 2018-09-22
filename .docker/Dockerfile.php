ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM govcmslagoon/php

COPY --from=cli /app /app
