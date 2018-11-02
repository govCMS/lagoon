#!/usr/bin/env sh
##
# Sanitize codebase.
#

APP_DIR=${APP_DIR:-/app}

rm $APP_DIR/web/core/.env.example
rm $APP_DIR/web/core/install.php
rm $APP_DIR/web/core/INSTALL.txt
rm $APP_DIR/web/core/INSTALL.*.txt
rm $APP_DIR/web/core/CHANGELOG.txt
rm $APP_DIR/web/core/COPYRIGHT.txt
rm $APP_DIR/web/core/LICENSE.txt
rm $APP_DIR/web/core/MAINTAINERS.txt
rm $APP_DIR/web/core/UPDATE.txt
rm $APP_DIR/web/web.config
rm $APP_DIR/web/robots.txt
rm $APP_DIR/.editorconfig

if [ -f favicon.ico ]; then
  mv $APP_DIR/favicon.ico $APP_DIR/web/core/misc/favicon.ico;
fi

find $APP_DIR -type f -iname 'API.md' -exec rm {} +
find $APP_DIR -type f -iname 'CHANGELOG.txt' -exec rm {} +
find $APP_DIR -type f -iname 'CONTRIBUTING.md' -exec rm {} +
find $APP_DIR -type f -iname 'COPYRIGHT.txt' -exec rm {} +
find $APP_DIR -type f -iname 'INSTALL.txt' -exec rm {} +
find $APP_DIR -type f -iname 'INSTALL.*.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENCE.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE-MIT' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE.md' -exec rm {} +
find $APP_DIR -type f -iname 'MAINTAINERS.txt' -exec rm {} +
find $APP_DIR -type f -name 'PATCHES.txt' -exec rm {} +
find $APP_DIR -type f -iname 'UPDATE.txt' -exec rm {} +
find $APP_DIR -type f -iname 'README.txt' -exec rm {} +

# Remove test dirs as they can contain vulnerabilities.
find $APP_DIR/web/libraries/ -type d -name test -exec rm -rf {} +
find $APP_DIR/web/libraries/ -type d -name tests -exec rm -rf {} +
find $APP_DIR/web/libraries/ -type d -name samples -exec rm -rf {} +
