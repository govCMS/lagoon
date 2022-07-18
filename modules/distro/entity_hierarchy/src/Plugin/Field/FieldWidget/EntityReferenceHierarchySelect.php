<?php

namespace Drupal\entity_hierarchy\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Select widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_hierarchy_select",
 *   label = @Translation("Select"),
 *   description = @Translation("A select field with associated data."),
 *   field_types = {
 *     "entity_reference_hierarchy"
 *   }
 * )
 */
class EntityReferenceHierarchySelect extends OptionsWidgetBase {
  const HIDE_WEIGHT = 'hide_weight';

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
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element += [
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => isset($items[$delta]->target_id) ? $items[$delta]->target_id : '',
    ];

    $widget = [
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    ];

    $widget['target_id'] = $element;
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

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    // Single select: add a 'none' option for non-required fields,
    // and a 'select a value' option for required fields that do not come
    // with a value selected.
    if (!$this->required) {
      return t('- None -');
    }
    if (!$this->has_value) {
      return t('- Select a value -');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#value'] == '_none') {
      if ($element['#required'] && $element['#value'] == '_none') {
        $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
      }
      else {
        $form_state->setValueForElement($element, NULL);
      }
    }
    else {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

}
