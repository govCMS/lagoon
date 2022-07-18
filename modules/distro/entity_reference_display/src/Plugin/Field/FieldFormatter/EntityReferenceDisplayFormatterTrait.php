<?php

namespace Drupal\entity_reference_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin trait for the 'entity_reference_display' formatters.
 */
trait EntityReferenceDisplayFormatterTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_field' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    // Get display mode fields.
    $display_fields = $this->getDisplayFields();
    // Create select element.
    $element['display_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode field'),
      '#description' => $this->t('Select a field of which value will be used as a display mode for rendering.'),
      '#options' => $display_fields,
      '#default_value' => $this->getSetting('display_field'),
      '#required' => TRUE,
      '#access' => count($display_fields) > 1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Get display mode fields.
    $display_fields = $this->getDisplayFields();
    // Get selected display mode field.
    $display_field = $this->getDisplayField($display_fields);
    // Find field label.
    $field_label = $display_fields[$display_field];
    // Create settings summary.
    $summary[] = $this->t('Rendered by display mode selected in "@field" field.', [
      '@field' => $field_label,
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get display mode fields.
    $display_fields = $this->getDisplayFields();
    // Get selected display mode field.
    $display_field = $this->getDisplayField($display_fields);
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $items->getEntity();
    // Only if entity has this field.
    if ($entity->hasField($display_field)) {
      // Get selected display mode value from field.
      $display_mode = $entity->get($display_field)->value;
      // Only if some value available.
      if (!empty($display_mode)) {
        // Override display mode setting.
        $this->setSetting('view_mode', $display_mode);
      }
    }
    // Use parent method for rendering.
    return parent::viewElements($items, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Get all fields associated with current entity.
    $entity_type = $field_definition->getTargetEntityTypeId();
    $entity_bundle = $field_definition->getTargetBundle();
    if (!empty($entity_bundle)) {
      $entity_fields = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions($entity_type, $entity_bundle);
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      foreach ($entity_fields as $field) {
        // Formatter is only available for entity types with display mode field.
        if ($field->getType() == 'entity_reference_display') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieve available display mode fields.
   */
  private function getDisplayFields() {
    $display_fields = [];
    // Get all fields associated with current entity.
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $entity_bundle = $this->fieldDefinition->getTargetBundle();
    $entity_fields = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($entity_type, $entity_bundle);
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($entity_fields as $key => $field) {
      // Find display mode fields.
      if ($field->getType() == 'entity_reference_display') {
        $display_fields[$key] = $field->getLabel();
      }
    }
    return $display_fields;
  }

  /**
   * Retrieve selected display mode field.
   */
  private function getDisplayField(array $display_fields) {
    // Get display mode field setting.
    $display_field = $this->getSetting('display_field');
    // Use first field if no setting exists.
    if (empty($display_field)) {
      $display_field = key($display_fields);
    }
    return $display_field;
  }

}
