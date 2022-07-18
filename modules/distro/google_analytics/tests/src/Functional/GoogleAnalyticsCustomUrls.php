<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Test custom url functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsCustomUrls extends BrowserTestBase {

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
      'administer site configuration',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if user password page urls are overridden.
   */
  public function testGoogleAnalyticsCustomUrls() {
    $base_path = base_path();
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')
      ->set('account', $ua_code)
      ->set('privacy.anonymizeip', 0)
      ->set('track.displayfeatures', 1)
      ->save();

    $this->drupalGet('user/password', ['query' => ['name' => 'foo']]);
    $this->assertSession()->responseContains('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":"' . $base_path . 'user/password"});');

    $this->drupalGet('user/password', ['query' => ['name' => 'foo@example.com']]);
    $this->assertSession()->responseContains('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":"' . $base_path . 'user/password"});');

    $this->drupalGet('user/password');
    $this->assertSession()->responseNotContains('"page_path":"' . $base_path . 'user/password"});');

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains($base_path . '403.html');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseContains($base_path . '404.html');
  }

}
