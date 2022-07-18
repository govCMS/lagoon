<?php

namespace Drupal\entity_hierarchy\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Widget that uses autocomplete.
 *
 * @FieldWidget(
 *   id = "entity_reference_hierarchy_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field with associated data."),
 *   field_types = {
 *     "entity_reference_hierarchy"
 *   }
 * )
 */
class EntityReferenceHierarchyAutocomplete extends EntityReferenceAutocompleteWidget {
  const HIDE_WEIGHT = 'hide_weight';

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    if (isset($element[0]['target_id'])) {
      return $element[0]['target_id'];
    }
    if (isset($element['target_id'])) {
      return $element['target_id'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [self::HIDE_WEIGHT => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'hide_weight' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide weight field'),
        '#description' => $this->t('Hide the weight field and use the default value instead'),
        '#default_value' => $this->getSetting(self::HIDE_WEIGHT),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting(self::HIDE_WEIGHT)) {
      $summary[] = $this->t('Weight field is hidden');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = [
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    ];
    $widget['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);
    if ($this->getSetting(self::HIDE_WEIGHT)) {
      $widget['weight'] = [
        '#type' => 'value',
        '#value' => isset($items[$delta]->weight) ? $items[$delta]->weight : 0,
      ];
    }
    else {
      $widget['weight'] = [
        '#type' => 'number',
        '#size' => '4',
        '#default_value' => isset($items[$delta]) ? $items[$delta]->weight : 1,
        '#weight' => 10,
      ];

      if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
        $widget['weight']['#placeholder'] = $this->fieldDefinition->getSetting('weight_label');
      }
      else {
        $widget['weight']['#title'] = $this->fieldDefinition->getSetting('weight_label');
      }
    }
    return $widget;
  }

}
