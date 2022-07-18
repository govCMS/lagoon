<?php

namespace Drupal\inline_entity_form;

use Drupal\Component\Utility\NestedArray;
use Drupal\field\Plugin\migrate\source\d7\FieldInstance;
use Drupal\field\Plugin\migrate\source\d7\FieldInstancePerFormDisplay;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Helper for migration hooks in inline_entity_form.module.
 */
class MigrationHelper {

  /**
   * Alters the field migrations for the inline_entity_form widget.
   *
   * @param array $migrations
   *   An array of migrations.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function alterPlugins(array &$migrations) {
    foreach ($migrations as &$migration) {
      /** @var \Drupal\migrate\Plugin\MigrateSourcePluginManager $source_plugin_manager */
      $source_plugin_manager = \Drupal::service('plugin.manager.migrate.source');
      $source = NULL;
      if (isset($migration['source']['plugin'])) {
        $source = $source_plugin_manager->getDefinition($migration['source']['plugin']);
      }
      if (isset($source['class'])) {
        // Field instance.
        if ($source['class'] === FieldInstance::class) {
          $settings[] = $migration['process']['settings'];
          $addition = [
            'inline_entity_form' => [
              'plugin' => 'inline_entity_form_field_instance_settings',
            ],
          ];
          $settings = NestedArray::mergeDeepArray([$settings, $addition], TRUE);
          $migration['process']['settings'] = $settings;
        }
        if (is_a($source['class'], FieldInstancePerFormDisplay::class, TRUE)) {
          $addition = [
            'inline_entity_form_single' => 'inline_entity_form_simple',
            'inline_entity_form' => 'inline_entity_form_complex',
          ];
          $migration['process']['options/type']['type']['map'] = array_merge($migration['process']['options/type']['type']['map'], $addition);
        }
      }
    }
  }

  /**
   * Adds all bundles for the entity type to the row.
   *
   * Drupal 7 inline_entity_form set the target bundles to an empty array to
   * indicate all bundles are referenced. In Drupal 8+ all the target bundles
   * are listed in the handler settings. Therefore, when the target bundle is an
   * empty array  get all the bundles and put them on the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source for this migration.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   *
   * @throws \Exception
   */
  public function alterRow(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    if ($source) {
      if (get_class($source) === FieldInstance::class && $row->get('type') === 'entityreference') {
        $widget = $row->get('widget/type');
        if ($widget === 'inline_entity_form_single' || $widget === 'inline_entity_form') {
          $data = $row->get('field_definition/data');
          $definition = unserialize($data);
          if (empty($definition['settings']['handler_settings']['target_bundles'])) {
            $entity_type = $row->get('entity_type');
            $bundles = $this->getBundles($source, $entity_type);
          }
        }
      }
      $bundles = $bundles ?? [];
      $row->setSourceProperty('target_bundles', $bundles);
    }
  }

  /**
   * Helper to get the bundles for an entity type.
   *
   * This currently only works for nodes. To add other entity types add a new
   * case to the switch statement below and either use a query to get the
   * bundles or hard code the values for you source site.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source_plugin
   *   The source plugin.
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The product types.
   */
  protected function getBundles(MigrateSourceInterface $source_plugin, $entity_type) {
    $bundles = NULL;
    $connection = NULL;

    try {
      $connection = $source_plugin->getDatabase();
    }
    catch (RequirementsException $e) {
    }

    if ($connection) {
      switch ($entity_type) {
        case 'node':
          if ($connection->schema()->tableExists('node_type')) {
            $query = $connection->select('node_type', 't')->fields('t');
            $bundles = $query->execute()->fetchCol();
          }
          break;

        default:
          break;
      }
    }
    return $bundles;
  }

}
