<?php

namespace Drupal\google_analytics\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts D7 dimension and metric to D8 in a single field.
 *
 * @MigrateProcessPlugin(
 *   id = "google_analytics_parameter_pages"
 * )
 */
class GoogleAnalyticsParameterPages extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The migration process plugin.
   *
   * The migration process plugin, configured for lookups in the d6_user_role
   * and d7_user_role migrations.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $migration_configuration = [
      'migration' => [
        'd6_user_role',
        'd7_user_role',
      ],
    ];
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('plugin.manager.migrate.process')->createInstance('migration_lookup', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$dimensions, $metrics] = $value;
    $return_array = [];
    foreach ($dimensions as $dimension) {
      $index = 'dimension' . $dimension['index'];
      $return_array[$index] = [
        'type'  => 'dimension',
        'name'  => $index,
        'value'  => $dimension['value'],
      ];
    }
    foreach ($metrics as $metric) {
      $index = 'metric' . $metric['index'];
      $return_array[$index] = [
        'type'  => 'metric',
        'name'  => $index,
        'value'  => $metric['value'],
      ];
    }
    return $return_array;
  }

}
