<?php

namespace Drupal\Tests\entity_hierarchy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Defines a class for testing manage display with EH field.
 *
 * @group entity_hierarchy
 */
class ManageDisplayTest extends BrowserTestBase {

  use EntityHierarchyTestTrait;
  use BlockCreationTrait;

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
    'field_ui',
    'dbal',
    'block',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityHierarchyField(static::ENTITY_TYPE, static::ENTITY_TYPE, static::FIELD_NAME);
    $this->additionalSetup();
    $this->placeBlock('system_messages_block');
  }

  /**
   * Tests manage display.
   */
  public function testManageDisplay() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test fields',
      'administer entity_test display',
      'administer entity_test content',
    ]));

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('entity_test', 'entity_test');
    $display->setComponent('parents', [
      'type' => 'entity_reference_hierarchy_label',
    ]);
    $display->save();
    $this->drupalGet(Url::fromRoute('entity.entity_view_display.entity_test.default', [
      'bundle' => 'entity_test',
    ]));
  }

}
