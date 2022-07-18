<?php

namespace Drupal\Tests\layout_builder_modal\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of layout_builder_modal module.
 *
 * @group layout_builder_modal
 */
class LayoutBuilderModalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder', 'layout_builder_modal'];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests the Layout Builder Modal settings form.
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();

    // Test access is denied for user without administer permission.
    $account = $this->drupalCreateUser([]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/layout-builder-modal');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();

    // Test access is allowed for user with administer permission.
    // Test configuration forms submits correctly.
    $account = $this->drupalCreateUser(['administer layout builder modal']);
    $this->drupalLogin($account);

    $edit = [
      'modal_width' => 800,
      'modal_height' => 500,
    ];

    $this->drupalGet('admin/config/user-interface/layout-builder-modal');
    $assert_session->statusCodeEquals(200);
    $this->submitForm($edit, 'Save configuration');

    $settings = $this->config('layout_builder_modal.settings');

    $this->assertEquals(800, $settings->get('modal_width'));
    $this->assertEquals(500, $settings->get('modal_height'));
    $this->assertEquals('default_theme', $settings->get('theme_display'));

    $edit = [
      'modal_height' => 'auto',
      'theme_display' => 'seven',
    ];

    $this->submitForm($edit, 'Save configuration');

    $settings = $this->config('layout_builder_modal.settings');

    $this->assertEquals('auto', $settings->get('modal_height'));
    $this->assertEquals('seven', $settings->get('theme_display'));

    $this->assertTrue($settings->get('modal_autoresize'));

    // Tests updating modal auto resize setting.
    $edit = [
      'modal_autoresize' => FALSE,
    ];

    $this->submitForm($edit, 'Save configuration');

    $settings = $this->config('layout_builder_modal.settings');

    $this->assertFalse($settings->get('modal_autoresize'));
  }

}
