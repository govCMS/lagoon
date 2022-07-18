<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test roles functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsRolesTest extends BrowserTestBase {

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
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if roles based tracking works.
   */
  public function testGoogleAnalyticsRolesTracking() {
    $ua_code = 'UA-123456-4';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    $this->config('google_analytics.settings')->set('visibility.user_role_mode', 0)->save();
    // Enable tracking for all users.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);
    $this->drupalGet('admin');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains('/403.html');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);
    $this->drupalGet('admin');
    $this->assertSession()->responseNotContains($ua_code);

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($ua_code);

    // Add to every role except the selected ones.
    $this->config('google_analytics.settings')->set('visibility.user_role_mode', 1)->save();
    // Enable tracking for all users.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);
    $this->drupalGet('admin');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains('/403.html');

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);
    $this->drupalGet('admin');
    $this->assertSession()->responseNotContains($ua_code);

    // Disable tracking for authenticated users.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertSession()->responseNotContains($ua_code);
    $this->drupalGet('admin');
    $this->assertSession()->responseNotContains($ua_code);

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);
  }

}
