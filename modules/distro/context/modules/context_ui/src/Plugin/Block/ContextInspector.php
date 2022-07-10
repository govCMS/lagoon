<?php

namespace Drupal\context_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'context inspector' block.
 *
 * @Block(
 *   id = "context_inspector",
 *   admin_label = @Translation("Context inspector"),
 *   category = @Translation("Debugging")
 * )
 */
class ContextInspector extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $module = $moduleHandler->moduleExists('devel');
    $permission = \Drupal::currentUser()->hasPermission('access devel information');
    if ($module && $permission) {
      /** @var \Drupal\context\ContextManager $context_manager */
      $context_manager = \Drupal::service('context.manager');
      /** @codingStandardsIgnoreStart * */
      $output = kpr($context_manager->getActiveContexts(), TRUE);
      /** @codingStandardsIgnoreEnd * */
    }
    elseif ($module && !$permission) {
      $output = $this->t('You do not have permissions to view debug content.');
    }
    elseif (!$module) {
      $output = $this->t('Please enable the devel module to use the context inspector.');
    }
    $build = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    return isset($output) ? $build : [];
  }

}
