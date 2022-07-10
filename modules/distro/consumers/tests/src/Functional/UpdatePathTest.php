<?php

namespace Drupal\Tests\consumers\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group consumers
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    // This conditional allows tests to pass both before and after 8.8.x. The
    // 8.4.0 fixtures were removed in 8.8.x.
    // https://www.drupal.org/project/consumers/issues/3115996
    // @todo: Remove this conditional after 8.7.x is no longer supported.

    if (file_exists($this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz')) {
      $this->databaseDumpFiles = [
        $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
        __DIR__ . '/../../drupal-8.4.0-consumers_installed.php',
      ];
    }
    else {
      $this->databaseDumpFiles = [
        // @todo: Remove this fixture after 8.7 is no longer supported.
        __DIR__ . '/../../drupal-8.4.0.bare.standard.php.gz',
        __DIR__ . '/../../drupal-8.4.0-consumers_installed.php',
      ];
    }
  }

  /**
   * Tests the update path from Consumers 8.x-1.0-beta1 on Drupal 8.4.0.
   */
  public function testUpdatePath() {
    $this->runUpdates();
  }

}
