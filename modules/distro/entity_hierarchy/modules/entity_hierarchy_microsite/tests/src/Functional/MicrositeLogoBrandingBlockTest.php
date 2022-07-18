<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Functional;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;

/**
 * Defines a class for testing microsite logo plugin.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeLogoBrandingBlockTest extends MicrositeFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('entity_hierarchy_microsite_branding', [
      'field' => self::FIELD_NAME,
      'id' => 'microsite_branding',
      'context_mapping' => [
        'node' => '@node.node_route_context:node',
      ],
      'region' => 'content',
      'visibility' => [
        'entity_hierarchy_microsite_child' => [
          'id' => 'entity_hierarchy_microsite_child',
          'field' => self::FIELD_NAME,
          'negate' => FALSE,
          'context_mapping' => [
            'node' => '@node.node_route_context:node',
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests branding block.
   */
  public function testBrandingBlock() {
    $assert = $this->assertSession();
    $logo = $this->createImageMedia();
    $root = $this->createTestEntity(NULL, 'Root');
    $children = $this->createChildEntities($root->id(), 1);
    $child = reset($children);
    $microsite = Microsite::create([
      'name' => $child->label(),
      'home' => $child,
      'logo' => $logo,
    ]);
    $microsite->save();
    $this->drupalGet($child->toUrl());
    $element = $assert->elementExists('css', 'a[rel=home]');
    $this->assertStringContainsString($child->label(), $element->getAttribute('title'));
    $this->drupalGet($root->toUrl());
    $assert->elementNotExists('css', 'a[rel=home]');
  }

}
