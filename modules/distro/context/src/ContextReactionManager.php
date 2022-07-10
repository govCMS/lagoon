<?php

namespace Drupal\context;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a manager for Context Reactions.
 */
class ContextReactionManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ContextReaction', $namespaces, $module_handler, 'Drupal\context\ContextReactionInterface', 'Drupal\context\Reaction\Annotation\ContextReaction');

    $this->alterInfo('context_condition_info');
    $this->setCacheBackend($cache_backend, 'context_condition_plugins');
  }

}
