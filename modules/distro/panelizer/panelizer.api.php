<?php
use Drupal\Core\Entity\EntityInterface;
/**
 * @file
 * Developer documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow other modules to alter view data.
 *
 * @param string $view_mode
 *   The view_mode being rendered.
 * @param object $entity
 *   The entity being viewed.
 * @param string $langcode
 *   The langcode.
 */
function hook_panelizer_pre_view_builder_alter(&$view_mode, EntityInterface $entity, &$langcode) {
  if ($entity->bundle() == 'page') {
    $view_mode = 'my_custom_view_mode';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
