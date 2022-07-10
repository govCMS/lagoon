<?php

namespace Drupal\Tests\config_ignore\Functional;

/**
 * Test hook implementation of another module.
 *
 * @package Drupal\Tests\config_ignore\Functional
 *
 * @group config_ignore
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ConfigIgnoreHookTest extends ConfigIgnoreBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'config_ignore',
    'config',
    'config_filter',
    'config_ignore_hook_test'
  ];

  /**
   * Test hook implementation of another module.
   */
  public function testSettingsAlterHook() {

    $this->config('system.site')->set('name', 'Test import')->save();

    $this->doExport();

    $this->config('system.site')->set('name', 'Changed title')->save();

    $this->doImport();

    // Test if the `config_ignore_hook_test` module got to ignore the site name
    // config.
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

}
