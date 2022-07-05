
Drupal core provides a module called 'Update status' which provides update
notifications when new versions of core or contributed modules and themes are
available.

If for some reason you do not want to enable this functionalilty (for example,
you have privacy concerns, or you plan to handle your own system updates (e.g.
for a multisite install)), you can use this module to turn off the warning that
appears in the administration panel when Update status module is disabled.

This module has absolutely no functionality; it simply disables the warning.

To install:
- Place the module in your modules/contrib directory.
- You can only enable this by updating core_extensions.yml or enabling it via Drush. It is hidden from the UI.

