<?php

namespace Drupal\Tests\key\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests administration of keys.
 *
 * @group key
 */
class KeyAdminTest extends BrowserTestBase {

  use KeyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['key'];

  /**
   * A user with the 'administer keys' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer keys']);

  }

  /**
   * Tests the key list builder.
   */
  public function testKeyListBuilder() {
    $this->drupalLogin($this->adminUser);
    $assert_session = $this->assertSession();
    // Go to the Key list page.
    $this->drupalGet('admin/config/system/keys');
    $assert_session->statusCodeEquals(200);

    // Verify that the "no keys" message displays.
    $assert_session->responseContains(
      new FormattableMarkup('No keys are available. <a href=":link">Add a key</a>.', [
        ':link' => Url::fromRoute('entity.key.add_form')->toString(),
      ]));

    // Add a key.
    $this->drupalGet('admin/config/system/keys/add');

    $edit = [
      'id' => 'testing_key',
      'label' => 'Testing Key',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Go to the Key list page.
    $this->drupalGet('admin/config/system/keys');
    $assert_session->statusCodeEquals(200);

    // Verify that the "no keys" message does not display.
    $assert_session->pageTextNotContains('No keys are available.');
  }

  /**
   * Tests key routes for an authorized user.
   */
  public function testAdminUserRoutes() {
    $this->createTestKey('key_foo');
    $this->createTestKeyConfigOverride('test_override', 'key_foo');

    $this->drupalLogin($this->adminUser);

    $basicKeyRoutes = [
      'entity.key.collection' => [],
      'entity.key.add_form' => [],
      'entity.key.edit_form' => ['key' => 'key_foo'],
      'entity.key.delete_form' => ['key' => 'key_foo'],
    ];

    $overrideKeyRoutes = [
      'entity.key_config_override.collection' => [],
      'entity.key_config_override.add_form' => [],
      'entity.key_config_override.delete_form' => ['key_config_override' => 'test_override'],
    ];

    $this->routeAccessTest($basicKeyRoutes, 200);
    $this->routeAccessTest($overrideKeyRoutes, 403);
  }

}
