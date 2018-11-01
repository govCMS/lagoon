#!/usr/bin/env sh
##
# Sanitize codebase.
#

APP_DIR=${APP_DIR:-/app}

rm $APP_DIR/install.php
rm $APP_DIR/INSTALL.txt
rm $APP_DIR/INSTALL.*.txt
rm $APP_DIR/COPYRIGHT.txt
rm $APP_DIR/MAINTAINERS.txt
rm $APP_DIR/UPGRADE.txt
rm $APP_DIR/README.txt
rm $APP_DIR/web.config
rm $APP_DIR/robots.txt
rm $APP_DIR/.editorconfig

if [ -f favicon.ico ]; then
  mv $APP_DIR/favicon.ico $APP_DIR/misc/favicon.ico;
fi

find $APP_DIR -type f -name 'PATCHES.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE-MIT' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENCE.txt' -exec rm {} +
find $APP_DIR -type f -iname 'LICENSE.md' -exec rm {} +
find $APP_DIR -type f -iname 'CHANGELOG.txt' -exec rm {} +
find $APP_DIR -type f -iname 'CONTRIBUTING.md' -exec rm {} +
find $APP_DIR -type f -iname 'API.md' -exec rm {} +

# Remove test dirs as they can contain vulnerabilities.
find $APP_DIR/profiles/govcms/libraries/ -type d -name test -exec rm -rf {} +
find $APP_DIR/profiles/govcms/libraries/ -type d -name tests -exec rm -rf {} +
find $APP_DIR/profiles/govcms/libraries/ -type d -name samples -exec rm -rf {} +
