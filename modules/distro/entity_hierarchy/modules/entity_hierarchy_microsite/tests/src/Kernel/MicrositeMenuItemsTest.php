<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Kernel;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;
use Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverride;

/**
 * Defines a class for testing microsite menu items.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeMenuItemsTest extends EntityHierarchyMicrositeKernelTestBase {

  /**
   * Tests the microsite menu link integration.
   */
  public function testMicrositeMenuLinkDerivation() {
    $media = $this->createImageMedia();
    $children = $this->createChildEntities($this->parent->id(), 5);
    list ($first, $second) = array_values($children);
    $first_children = $this->createChildEntities($first->id(), 5, '1.');
    $second_children = $this->createChildEntities($second->id(), 4, '2.');
    $microsite = Microsite::create([
      'name' => 'Subsite',
      'home' => $this->parent,
      'logo' => $media,
    ]);
    $microsite->save();
    // hook_entity_hierarchy_microsite_links_alter() should be fired.
    $this->assertEquals('success', \Drupal::state()->get('entity_hierarchy_microsite_test_entity_hierarchy_microsite_links_alter', NULL));
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $tree */
    $tree = \Drupal::service('menu.link_tree');
    $params = $tree->getCurrentRouteMenuTreeParameters('entity-hierarchy-microsite');
    $params->setMaxDepth(9);
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(1, $items);
    $plugin_id = 'entity_hierarchy_microsite:' . $this->parent->uuid();
    $this->assertArrayHasKey($plugin_id, $items);
    $this->assertCount(5, $items[$plugin_id]->subtree);
    foreach ($children as $entity) {
      $child_plugin_id = 'entity_hierarchy_microsite:' . $entity->uuid();
      $this->assertArrayHasKey($child_plugin_id, $items[$plugin_id]->subtree);
      if ($entity->uuid() === $first->uuid()) {
        $this->assertCount(5, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        foreach ($first_children as $child_entity) {
          $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        }
      }
      if ($entity->uuid() === $second->uuid()) {
        $this->assertCount(4, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        foreach ($second_children as $child_entity) {
          $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
        }
      }
    }
    /** @var \Drupal\node\NodeInterface $last */
    $last = array_pop($second_children);
    array_push($first_children, $last);
    $last->{self::FIELD_NAME} = $first;
    $last->save();
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $child_plugin_id = 'entity_hierarchy_microsite:' . $first->uuid();
    $this->assertCount(6, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($first_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }
    $child_plugin_id = 'entity_hierarchy_microsite:' . $second->uuid();
    $this->assertCount(3, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($second_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }

    $last = array_pop($second_children);
    // Create a new revision.
    $last->{self::FIELD_NAME} = NULL;
    $last->setNewRevision(TRUE);
    $last->save();
    $last->delete();
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(2, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($second_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }

    // Update child and make sure no items have been re-parented.
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(5, $items[$plugin_id]->subtree);
    $first->set('title', 'Updated first title')->setNewRevision();
    $first->save();
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $this->assertCount(5, $items[$plugin_id]->subtree);

    $lastChildOfSecond = end($second_children);
    $override1 = MicrositeMenuItemOverride::create([
      'target' => $lastChildOfSecond->uuid(),
      'enabled' => FALSE,
      'weight' => 1000,
      'title' => $lastChildOfSecond->label(),
      'parent' => 'entity_hierarchy_microsite:' . $second->uuid(),
    ]);
    $override1->save();
    $moved = reset($second_children);
    $override2 = MicrositeMenuItemOverride::create([
      'target' => $moved->uuid(),
      'weight' => -1000,
      'title' => 'Some other title',
      'parent' => 'entity_hierarchy_microsite:' . $first->uuid(),
    ]);
    $override2->save();
    $items = $tree->load('entity-hierarchy-microsite', $params);
    $child_plugin_id = 'entity_hierarchy_microsite:' . $first->uuid();
    $this->assertCount(7, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    foreach ($first_children as $child_entity) {
      $this->assertArrayHasKey('entity_hierarchy_microsite:' . $child_entity->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    }
    $this->assertArrayHasKey('entity_hierarchy_microsite:' . $moved->uuid(), $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    $this->assertEquals('Some other title', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $moved->uuid()]->link->getTitle());
    $this->assertEquals('-1000', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $moved->uuid()]->link->getWeight());
    $child_plugin_id = 'entity_hierarchy_microsite:' . $second->uuid();
    $this->assertCount(1, $items[$plugin_id]->subtree[$child_plugin_id]->subtree);
    $this->assertFalse((bool) $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $lastChildOfSecond->uuid()]->link->isEnabled());
    $this->assertEquals('some-data', $items[$plugin_id]->subtree[$child_plugin_id]->subtree['entity_hierarchy_microsite:' . $lastChildOfSecond->uuid()]->link->getUrlObject()->getOption('attributes')['data-some-data']);
  }

  /**
   * Tests microsite menus do not exceed the maximum depth.
   */
  public function testMicrositeMenuLinkMaxDepth() {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree */
    $menu_link_tree = \Drupal::service('menu.link_tree');
    $menu_max_depth = $menu_link_tree->maxDepth();
    $entity_max_depth = $menu_max_depth + 1;

    $media = $this->createImageMedia();
    $parent_id = $this->parent->id();
    for ($i=1; $i<=$entity_max_depth; $i++) {
      $child = $this->createTestEntity($parent_id, 1, "{$i}.");
      $parent_id = $child->id();
    }
    $microsite = Microsite::create([
      'name' => 'Subsite',
      'home' => $this->parent,
      'logo' => $media,
    ]);
    $microsite->save();

    // menu depth should not exceed the maximum supported depth
    $plugin_id = 'entity_hierarchy_microsite:' . $this->parent->uuid();
    $this->assertEquals($menu_max_depth, $menu_link_tree->getSubtreeHeight($plugin_id));

    // microsite should still have descendants beyond the maximum supported depth
    $descendants = $this->treeStorage->findDescendants($this->parentStub);
    $this->assertEquals($entity_max_depth, end($descendants)->getDepth());
  }

}
