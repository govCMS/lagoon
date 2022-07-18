<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Kernel;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;

/**
 * Defines a class for testing the child of microsite condition plugin.
 *
 * @group entity_hierarchy_microsite
 */
class ChildOfMicrositeConditionTest extends EntityHierarchyMicrositeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function testCondition() {
    $children = $this->createChildEntities($this->parent->id(), 1);
    $child = reset($children);
    $grandchildren = $this->createChildEntities($child->id(), 2);
    $microsite = Microsite::create([
      'name' => $child->label(),
      'home' => $child,
    ]);
    $microsite->save();
    $standalone = $this->createChildEntities(NULL, 1);
    $standalone = reset($standalone);
    $microsite2 = Microsite::create([
      'name' => $standalone->label(),
      'home' => $standalone,
    ]);
    $microsite2->save();
    /** @var \Drupal\Core\Condition\ConditionInterface|\Drupal\Core\Plugin\ContextAwarePluginInterface $condition */
    $condition = $this->container->get('plugin.manager.condition')->createInstance('entity_hierarchy_microsite_child');
    $this->assertTrue($condition->evaluate());
    $condition->setConfiguration([
      'field' => self::FIELD_NAME,
    ]);
    $condition->setContextValue('node', $this->parent);
    $this->assertFalse($condition->evaluate());
    $condition->setContextValue('node', $child);
    $this->assertTrue($condition->evaluate());
    $condition->setContextValue('node', reset($grandchildren));
    $this->assertTrue($condition->evaluate());
    $condition->setContextValue('node', $standalone);
    $this->assertTrue($condition->evaluate());
    $condition->setContextValue('node', NULL);
    // There is no microsite if there is no active node.
    $this->assertFalse($condition->evaluate());
  }

}
