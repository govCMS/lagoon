<?php

namespace Drupal\Tests\key\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests access for non-admin users.
 *
 * @group key
 */
class KeyNonAdminAccessTest extends BrowserTestBase {

  use KeyTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['key'];

  /**
   * A non-admin authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createTestKey('key_foo');
    $this->createTestKeyConfigOverride('test_override', 'key_foo');

    $this->authenticatedUser = $this->drupalCreateUser();
  }

  /**
   * Tests key routes for an unauthorized user.
   */
  public function testNonAdminUserAccess() {
    $keyRoutes = [
      'entity.key.collection' => [],
      'entity.key.add_form' => [],
      'entity.key.edit_form' => ['key' => 'key_foo'],
      'entity.key.delete_form' => ['key' => 'key_foo'],
      'entity.key_config_override.collection' => [],
      'entity.key_config_override.add_form' => [],
      'entity.key_config_override.delete_form' => ['key_config_override' => 'test_override'],
    ];
    $this->routeAccessTest($keyRoutes, 403);

    $this->drupalLogin($this->authenticatedUser);
    $this->routeAccessTest($keyRoutes, 403);
  }

}
