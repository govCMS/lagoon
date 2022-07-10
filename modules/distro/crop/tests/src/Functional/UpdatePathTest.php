<?php

namespace Drupal\Tests\crop\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group crop
 * @group legacy
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->root . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../fixtures/crop-1.0-alpha2-installed.php',
    ];
  }

  public function testUpdatePath() {
    $this->runUpdates();
    $this->assertTrue(\Drupal::database()->schema()->indexExists('crop_field_data', 'crop__uri_type'));
  }

}
