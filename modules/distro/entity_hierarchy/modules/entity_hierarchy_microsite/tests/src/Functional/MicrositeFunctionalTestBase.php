<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;
use Drupal\Tests\entity_hierarchy_microsite\Traits\MediaTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Defines a base class for testing microsite.
 */
abstract class MicrositeFunctionalTestBase extends BrowserTestBase {

  use MediaTrait;
  use EntityHierarchyTestTrait;
  use ContentTypeCreationTrait;

  const ENTITY_TYPE = 'node';
  const FIELD_NAME = 'field_parents';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy_microsite',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
    $this->drupalCreateContentType(['type' => 'basic']);
    $this->setupEntityHierarchyField('node', 'basic', 'field_parents');
    $this->createMediaType('image', [
      'id' => 'image',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = Node::create(['type' => 'basic', 'status' => 1] + $values);
    return $entity;
  }

}
