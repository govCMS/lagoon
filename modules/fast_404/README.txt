Fast 404 is Super Fast and Super Amazing. It is also aggressive and hard-core.

BE CAREFUL! TEST YOUR SITE THOROUGHLY AFTER ENABLING!


-----------------------------------------------------------------
BASIC INSTALLATION INSTRUCTIONS
-----------------------------------------------------------------

*NOTE, THIS ONLY CHECKS STATIC FILES AND NOT DRUPAL PATHS*
(no settings.php modifications needed)

Step 1. Upload the module to your standard modules location (usually /modules).
Step 2. Enable the module in your modules page.


-----------------------------------------------------------------
ADVANCED INSTALLATION INSTRUCTIONS
-----------------------------------------------------------------

Step 1. Upload the module to your standard modules location (usually /modules).
Step 2. Copy the example.settings.fast404.php to your site folder and rename it
  to settings.fast404.php.
Step 3. Place the include code below at the end of your settings.php file.
Step 4. Optionally, modify the include_once path in the example
  settings.fast404.php if you did not put the module in /modules.
Step 5. Enable the module in your modules page.

/**
 * Load fast404 configuration, if available.
 *
 * Use settings.fast404.php to provide settings for Fast 404 module.
 *
 * Keep this code block at the end of this file to take full effect.
 */
#
# if (file_exists($app_root . '/' . $site_path . '/settings.fast404.php')) {
#   include $app_root . '/' . $site_path . '/settings.fast404.php';
# }


-----------------------------------------------------------------
GETTING EXTRA SPEED OUT OF THE ADVANCED INSTALL
-----------------------------------------------------------------

#1) Check extensions from settings.php, not after loading all modules.

  WARNING: This is not fully implemented and not ready for to use.
  @see: https://www.drupal.org/project/fast_404/issues/2961512

  This method is faster as it checks for missing static files at bootstrap
  stage 1 rather than 5 when the modules are loaded and events dispatched.

  To enable this functionality, uncomment the lines below near the bottom of the
  example settings.fast404.php code:

  if (file_exists($app_root . '/modules/contrib/fast_404/fast404.inc')) {
    include_once $app_root . '/modules/contrib/fast_404/fast404.inc';
    fast404_preboot($settings);
  }

#2) Enable Drupal path checking

  This checks to see if the URL being visited actually corresponds to a
  real page in Drupal. This feature may be enabled with the following.

  Global switch to turn this checking on and off (Default: off):

  $settings['fast404_path_check'] = FALSE;

#3) Give the static file checking a kick in the pants!

  Static file checking does require you to keep an eye on the extension list
  as well as a bit of extra work with the preg_match (OK, a very small amount).
  Optionally, you can use whitelisting rather than blacklisting. To turn this
  on, alter this setting in the settings.php:

  $settings['fast404_url_whitelisting'] = TRUE;

  This setting requires you to do some serious testing to ensure your site's
  pages are all still loading. Also make sure this list is accurate for your
  site:

  $settings['fast404_whitelist']  = ['index.php', 'rss.xml', 'install.php', 'cron.php', 'update.php', 'xmlrpc.php'];

#4) Disallow imagestyles file creation for anonymous users

  Normally the module skips out if 'styles' is in the URL to the static file.
  There are times when you may not want this (it would be pretty easy for
  someone to take down your site by simply hammering you with URLs with
  image derivative locations in them.

  In an ideal situation, your logged in users should have verified the pages
  are loading correctly when they create them, so any needed image derivatives
  are already made. This new setting will make it so that image derivative URLs
  are not excluded and fall under the same static file rules as non-imagestyles
  URLs. Set to false to enable this new feature.

  $settings['fast404_allow_anon_imagecache'] = TRUE;

#5) Prevent conflicts with other modules

  Some performance modules create paths to files which don't exist on disk.
  These modules conflict with fast404. To workaround this limitation, you
  can whitelist the URL fragments used by these modules.

  For example if you are using the CDN module and have the far future date
  feature enabled add the following configuration:

  $settings['fast404_string_whitelisting'] = ['cdn/farfuture'];

  If you are using AdvAgg you can use this:

  $settings['fast404_string_whitelisting'] = ['/advagg_'];

  Any further modules/paths that may need whitelisting can be added to the array.
