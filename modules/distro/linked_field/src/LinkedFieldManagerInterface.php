<?php

namespace Drupal\linked_field;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface LinkedFieldManagerInterface.
 *
 * @package Drupal\linked_field
 */
interface LinkedFieldManagerInterface {

  /**
   * Get list of blacklisted field types.
   *
   * @return array
   *   An array containing blacklisted field types.
   */
  public function getFieldTypeBlacklist();

  /**
   * Get configured attributes from configuration.
   *
   * @return array
   *   An array of the configured attributes.
   */
  public function getAttributes();

  /**
   * Get allowed destination fields.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return array
   *   An array containing the field names keyed by their machine name.
   */
  public function getDestinationFields($entity_type_id, $bundle_id);

  /**
   * Get Linked Field display settings for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being viewed.
   * @param string $view_mode
   *   The name of the view mode.
   * @param string $field_name
   *   The name of the field.
   *
   * @deprecated No longer used by internal code and not recommended.
   *
   * @return array
   *   Getting linked field display settings.
   */
  public function getDisplaySettings(EntityInterface $entity, $view_mode, $field_name);

  /**
   * Get display settings for a given field.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   Getting display settings.
   */
  public function getFieldDisplaySettings(EntityViewDisplayInterface $display, $field_name);

  /**
   * Get the destination for a set field or custom text.
   *
   * @param string $type
   *   The type of the destination, either 'field' or 'custom'.
   * @param string $value
   *   The value of the destination.
   * @param array $context
   *   An array of context information.
   *
   * @return string
   *   Getting the destination.
   */
  public function getDestination($type, $value, array $context);

  /**
   * Build the final destination URL.
   *
   * @param string $destination
   *   The destination.
   *
   * @return false|string
   *   Either FALSE or the destination URL.
   */
  public function buildDestinationUrl($destination);

  /**
   * Get the URI from field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_items
   *   The field item list.
   *
   * @return string
   *   The value of the first field item.
   */
  public function getFieldValue(FieldItemListInterface $field_items);

  /**
   * Replace token in text.
   *
   * @param string $text
   *   The text with tokens included.
   * @param array $data
   *   Additional context information.
   * @param array $options
   *   An options array for the token replacement.
   *
   * @return string
   *   String with tokens.
   */
  public function replaceToken($text, array $data = [], array $options = []);

  /**
   * Link a DOM node.
   *
   * @param \DOMNode $node
   *   An object which gets investigated.
   * @param \DOMDocument $dom
   *   An object which represents an entire HTML or XML document.
   * @param array $attributes
   *   An array containing element attributes.
   */
  public function linkNode(\DOMNode $node, \DOMDocument $dom, array $attributes);

  /**
   * Link HTML code with set link attributes.
   *
   * @param string $html
   *   The list of HTML.
   * @param array $attributes
   *   An associative array of attributes.
   *
   * @return string
   *   Linking the html code.
   */
  public function linkHtml($html, array $attributes);

}
