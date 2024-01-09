#!/usr/bin/env sh
##
# Sanitize codebase.
#

APP_DIR=${APP_DIR:-/app}

rm -f $APP_DIR/web/core/.env.example
rm -f $APP_DIR/web/core/install.php
rm -f $APP_DIR/web/core/INSTALL.txt
rm -f $APP_DIR/web/core/INSTALL.*.txt
rm -f $APP_DIR/web/core/CHANGELOG.txt
rm -f $APP_DIR/web/core/COPYRIGHT.txt
rm -f $APP_DIR/web/core/LICENSE.txt
rm -f $APP_DIR/web/core/MAINTAINERS.txt
rm -f $APP_DIR/web/core/UPDATE.txt
rm -f $APP_DIR/web/web.config
rm -f $APP_DIR/web/robots.txt
rm -f $APP_DIR/web/.editorconfig

if [ -f favicon.ico ]; then
  mv $APP_DIR/favicon.ico $APP_DIR/web/core/misc/favicon.ico;
fi

find $APP_DIR -type f -iname 'API.md' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'CHANGELOG.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'CONTRIBUTING.md' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'COPYRIGHT.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'INSTALL.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'INSTALL.*.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'LICENCE.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'LICENSE' -exec truncate -s 0 {} +
find $APP_DIR -type f -iname 'LICENSE-MIT' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'LICENSE.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'LICENSE.md' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'MAINTAINERS.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -name 'PATCHES.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'UPDATE.txt' -exec truncate -s 0 {}
find $APP_DIR -type f -iname 'README.txt' -exec truncate -s 0 {}

# Remove test dirs as they can contain vulnerabilities.
find $APP_DIR/web/libraries/ -type d -name test -exec rm -rf {} +
find $APP_DIR/web/libraries/ -type d -name tests -exec rm -rf {} +
find $APP_DIR/web/libraries/ -type d -name samples -exec rm -rf {} +

# Ensure directory permissions are correct.
find $APP_DIR/web/sites -type d -exec chmod 755 {} +
find $APP_DIR/web/sites -type f -exec chmod 644 {} +
chmod 755 $APP_DIR/web/sites
