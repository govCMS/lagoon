<?php

namespace Drupal\Tests\ga_login\Functional;

use Drupal\Tests\tfa\Functional\TfaTestBase;
use Drupal\tfa\TfaDataTrait;
use Drupal\tfa\TfaLoginTrait;
use ParagonIE\ConstantTime\Encoding;

/**
 * Class GALoginTotpValidationPluginTest.
 *
 * @group GA_Login
 *
 * @ingroup GA_Login
 */
class GALoginTotpValidationPluginTest extends TfaTestBase {
  use TfaLoginTrait;
  use TfaDataTrait;

  /**
   * Non-admin user account. Standard tfa user.
   *
   * @var \Drupal\user\Entity\User
   */
  public $userAccount;

  /**
   * Validation plugin ID.
   *
   * @var string
   */
  public $validationPluginId = 'ga_login_totp';

  /**
   * Instance of the validation plugin for the $validationPluginId.
   *
   * @var \Drupal\ga_login\Plugin\TfaValidation\GALoginTotpValidation
   */
  public $validationPlugin;

  /**
   * The secret.
   *
   * @var string
   */
  public $seed;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tfa',
    'encrypt',
    'encrypt_test',
    'key',
    'ga_login',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->canEnableValidationPlugin($this->validationPluginId);

    $this->userAccount = $this->drupalCreateUser([
      'setup own tfa',
      'disable own tfa',
    ]);
    $this->validationPlugin = \Drupal::service('plugin.manager.tfa.validation')->createInstance($this->validationPluginId, ['uid' => $this->userAccount->id()]);
    $this->drupalLogin($this->userAccount);
    $this->setupUserTotp();
    $this->drupalLogout();
  }

  /**
   * Setup the user's Validation plugin.
   */
  public function setupUserTotp() {
    $edit = [
      'current_pass' => $this->userAccount->passRaw,
    ];
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa/' . $this->validationPluginId);
    $this->submitForm($edit, 'Confirm');

    // Fetch seed.
    $result = $this->xpath('//input[@name="seed"]');
    if (empty($result)) {
      $this->fail('Unable to extract seed from page. Aborting test.');
      return;
    }

    $this->seed = $result[0]->getValue();
    $this->validationPlugin->storeSeed($this->seed);
    $edit = [
      'code' => $this->validationPlugin->auth->otp->totp(Encoding::base32DecodeUpper($this->seed)),
    ];
    $this->submitForm($edit, 'Verify and save');

    $this->assertSession()->linkExists('Disable TFA');
  }

  /**
   * Test that a user can login with GALoginTotpValidation.
   */
  public function testTotpLogin() {
    $assert = $this->assertSession();
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Verification code is application generated and 6 digits long.');

    // Try invalid code.
    $edit = ['code' => 112233];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid application code. Please try again.');

    // Try a code that is 30 minutes old.
    $this->validationPlugin->auth->otp->setTotpOffset(-1800);
    $old_code = $this->validationPlugin->auth->otp->totp(Encoding::base32DecodeUpper($this->seed));
    $edit = ['code' => $old_code];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid application code. Please try again.');

    // Try valid code. We need to offset the timing on Totp so that we don't
    // generate the same code we used during setup.
    $this->validationPlugin->auth->otp->setTotpOffset($this->validationPlugin->getTimeSkew() * 30);
    $valid_code = $this->validationPlugin->auth->otp->totp(Encoding::base32DecodeUpper($this->seed));
    $edit = ['code' => $valid_code];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($this->userAccount->getDisplayName());

    // Check for replay attack.
    $this->drupalLogout();
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Verification code is application generated and 6 digits long.');

    $edit = ['code' => $valid_code];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid code, it was recently used for a login. Please try a new code.');
  }

}
