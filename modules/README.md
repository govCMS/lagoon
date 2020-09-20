## 9.x compatibility note.

fast_404, config_ignore and clamav are waiting on 9.x releases on Drupal.org.

For now we are shipping with pre-patched versions:
  * clamav 8.x-1.1 with patch: https://www.drupal.org/files/issues/2020-07-21/drupal9_compatibility-3131448-8.patch
  * fast_404 8.x-2.0-alpha5: https://www.drupal.org/files/issues/2020-03-17/3042976-13.patch

A placeholder version of config_ignore is present to allow database updates to complete with success while D9 compatibility is still being worked on (https://www.drupal.org/project/config_ignore/issues/3042661)
