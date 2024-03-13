<?php

namespace Drupal\Tests\panelizer\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the updating of Layout IDs.
 *
 * @group panelizer
 */
class PanelizerLayoutIDUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.8.panelizer.minimal.php.gz',
    ];
  }

  /**
   * Test updates.
   */
  public function testUpdate() {
    $this->runUpdates();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->clickLink('Edit', 1);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('node/2');
    $this->assertSession()->statusCodeEquals(200);
  }

}
