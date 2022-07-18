<?php

namespace Drupal\entity_reference_display\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Plugin implementation of the 'entity_reference_display' field type.
 *
 * @FieldType(
 *   id = "entity_reference_display",
 *   label = @Translation("Display mode"),
 *   description = @Translation("This field allows you to specify a display mode for entity reference field."),
 *   category = @Translation("Reference"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default"
 * )
 */
class EntityReferenceDisplayItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'value' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('Display mode'))
        ->setRequired(TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'exclude' => [],
      'negate' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    // Prepare select element with all options.
    $element['exclude'] = [
      '#type' => 'select',
      '#title' => $this->t('Excluded display modes'),
      '#description' => $this->t('Select all display modes which will not be offered.'),
      '#options' => $this->getAllDisplayModes(),
      '#default_value' => $this->getSetting('exclude'),
      '#multiple' => TRUE,
    ];
    $element['negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include selected display modes instead of excluding'),
      '#description' => $this->t('If checked, only display modes selected above will be offered.'),
      '#default_value' => $this->getSetting('negate'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    // Get values from possible options.
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    // Get all display modes in alphabetical order.
    $display_modes = $this->getAllDisplayModes();
    // Get displays to exclude from options.
    $exclude = $this->getSetting('exclude');
    // Check if display modes should be negated.
    $negate = !empty($this->getSetting('negate'));
    // Get options array.
    $options = [];
    foreach ($display_modes as $key => $display_mode) {
      // Only if display is not excluded or it's negated and included.
      if ((!$negate && !isset($exclude[$key])) || ($negate && isset($exclude[$key]))) {
        // Add display between options.
        $options[$key] = $display_mode;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Get values from settable options.
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // Get the same as from possible options.
    return $this->getPossibleOptions($account);
  }

  /**
   * Get all display modes in alphabetical order with Default as first.
   */
  private function getAllDisplayModes() {
    // Get all display modes grouped by entity types.
    $display_modes = \Drupal::service('entity_display.repository')
      ->getAllViewModes();
    // Get basic information about display modes.
    $result = [];
    foreach ($display_modes as $modes) {
      foreach ($modes as $mode => $info) {
        // If display mode is not already in result set.
        if (!isset($result[$mode])) {
          $result[$mode] = $info['label'];
        }
      }
    }
    // Sort display modes in alphabetical order.
    asort($result);
    // Return array of all display modes prepended by Default.
    return ['default' => 'Default'] + $result;
  }

}
