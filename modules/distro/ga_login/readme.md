INTRODUCTION
------------

This module will allow you to add Time-based One-time Password Algorithm 
(also called "Two Step Authentication" or "Multi-Factor Authentication") 
support to user logins. It works with Google's Authenticator app system 
and supports most (if not all) OATH based HOTP/TOTP systems.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ga_login

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/ga_login
   
REQUIREMENTS
------------

This module requires the following module:

  * Two-factor Authentication (TFA) (https://www.drupal.org/project/tfa)
  
Users will need an application for their phone, tablet or computer that is
capable of generating authentication codes.  As of the date of this ReadMe,
some of the more popular options include:

  * Google Authenticator (Android, iOS, and Blackberry)
  * Authy (Android, iOS, Windows, OSX, Linux, Chrome browser extension)
  * FreeOTP (Android, iOS)
  * WinAuth (Windows)
  * GAuth Authenticator (Chrome browser extension and Chrome OS)
  
INSTALLATION
------------

  * This module depends on third-party libraries listed in composer.json,
    and should therefore be installed using Composer.  See
    https://www.drupal.org/node/2718229 for description and guidance on the
    installation process.
  
CONFIGURATION
-------------

  * The TFA module should be installed and configured before installing the 
    the Google Authenticator login module.  
  * Once this module is installed, two additional options will become available 
    as Validation plugins on the TFA Settings page - /admin/config/people/tfa. 
  * After selecting the HMAC-based (HOTP) or Time-based (TOTP) variant,
    additional settings will become available.
  * Under the heading of Extra Settings
    * Number of Accepted Codes (TOTP) or Counter Window (HOTP): generally
      you should leave these at their default settings.
    * Use site name as OTP QR code name prefix: use the name of the website
      as the prefix that will help the user identify this site's code in their
      authentication application.  This is mainly for use in multi-site
      installations that share a common TFA/GA Login configuration, and should
      probably not be used in other settings.
    * OTP QR Code Prefix: a string to use to help the user identify this
      site's code in their authentication application.  You should usually
      change this from the generic "TFA" value to something more meaningful.
