#!/bin/sh

# Provide aliases to help with general operations.
# The default uselagoon bashrc skips the standard .bash_aliases include
# so we override this file and include our aliases here.
#
# @see https://github.com/uselagoon/lagoon-images/blob/main/images/php-cli/entrypoints/55-cli-helpers.sh

dsql () {
	drush sql-sync $1 @self
}

dfiles () {
	drush rsync $1:%files @self:%files
}

# end Lagoon helpers.

# Ensure absolute paths are used.
alias composer=/usr/local/bin/composer
alias drush=/app/vendor/bin/drush
