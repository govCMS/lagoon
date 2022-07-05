<?php

namespace Drupal\Tests\key\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests administration of key overrides.
 *
 * @group key
 */
class KeyOverrideAdminTest extends BrowserTestBase {

  use KeyTestTrait;

  public static $modules = ['key'];

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

    $this->overrideUser =
      $this->drupalCreateUser(['administer key configuration overrides']);

  }

  /**
   * Tests key routes for overriding key configuration.
   */
  public function testOverrideUserRoutes() {

    $this->drupalLogin($this->overrideUser);

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

    $this->routeAccessTest($basicKeyRoutes, 403);
    $this->routeAccessTest($overrideKeyRoutes, 200);
  }

}
