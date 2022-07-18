<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\entity_hierarchy\Plugin\Field\FieldWidget\EntityReferenceHierarchyAutocomplete;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Defines a class for testing the warnings on edit form.
 *
 * @group entity_hierarchy
 */
class ForwardRevisionNodeValidationTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use ContentModerationTestTrait;

  const FIELD_NAME = 'parents';
  const ENTITY_TYPE = 'node';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy',
    'system',
    'user',
    'dbal',
    'field',
    'node',
    'filter',
    'options',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $content_type = $this->drupalCreateContentType([
      'type' => 'article',
    ]);
    $content_type->save();

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, static::ENTITY_TYPE, 'article');

    $this->setupEntityHierarchyField(static::ENTITY_TYPE, 'article', static::FIELD_NAME);
    $this->getEntityFormDisplay(static::ENTITY_TYPE, 'article', 'default')
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_hierarchy_autocomplete',
        'weight' => 20,
        'settings' => ['hide_weight' => TRUE] + EntityReferenceHierarchyAutocomplete::defaultSettings(),
      ])
      ->save();
    $this->additionalSetup();
  }

  /**
   * Tests validation warning.
   */
  public function testValidationWarning() {
    $entities = $this->createChildEntities($this->parent->id());
    $first_child = reset($entities);
    $this->drupalLogin($this->drupalCreateUser(array_keys($this->container->get('user.permissions')
      ->getPermissions()), NULL, TRUE));
    $this->drupalGet($this->parent->toUrl('edit-form'));
    // Try to submit form with child as parent.
    $this->submitForm([
      sprintf('%s[0][target_id][target_id]', static::FIELD_NAME) => sprintf('%s (%s)', $first_child->label(), $first_child->id()),
    ], 'Save');
    $assert = $this->assertSession();
    $assert->pageTextContains(sprintf('This entity (node: %s) cannot be referenced as it is either a child or the same entity.', $first_child->id()));
  }

  /**
   * {@inheritdoc}
   */
  protected function createTestEntity($parentId, $label = 'Child 1', $weight = 0) {
    $values = [
      'type' => 'article',
      'title' => $label,
      'moderation_state' => 'published',
      'status' => 1,
    ];
    if ($parentId) {
      $values[static::FIELD_NAME] = [
        'target_id' => $parentId,
        'weight' => $weight,
      ];
    }
    $entity = $this->doCreateTestEntity($values);
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreateTestEntity(array $values) {
    $entity = Node::create($values);
    return $entity;
  }

}
