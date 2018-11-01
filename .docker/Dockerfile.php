ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM govcms8lagoon/php

COPY --from=cli /app /app
