<?php

namespace Drupal\Tests\entity_hierarchy_breadcrumb\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_hierarchy\Traits\EntityHierarchyTestTrait;

/**
 * Defines a class for testing the reorder children form.
 *
 * @group entity_hierarchy_breadcrumb
 */
class BreadcrumbFunctionalTest extends BrowserTestBase {

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
    'entity_hierarchy_breadcrumb',
    'entity_test',
    'system',
    'user',
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
    $this->placeBlock('system_breadcrumb_block');
  }

  /**
   * Tests breadcrumb rendering.
   */
  public function testBreadcrumbs() {
    $children = $this->createChildEntities($this->parent->id());
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
    ]));
    $this->drupalGet($this->parent->toUrl());
    $this->assertElementsOrder('nav[aria-labelledby="system-breadcrumb"] li', [
      'Home',
      'Parent',
    ]);
    $first_child = reset($children);
    $this->drupalGet($first_child->toUrl());
    $this->assertElementsOrder('nav[aria-labelledby="system-breadcrumb"] li', [
      'Home',
      'Parent',
      'Child 1',
    ]);
  }

  /**
   * Assert elements are in an order.
   *
   * @param string $selector
   *   Css selector.
   * @param array $elements
   *   An array of strings you expect.
   */
  protected function assertElementsOrder($selector, array $elements) {
    $dom_nodes = $this->getSession()->getPage()->findAll('css', $selector);
    $actual = array_map(function (NodeElement $node) {
      return $node->getText();
    }, $dom_nodes);
    $this->assertEquals($elements, $actual);
  }

}
