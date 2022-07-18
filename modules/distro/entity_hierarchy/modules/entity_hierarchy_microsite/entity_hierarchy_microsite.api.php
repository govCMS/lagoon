<?php

/**
 * @file
 * Contains documentation for module APIs.
 */

/**
 * Allows modules to alter the URL generated from a microsite menu override.
 *
 * @param \Drupal\Core\Url $url
 *   The default URL.
 * @param \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface $override
 *   The override entity.
 * @param \Drupal\entity_hierarchy_microsite\Plugin\Menu\MicrositeMenuItem $menu_link
 *   The menu link plugin instance.
 */
function hook_entity_hierarchy_microsite_menu_item_url_alter(\Drupal\Core\Url $url, \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface $override, \Drupal\entity_hierarchy_microsite\Plugin\Menu\MicrositeMenuItem $menu_link) {
  $attributes = $url->getOption('attributes');
  $attributes['class'] = [$override->some_field->value];
  $url->setOption('attributes', $attributes);
}

/**
 * Allows modules to alter the microsite menu links.
 *
 * @param array $links
 *   The link definitions to be altered.
 */
function hook_entity_hierarchy_microsite_links_alter($links) {
  // Disable all test node links in the microsite menu.
  foreach ($links as $key => $link) {
    if (empty($link['menu_name']) ||
      $link['menu_name'] !== 'entity-hierarchy-microsite') {
      continue;
    }
    if (empty($link['options']['entity']) ||
      (!$node = $link['options']['entity']) ||
      !$node instanceof \Drupal\node\NodeInterface ||
      $node->bundle() !== 'test') {
      continue;
    }
    $links[$key]['enabled'] = 0;
  }
}
