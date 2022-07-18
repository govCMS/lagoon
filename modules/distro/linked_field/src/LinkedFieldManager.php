<?php

namespace Drupal\linked_field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;

/**
 * Provides helper methods for client related functionalities.
 */
class LinkedFieldManager implements LinkedFieldManagerInterface {

  /**
   * The Linked Field configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, Token $token, EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $config_factory->get('linked_field.config');
    $this->pathValidator = $path_validator;
    $this->token = $token;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeBlacklist() {
    return ['link'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    $attributes = $this->config->get('attributes') ?: [];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationFields($entity_type_id, $bundle_id) {
    $field_names = [];
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
    $label_field = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('label');

    // Remove the label field from fields.
    unset($fields[$label_field]);

    foreach ($fields as $field_name => $field) {
      if (in_array($field->getType(),
        [
          'link', 'string',
          'list_float',
          'list_string',
        ])) {
        $field_names[$field_name] = $field->getLabel() . ' (' . $field_name . ')';
      }
    }

    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplaySettings(EntityInterface $entity, $view_mode, $field_name) {
    $settings = [];
    $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $component = $entity_display->getComponent($field_name);

    if (isset($component['third_party_settings']['linked_field'])) {
      $settings = $component['third_party_settings']['linked_field'];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDisplaySettings(EntityViewDisplayInterface $display, $field_name) {
    $settings = [];
    $component = $display->getComponent($field_name);

    if (isset($component['third_party_settings']['linked_field'])) {
      $settings = $component['third_party_settings']['linked_field'];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination($type, $value, array $context) {
    $uri = '';

    if ($type == 'field') {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
      $field_items = $context['entity']->get($value);

      if (!$field_items->count()) {
        return FALSE;
      }

      $uri = $this->getFieldValue($field_items);
    }
    elseif ($type == 'custom') {
      // If custom type is used we simply return the custom text so we
      // can replace tokens later on.
      $uri = $value;
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDestinationUrl($destination) {
    $parsed_url = parse_url($destination);

    // Try to fix internal URLs by prefixing them with "internal:/".
    if (!isset($parsed_url['scheme'])) {
      // Let's support "/node/1" and "node/1" here.
      $slash = $destination[0] == '/' ? '' : '/';
      $destination = 'internal:' . $slash . $destination;
    }

    try {
      $url = Url::fromUri($destination);
      $destination_url = $url->setAbsolute()->toString();

      return $destination_url;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue(FieldItemListInterface $field_items) {
    $items = $field_items->getValue();
    $field_definition = $field_items->getFieldDefinition();
    $field_type = $field_definition->getType();
    // @TODO: We should add support for deltas.
    $item = $items[0];
    $uri = '';

    if ($field_type == 'link') {
      $uri = $item['uri'];
    }
    else {
      $uri = isset($item['value']) ? $item['value'] : '';
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceToken($text, array $data = [], array $options = []) {
    return $this->token->replace($text, $data, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function linkNode(\DOMNode $node, \DOMDocument $dom, array $attributes) {
    if ($node->hasChildNodes() && $node->nodeName != 'a') {
      $c = $node->childNodes->length;

      for ($i = $c; $i > 0; --$i) {
        $child = $node->childNodes->item($i - 1);
        $this->linkNode($child, $dom, $attributes);

        if ($child->nodeType == XML_TEXT_NODE) {
          $text = $child->textContent;

          if (strlen(trim($text))) {
            // Convert all applicable characters to HTML entities.
            $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
            // Create new <a> element, set the text and the href attribute.
            $element = $dom->createElement('a', $text);

            // Adding the attributes.
            foreach ($attributes as $name => $value) {
              if ($value) {
                // Convert all HTML entities back to
                // their applicable characters.
                $value = Html::decodeEntities($value);
                $element->setAttribute($name, $value);
              }
            }

            // Replace the the original element with the new one.
            $node->replaceChild($element, $child);
          }
        }
        elseif ($child->nodeName == 'img') {
          // Create new <a> element, set the href and append the image.
          $element = $dom->createElement('a');

          // Adding the attributes.
          foreach ($attributes as $name => $value) {
            if ($value) {
              // Convert all HTML entities back to their applicable characters.
              $value = Html::decodeEntities($value);
              $element->setAttribute($name, $value);
            }
          }

          $node->replaceChild($element, $child);
          $element->appendChild($child);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function linkHtml($html, array $attributes) {
    // Convert HTML code to a DOMDocument object.
    $html_dom = Html::load($html);
    $body = $html_dom->getElementsByTagName('body');
    $node = $body->item(0);

    // Recursively walk over the DOMDocument body and place the links.
    $this->linkNode($node, $html_dom, $attributes);

    // Converting the DOMDocument object back to HTML code.
    return Html::serialize($html_dom);
  }

}
