<?php

namespace Drupal\Tests\recaptcha\Functional;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test basic functionality of reCAPTCHA module.
 *
 * @group reCAPTCHA
 *
 * @dependencies captcha
 */
class ReCaptchaBasicTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * A normal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['recaptcha', 'captcha', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('captcha', 'inc');

    // Create a normal user.
    $permissions = [
      'access content',
    ];
    $this->normalUser = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions += [
      'administer CAPTCHA settings',
      'skip CAPTCHA',
      'administer permissions',
      'administer content types',
      'administer recaptcha',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Test access to the administration page.
   */
  public function testReCaptchaAdminAccess() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/captcha/recaptcha');
    $this->assertSession()->pageTextNotContains($this->t('Access denied'));
    $this->drupalLogout();
  }

  /**
   * Test the reCAPTCHA settings form.
   */
  public function testReCaptchaAdminSettingsForm() {
    $this->drupalLogin($this->adminUser);

    $site_key = $this->randomMachineName(40);
    $secret_key = $this->randomMachineName(40);

    // Check form validation.
    $edit['recaptcha_site_key'] = '';
    $edit['recaptcha_secret_key'] = '';
    $this->drupalGet('admin/config/people/captcha/recaptcha');
    $this->submitForm($edit, $this->t('Save configuration'));

    $this->assertSession()->responseContains($this->t('Site key field is required.'));
    $this->assertSession()->responseContains($this->t('Secret key field is required.'));

    // Save form with valid values.
    $edit['recaptcha_site_key'] = $site_key;
    $edit['recaptcha_secret_key'] = $secret_key;
    $edit['recaptcha_tabindex'] = 0;
    $this->drupalGet('admin/config/people/captcha/recaptcha');
    $this->submitForm($edit, $this->t('Save configuration'));
    $this->assertSession()->responseContains($this->t('The configuration options have been saved.'));

    $this->assertSession()->responseNotContains($this->t('Site key field is required.'));
    $this->assertSession()->responseNotContains($this->t('Secret key field is required.'));
    $this->assertSession()->responseNotContains($this->t('The tabindex must be an integer.'));

    $this->drupalLogout();
  }

  /**
   * Testing the protection of the user login form.
   */
  public function testReCaptchaOnLoginForm() {
    $site_key = $this->randomMachineName(40);
    $secret_key = $this->randomMachineName(40);
    $grecaptcha = '<div class="g-recaptcha" data-sitekey="' . $site_key . '" data-theme="light" data-type="image"></div>';

    // Test if login works.
    $this->drupalLogin($this->normalUser);
    $this->drupalLogout();

    $this->drupalGet('user/login');
    $this->assertSession()->responseNotContains($grecaptcha);

    // Enable 'captcha/Math' CAPTCHA on login form.
    captcha_set_form_id_setting('user_login_form', 'captcha/Math');

    $this->drupalGet('user/login');
    $this->assertSession()->responseNotContains($grecaptcha);

    // Enable 'recaptcha/reCAPTCHA' on login form.
    captcha_set_form_id_setting('user_login_form', 'recaptcha/reCAPTCHA');
    $result = captcha_get_form_id_setting('user_login_form');
    $this->assertNotNull($result, 'A configuration has been found for CAPTCHA point: user_login_form');
    $this->assertEquals($result->getCaptchaType(), 'recaptcha/reCAPTCHA', 'reCAPTCHA type has been configured for CAPTCHA point: user_login_form');

    // Check if a Math CAPTCHA is still shown on the login form. The site key
    // and security key have not yet configured for reCAPTCHA. The module need
    // to fall back to math captcha.
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains($this->t('Math question'));

    // Configure site key and security key to show reCAPTCHA and no fall back.
    $this->config('recaptcha.settings')->set('site_key', $site_key)->save();
    $this->config('recaptcha.settings')->set('secret_key', $secret_key)->save();

    // Check if there is a reCAPTCHA on the login form.
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains($grecaptcha);
    $this->assertSession()->responseContains('<script src="' . Url::fromUri('https://www.google.com/recaptcha/api.js', ['query' => ['hl' => \Drupal::service('language_manager')->getCurrentLanguage()->getId()], 'absolute' => TRUE])->toString() . '" async defer></script>');
    $this->assertSession()->responseNotContains($grecaptcha . '<noscript>');

    // Test if the fall back url is properly build and noscript code added.
    $this->config('recaptcha.settings')->set('widget.noscript', 1)->save();

    $this->drupalGet('user/login');
    $this->assertSession()->responseContains($grecaptcha . "\n" . '<noscript>');
    $options = [
      'query' => [
        'k' => $site_key,
        'hl' => \Drupal::service('language_manager')->getCurrentLanguage()->getId(),
      ],
      'absolute' => TRUE,
    ];
    $this->assertSession()->responseContains(Html::escape(Url::fromUri('https://www.google.com/recaptcha/api/fallback', $options)->toString()));

    // Check if there is a reCAPTCHA with global url on the login form.
    $this->config('recaptcha.settings')->set('use_globally', TRUE)->save();
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('<script src="' . Url::fromUri('https://www.recaptcha.net/recaptcha/api.js', ['query' => ['hl' => \Drupal::service('language_manager')->getCurrentLanguage()->getId()], 'absolute' => TRUE])->toString() . '" async defer></script>');
    $this->assertSession()->responseContains(Html::escape(Url::fromUri('https://www.recaptcha.net/recaptcha/api/fallback', $options)->toString()));

    // Check that data-size attribute does not exists.
    $this->config('recaptcha.settings')->set('widget.size', '')->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-size=:size]', [':class' => 'g-recaptcha', ':size' => 'small']);
    $this->assertEmpty($element, 'Tag contains no data-size attribute.');

    // Check that data-size attribute exists.
    $this->config('recaptcha.settings')->set('widget.size', 'small')->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-size=:size]', [':class' => 'g-recaptcha', ':size' => 'small']);
    $this->assertNotEmpty($element, 'Tag contains data-size attribute and value.');

    // Check that data-tabindex attribute does not exists.
    $this->config('recaptcha.settings')->set('widget.tabindex', 0)->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-tabindex=:index]', [':class' => 'g-recaptcha', ':index' => 0]);
    $this->assertEmpty($element, 'Tag contains no data-tabindex attribute.');

    // Check that data-tabindex attribute exists.
    $this->config('recaptcha.settings')->set('widget.tabindex', 5)->save();
    $this->drupalGet('user/login');
    $element = $this->xpath('//div[@class=:class and @data-tabindex=:index]', [':class' => 'g-recaptcha', ':index' => 5]);
    $this->assertNotEmpty($element, 'Tag contains data-tabindex attribute and value.');

    // Try to log in, which should fail.
    $edit['name'] = $this->normalUser->getAccountName();
    $edit['pass'] = $this->normalUser->getPassword();
    $this->assertSession()->responseContains('captcha_response');
    $this->assertSession()
      ->hiddenFieldExists('captcha_response')
      ->setValue('?');

    $this->drupalGet('user/login');
    $this->submitForm($edit, $this->t('Log in'));
    // Check for error message.
    $this->assertSession()->pageTextContains($this->t('The answer you entered for the CAPTCHA was not correct.'));

    // And make sure that user is not logged in: check for name and password
    // fields on "?q=user".
    $this->drupalGet('user/login');
    $this->assertSession()->fieldExists('name');
    $this->assertSession()->fieldExists('pass');
  }

}
