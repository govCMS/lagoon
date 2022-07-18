<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines a class for testing views integration with revisionable entities.
 *
 * @group entity_reference
 */
class ViewsRevisionableIntegrationTest extends ViewsIntegrationTest {

  /**
   * {@inheritdoc}
   */
  const ENTITY_TYPE = 'entity_test_rev';

  /**
   * Module containing the test views.
   *
   * @var string
   */
  protected $testViewModule = 'entity_hierarchy_test_views_revision';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy_test_views_revision',
  ];

  /**
   * {@inheritdoc}
   */
  protected function additionalSetup() {
    // The entity_test_rev entity type uses the entity_test schema.
    $this->installEntitySchema('entity_test');
    parent::additionalSetup();
  }

  /**
   * Creates the test entity.
   *
   * @param array $values
   *   Entity values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created entity.
   */
  protected function doCreateTestEntity(array $values) {
    // We use a different entity type here.
    $entity = EntityTestRev::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity($parentId, $label = 'Child 1', $weight = 0, $withRevision = TRUE) {
    $entity = parent::createTestEntity($parentId, $label, $weight);
    if ($withRevision) {
      // Save it twice so we end up with another revision.
      $entity->setNewRevision(TRUE);
      $entity->save();
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateChildTestEntity($parentId, $label, $weight) {
    // Don't want revisions here so pass FALSE for last argument.
    return $this->createTestEntity($parentId, $label, $weight, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getArgumentFromEntity(ContentEntityInterface $entity): int {
    return $entity->getRevisionId();
  }

}
