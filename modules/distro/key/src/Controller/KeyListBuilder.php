<?php

namespace Drupal\key\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of keys.
 *
 * @see \Drupal\key\Entity\Key
 */
class KeyListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Overrides.
   *
   * @var array
   */
  protected $overrides;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Key');
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['provider'] = [
      'data' => $this->t('Provider'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['overrides'] = [
      'data' => $this->t('Overrides'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $key \Drupal\key\Entity\Key */
    $key = $entity;

    $row['label'] = $key->label();
    $row['type'] = $key->getKeyType()->getPluginDefinition()['label'];
    $row['provider'] = $key->getKeyProvider()->getPluginDefinition()['label'];

    $overrides = $this->getOverridesByKeyId($key->id());
    $row['overrides']['data'] = [
      '#theme' => 'item_list',
      '#items' => $overrides,
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    /* @var $key \Drupal\key\Entity\Key */
    $key = $entity;

    $operations = parent::getOperations($key);

    $key_collection = Url::fromRoute('entity.key.collection')->toString();
    $operations['add_override'] = [
      'title' => $this->t('Add Config Override'),
      'weight' => 50,
      'url' => Url::fromRoute(
        'entity.key_config_override.add_form',
        [],
        ['query' => ['destination' => $key_collection, 'key' => $key->id()]]
      ),
    ];
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No keys are available. <a href=":link">Add a key</a>.', [':link' => Url::fromRoute('entity.key.add_form')->toString()]);
    return $build;
  }

  /**
   * Get any overrides associated with a key.
   *
   * @param string $key_id
   *   The ID of the key.
   *
   * @return array
   *   The overrides associated with a key.
   */
  protected function getOverridesByKeyId($key_id) {
    if (!$this->overrides) {
      $entities = $this->entityTypeManager
        ->getStorage('key_config_override')
        ->loadMultiple();

      foreach ($entities as $entity) {
        // Build the complete configuration ID.
        $config_id = '';
        $config_type = $entity->getConfigType();
        if ($config_type != 'system.simple') {
          $definition = $this->entityTypeManager->getDefinition($config_type);
          $config_id .= $definition->getConfigPrefix() . '.';
        }
        $config_id .= $entity->getConfigName();
        $config_id .= ':' . $entity->getConfigItem();

        $this->overrides[$entity->getKeyId()][] = $config_id;
      }
    }

    return isset($this->overrides[$key_id]) ? $this->overrides[$key_id] : [];
  }

}
