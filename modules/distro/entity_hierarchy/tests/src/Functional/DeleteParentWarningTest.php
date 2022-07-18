<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Defines a class for testing the warnings on delete form.
 *
 * @group entity_hierarchy
 */
class DeleteParentWarningTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;

  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'entity_test',
    'system',
    'user',
    'dbal',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, static::ENTITY_TYPE, static::FIELD_NAME);
    $this->additionalSetup();
  }

  /**
   * Tests delete warning.
   */
  public function testDeleteWarning() {
    $entities = $this->createChildEntities($this->parent->id());
    $first_child = reset($entities);
    $grandchildren = $this->createChildEntities($first_child->id(), 3);
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet($this->parent->toUrl('delete-form'));
    $assert = $this->assertSession();
    $assert->pageTextContains('This Test entity has 5 children, deleting this item will move those items to the root of the hierarchy.');
    foreach ($entities as $entity) {
      $assert->pageTextContains($entity->label());
    }
    // Now test one with a grandparent.
    $this->drupalGet($first_child->toUrl('delete-form'));
    $assert = $this->assertSession();
    $assert->pageTextContains(sprintf('This Test entity has 3 children, deleting this item will change their parent to be %s.', $this->parent->label()));
    foreach ($grandchildren as $entity) {
      $assert->pageTextContains($entity->label());
    }
  }

}
