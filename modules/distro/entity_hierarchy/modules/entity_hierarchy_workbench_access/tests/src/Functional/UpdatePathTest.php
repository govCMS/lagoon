<?php

namespace Drupal\Tests\entity_hierarchy_workbench_access\Functional;

use Composer\Semver\Semver;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Defines a class for testing the update path to scheme based access.
 *
 * @group entity_hierarchy_workbench_access
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * Set database dump files to be used.
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/eh_wba-update-path-test.php.gz',
    ];
  }

  /**
   * Tests entity_hierarchy_workbench_access_workbench_access_scheme_update_alter.
   */
  public function testUpdatePath() {
    if (Semver::satisfies(\Drupal::VERSION, '~9')) {
      $this->markTestSkipped('This test is only for Drupal 8');
    }
    $expected_fields = \Drupal::config('workbench_access.settings')->get('parents');
    $expected_bundles = array_keys(\Drupal::config('workbench_access.settings')->get('fields')['node']);
    $this->runUpdates();

    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
    $scheme = $this->container->get('entity_type.manager')->getStorage('access_scheme')->load('default');
    $config = $scheme->getAccessScheme()->getConfiguration();
    $this->assertEquals($expected_fields, $config['boolean_fields']);
    $this->assertEquals($expected_bundles, $config['bundles']);
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($scheme->toUrl('edit-form'));
    $assert = $this->assertSession();
    $assert->fieldExists('scheme_settings[bundles][page]');
    $assert->checkboxChecked('scheme_settings[bundles][page]');
  }

}
