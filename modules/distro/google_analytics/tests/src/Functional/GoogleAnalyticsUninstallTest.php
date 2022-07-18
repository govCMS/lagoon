<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test uninstall functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsUninstallTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer modules',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if the module cleans up the disk on uninstall.
   */
  public function testGoogleAnalyticsUninstall() {
    $cache_path = 'public://google_analytics';
    $ua_code = 'UA-123456-1';

    // Show tracker in pages.
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable local caching of gtag.js.
    $this->config('google_analytics.settings')->set('cache', 1)->save();

    // Load page to get the gtag.js downloaded into local cache.
    $this->drupalGet('');

    $file_system = \Drupal::service('file_system');
    // Test if the directory and gtag.js exists.
    $this->assertTrue($file_system->prepareDirectory($cache_path), 'Cache directory "public://google_analytics" has been found.');
    $this->assertTrue(file_exists($cache_path . '/gtag.js'), 'Cached analytics.js tracking file has been found.');
    $this->assertTrue(file_exists($cache_path . '/gtag.js.gz'), 'Cached analytics.js.gz tracking file has been found.');

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[google_analytics]'] = TRUE;
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, $this->t('Uninstall'));
    $this->assertSession()->pageTextNotContains(\Drupal::translation()->translate('Configuration deletions'));
    $this->submitForm([], $this->t('Uninstall'));
    $this->assertSession()->pageTextContains($this->t('The selected modules have been uninstalled.'));

    // Test if the directory and all files have been removed.
    $this->assertFalse($file_system->prepareDirectory($cache_path), 'Cache directory "public://google_analytics" has been removed.');
  }

}
