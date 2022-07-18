<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\entity_hierarchy\Information\ParentCandidateInterface;
use Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface;
use Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface;
use Drupal\entity_hierarchy_microsite\Form\MicrositeMenuItemForm;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for entity hooks for the module.
 *
 * @todo revisit a lot of this when
 *   https://www.drupal.org/project/drupal/issues/3001284 is in as that would
 *   allow us to specify cache tags which would trigger refreshing the
 *   derivatives automatically.
 *
 * @internal
 */
class EntityHooks implements ContainerInjectionInterface {

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Parent candidate.
   *
   * @var \Drupal\entity_hierarchy\Information\ParentCandidateInterface
   */
  protected $parentCandidate;

  /**
   * Microsite lookup.
   *
   * @var \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface
   */
  protected $childOfMicrositeLookup;

  /**
   * Discovery.
   *
   * @var \Drupal\entity_hierarchy_microsite\MicrositeMenuLinkDiscoveryInterface
   */
  protected $menuLinkDiscovery;

  /**
   * Constructs a new EntityHooks.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   Menu link manager.
   * @param \Drupal\entity_hierarchy\Information\ParentCandidateInterface $parentCandidate
   *   Parent candidate.
   * @param \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface $childOfMicrositeLookup
   *   Microsite lookup.
   * @param \Drupal\entity_hierarchy_microsite\MicrositeMenuLinkDiscoveryInterface $menuLinkDiscovery
   *   Discovery.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   Menu link tree.
   */
  public function __construct(MenuLinkManagerInterface $menuLinkManager, ParentCandidateInterface $parentCandidate, ChildOfMicrositeLookupInterface $childOfMicrositeLookup, MicrositeMenuLinkDiscoveryInterface $menuLinkDiscovery, MenuLinkTreeInterface $menuLinkTree) {
    $this->menuLinkTree = $menuLinkTree;
    $this->menuLinkManager = $menuLinkManager;
    $this->parentCandidate = $parentCandidate;
    $this->childOfMicrositeLookup = $childOfMicrositeLookup;
    $this->menuLinkDiscovery = $menuLinkDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('entity_hierarchy.information.parent_candidate'),
      $container->get('entity_hierarchy_microsite.microsite_lookup'),
      $container->get('entity_hierarchy_microsite.menu_link_discovery'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * React to node insert.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   */
  public function onNodeInsert(NodeInterface $node) {
    foreach ($this->parentCandidate->getCandidateFields($node) as $field) {
      foreach ($this->getMicrositesForNodeAndField($node, $field) as $microsite) {
        $this->updateMenuForMicrosite($microsite);
      }
    }
  }

  /**
   * Gets the possible microsites for a particular field and node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   * @param string $field
   *   Field name.
   *
   * @return \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface[]
   *   Microsites the node belongs to with the given field.
   */
  protected function getMicrositesForNodeAndField(NodeInterface $node, string $field) : array {
    if (!$node->hasField($field)) {
      return [];
    }
    if ($node->get($field)->isEmpty()) {
      return [];
    }
    return $this->childOfMicrositeLookup->findMicrositesForNodeAndField($node, $field);
  }

  /**
   * React to node update.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node update.
   */
  public function onNodeUpdate(NodeInterface $node) {
    $original = $node->original;
    foreach ($this->parentCandidate->getCandidateFields($node) as $field) {
      if ($node->hasField($field) && ((!$node->get($field)->isEmpty() || !$original->get($field)->isEmpty()) ||
        ($node->{$field}->target_id !== $original->{$field}->target_id ||
        $node->{$field}->weight !== $original->{$field}->weight))) {
        if ($microsites = $this->childOfMicrositeLookup->findMicrositesForNodeAndField($node, $field)) {
          foreach ($microsites as $microsite) {
            $this->updateMenuForMicrosite($microsite);
          }
          continue;
        }
        if ($microsites = $this->childOfMicrositeLookup->findMicrositesForNodeAndField($original, $field)) {
          // This item is no longer in the microsite, so we need to trigger
          // its removal.
          $plugin_id = 'entity_hierarchy_microsite:' . $original->uuid();
          if ($this->menuLinkManager->hasDefinition($plugin_id)) {
            $this->menuLinkManager->removeDefinition($plugin_id);
          }
        }
      }
    }
  }

  /**
   * React to node delete.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node update.
   */
  public function onNodeDelete(NodeInterface $node) {
    foreach ($this->parentCandidate->getCandidateFields($node) as $field) {
      if ($this->getMicrositesForNodeAndField($node, $field)) {
        $plugin_id = 'entity_hierarchy_microsite:' . $node->uuid();
        if ($this->menuLinkManager->hasDefinition($plugin_id)) {
          $this->menuLinkManager->removeDefinition($plugin_id);
          continue;
        }
      }
    }
  }

  /**
   * React to microsite being saved.
   *
   * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface $microsite
   *   Microsite.
   * @param bool $isUpdate
   *   TRUE if is an update.
   */
  public function onMicrositePostSave(MicrositeInterface $microsite, $isUpdate) {
    $this->updateMenuForMicrosite($microsite);
  }

  /**
   * Updates menu for the microsite.
   *
   * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface $microsite
   *   Microsite.
   */
  protected function updateMenuForMicrosite(MicrositeInterface $microsite) {
    $menu_max_depth = $this->menuLinkTree->maxDepth();
    foreach ($this->menuLinkDiscovery->getMenuLinkDefinitions($microsite) as $uuid => $definition) {
      $plugin_id = 'entity_hierarchy_microsite:' . $uuid;
      if ($this->menuLinkManager->hasDefinition($plugin_id)) {
        if ($definition['metadata']['entity_hierarchy_depth'] < $menu_max_depth) {
          $this->menuLinkManager->updateDefinition($plugin_id, $definition, FALSE);
          continue;
        }
        $this->menuLinkManager->removeDefinition($plugin_id);
        continue;
      }
      if ($definition['metadata']['entity_hierarchy_depth'] < $menu_max_depth) {
        $this->menuLinkManager->addDefinition($plugin_id, $definition);
      }
    }
  }

  /**
   * Post save handler for overrides.
   *
   * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface $item
   *   Item saved.
   * @param bool $update
   *   TRUE if is an update.
   */
  public function onMenuOverridePostSave(MicrositeMenuItemOverrideInterface $item, $update = FALSE) {
    if ($item->isSyncing()) {
      return;
    }
    $plugin_id = 'entity_hierarchy_microsite:' . $item->getTarget();
    if ($this->menuLinkManager->hasDefinition($plugin_id) && ($original = $this->menuLinkManager->getDefinition($plugin_id))) {
      $definition = [
        'title' => $item->label(),
        'weight' => $item->getWeight(),
        'form_class' => MicrositeMenuItemForm::class,
        'enabled' => $item->isEnabled(),
        'expanded' => $item->isExpanded(),
        'parent' => $item->getParent(),
      ] + $original;
      if (!$update) {
        $definition['metadata'] = [
          'original' => array_intersect_key($original, [
            'title' => TRUE,
            'weight' => TRUE,
            'enabled' => TRUE,
            'expanded' => TRUE,
            'parent' => TRUE,
          ]),
        ] + $original['metadata'];
      }
      $this->menuLinkManager->updateDefinition($plugin_id, $definition);
    }
  }

  /**
   * Post delete handler for microsite items.
   *
   * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface[] $items
   *   Deleted items.
   */
  public function onMenuOverridePostDelete(array $items) {
    foreach ($items as $item) {
      $plugin_id = 'entity_hierarchy_microsite:' . $item->getTarget();
      if ($this->menuLinkManager->hasDefinition($plugin_id) && ($definition = $this->menuLinkManager->getDefinition($plugin_id)) && isset($definition['metadata']['original'])) {
        $definition = $definition['metadata']['original'] + $definition;
        $definition['form_class'] = MenuLinkDefaultForm::class;
        unset($definition['metadata']['original']);
        $this->menuLinkManager->updateDefinition($plugin_id, $definition, FALSE);
        continue;
      }
    }
  }

}
