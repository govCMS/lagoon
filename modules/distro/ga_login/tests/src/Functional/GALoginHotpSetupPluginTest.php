<?php

namespace Drupal\Tests\ga_login\Functional;

use Drupal\Tests\tfa\Functional\TfaTestBase;
use ParagonIE\ConstantTime\Encoding;

/**
 * Class GALoginHotpSetupPluginTest.
 *
 * @group GA_Login
 *
 * @ingroup GA_Login
 */
class GALoginHotpSetupPluginTest extends TfaTestBase {

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
  public $validationPluginId = 'ga_login_hotp';

  /**
   * Instance of the setup plugin for the $validationPluginId.
   *
   * @var \Drupal\ga_login\Plugin\TfaSetup\GALoginHotpSetup
   */
  public $setupPlugin;

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
    $this->setupPlugin = \Drupal::service('plugin.manager.tfa.setup')->createInstance($this->validationPluginId . '_setup', ['uid' => $this->userAccount->id()]);
    $this->drupalLogin($this->userAccount);
  }

  /**
   * Test that the overview page exists for a standard user.
   */
  public function testTfaOverviewExists() {
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->linkExists('Set up application');
  }

  /**
   * Test setting up the tfa_test_plugins_validation plugin as a generic user.
   */
  public function testPluginSetup() {
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa/' . $this->validationPluginId);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter your current password');

    // Provide the user's password to continue.
    $edit = [
      'current_pass' => $this->userAccount->passRaw,
    ];
    $this->drupalPostForm(NULL, $edit, 'Confirm');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Application verification code');

    // Fetch seed.
    $result = $this->xpath('//input[@name="seed"]');
    if (empty($result)) {
      $this->fail('Unable to extract seed from page. Aborting test.');
      return;
    }

    $seed = $result[0]->getValue();
    $this->setupPlugin->setSeed($seed);

    // Try invalid code.
    $edit = [
      'code' => substr(str_shuffle('1234567890'), 0, 6),
    ];
    $this->drupalPostForm(NULL, $edit, 'Verify and save');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid application code. Please try again.');

    // Submit valid code.
    $edit = [
      'code' => $this->setupPlugin->auth->otp->hotp(Encoding::base32DecodeUpper($seed), 1),
    ];
    $this->drupalPostForm(NULL, $edit, 'Verify and save');
    $assert->statusCodeEquals(200);

    $assert->linkExists('Disable TFA');
  }

}
