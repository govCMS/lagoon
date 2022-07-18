<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test user fields functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsUserFieldsTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'field_ui'];

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
      'administer user form display',
      'opt-in or out of google analytics tracking',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if "allow users to customize tracking on their account page" works.
   */
  public function testGoogleAnalyticsUserFields() {
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Check if the pseudo field is shown on account forms.
    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($this->t('Google Analytics settings'));

    // No customization allowed.
    $this->config('google_analytics.settings')->set('visibility.user_account_mode', 0)->save();
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($this->t('Google Analytics settings'));

    // Tracking on by default, users with opt-in or out of tracking permission
    // can opt out.
    $this->config('google_analytics.settings')->set('visibility.user_account_mode', 1)->save();
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($this->t('Users are tracked by default, but you are able to opt out.'));

    // Tracking off by default, users with opt-in or out of tracking permission
    // can opt in.
    $this->config('google_analytics.settings')->set('visibility.user_account_mode', 2)->save();
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($this->t('Users are <em>not</em> tracked by default, but you are able to opt in.'));
  }

}
