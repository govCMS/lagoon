<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\entity_hierarchy\Plugin\Field\FieldWidget\EntityReferenceHierarchyAutocomplete;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Defines a class for testing the ability to hide the weight field.
 *
 * @group entity_hierarchy
 */
class HideWeightFieldFunctionalTest extends BrowserTestBase {

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
    $this->setupEntityFormDisplay(self::ENTITY_TYPE, self::ENTITY_TYPE, self::FIELD_NAME);
    $this->getEntityFormDisplay(self::ENTITY_TYPE, self::ENTITY_TYPE, 'default')
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_hierarchy_autocomplete',
        'weight' => 20,
        'settings' => ['hide_weight' => FALSE] + EntityReferenceHierarchyAutocomplete::defaultSettings(),
      ])
      ->save();
  }

  /**
   * Tests ordered storage in nested set tables.
   */
  public function testReordering() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/entity_test/add');
    $assert = $this->assertSession();
    $assert->fieldExists('parents[0][weight]');
    // Change the field to hide the weight field.
    $field = FieldConfig::load("entity_test.entity_test.parents");
    $this->getEntityFormDisplay(self::ENTITY_TYPE, self::ENTITY_TYPE, 'default')
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_hierarchy_autocomplete',
        'weight' => 20,
        'settings' => ['hide_weight' => TRUE] + EntityReferenceHierarchyAutocomplete::defaultSettings(),
      ])
      ->save();
    $field->setSetting('hide_weight', TRUE);
    $field->save();
    $this->drupalGet('/entity_test/add');
    $assert->fieldNotExists('parents[0][weight]');
    // Submit the form.
    $name = $this->randomMachineName();
    $this->submitForm([
      'parents[0][target_id][target_id]' => sprintf('%s (%s)', $this->parent->label(), $this->parent->id()),
      'name[0][value]' => $name,
    ], 'Save');
    $saved = $this->container->get('entity_type.manager')->getStorage('entity_test')->loadByProperties(['name' => $name]);
    $this->assertCount(1, $saved);
  }

  /**
   * Tests weight element can be hidden on Select widget.
   */
  public function testHideWeightSelectWidget() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/entity_test/add');
    $assert = $this->assertSession();
    $assert->fieldExists('parents[0][weight]');
    // Change the field to hide the weight field.
    $field = FieldConfig::load("entity_test.entity_test.parents");
    $this->getEntityFormDisplay(self::ENTITY_TYPE, self::ENTITY_TYPE, 'default')
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_hierarchy_select',
        'weight' => 20,
        'settings' => ['hide_weight' => TRUE] + EntityReferenceHierarchyAutocomplete::defaultSettings(),
      ])
      ->save();
    $field->setSetting('hide_weight', TRUE);
    $field->save();
    $this->drupalGet('/entity_test/add');
    $assert->fieldNotExists('parents[0][weight]');
    // Submit the form.
    $name = $this->randomMachineName();
    $this->submitForm([
      'parents[0][target_id]' => $this->parent->id(),
      'name[0][value]' => $name,
    ], 'Save');
    $saved = $this->container->get('entity_type.manager')->getStorage('entity_test')->loadByProperties(['name' => $name]);
    $this->assertCount(1, $saved);
  }

}
