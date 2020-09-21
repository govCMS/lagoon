## 9.x compatibility note.

fast_404 and clamav are waiting on 9.x releases on Drupal.org but have viable 9.x patches.

For now we are shipping with pre-patched versions:
  * clamav 8.x-1.1 with patch: https://www.drupal.org/files/issues/2020-07-21/drupal9_compatibility-3131448-8.patch
  * fast_404 8.x-2.0-alpha5: https://www.drupal.org/files/issues/2020-03-17/3042976-13.patch

Several placeholder modules and themes are shipped in this folder that are not yet ready for D9.

These modules allow database updates to complete without failure for early pre-release testing.

  * `config_ignore`
  * `module_filter`
  * `event_log_track_*`
  * `module_filter`
  * `adminimal_theme`
  * `govcms_admin_theme`
  * `govcmsui`
  * `govcms8_uikit_starter`
