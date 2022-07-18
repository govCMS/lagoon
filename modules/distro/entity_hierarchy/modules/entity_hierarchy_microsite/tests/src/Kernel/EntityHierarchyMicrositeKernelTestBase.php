<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\entity_hierarchy\Kernel\EntityHierarchyKernelTestBase;
use Drupal\Tests\entity_hierarchy_microsite\Traits\MediaTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Defines a base class for micro-site tests.
 */
abstract class EntityHierarchyMicrositeKernelTestBase extends EntityHierarchyKernelTestBase {
  use ContentTypeCreationTrait;
  use MediaTrait;

  const ENTITY_TYPE = 'node';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy_microsite',
    'node',
    'file',
    'image',
    'media',
    'entity_hierarchy_microsite_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('entity_hierarchy_microsite');
    $this->installEntitySchema('eh_microsite_menu_override');
    $this->createMediaType('image', [
      'id' => 'image',
    ]);
    $this->installConfig('entity_hierarchy_microsite');
  }

  /**
   * {@inheritdoc}
   */
  protected function setupEntityHierarchyField($entity_type_id, $bundle, $field_name) {
    $this->installConfig('node');
    $this->createContentType(['type' => 'basic']);
    parent::setupEntityHierarchyField($entity_type_id, 'basic', $field_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = Node::create(['type' => 'basic'] + $values);
    return $entity;
  }

}
