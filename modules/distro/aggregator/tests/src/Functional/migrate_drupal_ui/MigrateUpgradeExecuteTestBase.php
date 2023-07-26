<?php

namespace Drupal\Tests\aggregator\Functional\migrate_drupal_ui;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeExecuteTestBase as CoreUpgradeTestBase;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Provides a base class for testing a complete upgrade via the UI.
 */
abstract class MigrateUpgradeExecuteTestBase extends CoreUpgradeTestBase {

  /**
   * Executes an upgrade.
   */
  public function testUpgrade() {
    // Start the upgrade process.
    $this->submitCredentialForm();
    $session = $this->assertSession();

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Test the review form.
    $this->assertReviewForm();

    $this->useTestMailCollector();
    $this->submitForm([], 'Perform upgrade');
    $this->assertUpgrade($this->getEntityCounts());
  }

  /**
   * Helper to assert content on the Review form.
   *
   * @param array|null $available_paths
   *   An array of modules that will be upgraded. Defaults to
   *   $this->getAvailablePaths().
   * @param array|null $missing_paths
   *   An array of modules that will not be upgraded. Defaults to
   *   $this->getMissingPaths().
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertReviewForm(array $available_paths = NULL, array $missing_paths = NULL) {
    $session = $this->assertSession();
    $session->pageTextContains('What will be upgraded?');

    $available_paths = $available_paths ?? $this->getAvailablePaths();
    $missing_paths = $missing_paths ?? $this->getMissingPaths();
    // Test the available migration paths.
    foreach ($available_paths as $available) {
      $session->elementExists('xpath', "//td[contains(@class, 'checked') and text() = '$available']");
      $session->elementNotExists('xpath', "//td[contains(@class, 'error') and text() = '$available']");
    }

    // Test the missing migration paths.
    foreach ($missing_paths as $missing) {
      $session->elementExists('xpath', "//td[contains(@class, 'error') and text() = '$missing']");
      $session->elementNotExists('xpath', "//td[contains(@class, 'checked') and text() = '$missing']");
    }

  }

  /**
   * Asserts the upgrade completed successfully.
   *
   * @param array $entity_counts
   *   An array of entity count, where the key is the entity type and the value
   *   is the number of the entities that should exist post migration.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertUpgrade(array $entity_counts) {
    $session = $this->assertSession();
    $session->pageTextContains(t('Congratulations, you upgraded Drupal!'));

    // Assert the count of entities after the upgrade. First, reset all the
    // statics after migration to ensure entities are loadable.
    $this->resetAll();

    // Assert the correct number of entities exists.
    $actual_entity_counts = [];
    foreach (array_keys($entity_counts) as $entity_type) {
      $actual_entity_counts[$entity_type] = (int) \Drupal::entityQuery($entity_type)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }
    $this->assertSame($entity_counts, $actual_entity_counts);

    $plugin_manager = \Drupal::service('plugin.manager.migration');
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);
    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        $this->assertNotSame(MigrateIdMapInterface::STATUS_FAILED, $row['source_row_status'], $message);
        $this->assertNotSame(MigrateIdMapInterface::STATUS_NEEDS_UPDATE, $row['source_row_status'], $message);
      }
    }
  }

}
