<?php

namespace Drupal\entity_hierarchy_microsite\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_hierarchy_microsite\Plugin\MicrositePluginTrait;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for a branding and logo block for a microsite.
 *
 * @Block(
 *   id = "entity_hierarchy_microsite_branding",
 *   admin_label = @Translation("Microsite Branding"),
 *   category = @Translation("Microsite"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Current node"))
 *   }
 * )
 */
class MicrositeLogoBranding extends BlockBase implements ContainerFactoryPluginInterface {

  use MicrositePluginTrait {
    create as parentCreate;
  }

  /**
   * Sets value of EntityTypeManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Value for EntityTypeManager.
   *
   * @return $this
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    return $this;
  }

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\entity_hierarchy_microsite\Plugin\Block\MicrositeLogoBranding $instance */
    $instance = self::parentCreate($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setEntityTypeManager($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    if (!($node = $this->getContextValue('node')) ||
      !($node instanceof NodeInterface) ||
      !($microsites = $this->childOfMicrositeLookup->findMicrositesForNodeAndField($node, $this->configuration['field']))) {
      $build = [];
      if ($node) {
        $cache->addCacheableDependency($node);
      }
      $cache->applyTo($build);
      return $build;
    }
    /** @var \Drupal\entity_hierarchy_microsite\Entity\MicrositeInterface $microsite */
    $microsite = reset($microsites);
    $cache->addCacheableDependency($node);
    $cache->addCacheableDependency($microsite);
    if ($home = $microsite->getHome()) {
      $cache->addCacheableDependency($home);
    }
    $build = [
      '#theme' => 'entity_hierarchy_microsite_branding',
      '#site_name' => $microsite->label(),
      '#site_home' => $home ? $home->toUrl()->toString() : '',
      '#microsite' => $microsite,
    ];
    if ($media = $microsite->getLogo()) {
      $cache->addCacheableDependency($media);
      $build['#site_logo'] = $this->entityTypeManager->getViewBuilder('media')->view($media, 'entity_hierarchy_microsite');
    }
    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_merge(parent::getCacheContexts(), ['entity_hierarchy_microsite']);
  }

}
