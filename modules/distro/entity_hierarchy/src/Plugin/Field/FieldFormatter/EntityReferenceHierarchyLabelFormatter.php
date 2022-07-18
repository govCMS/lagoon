<?php

namespace Drupal\entity_hierarchy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_hierarchy' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_hierarchy_label",
 *   label = @Translation("Label with weight"),
 *   description = @Translation("Display the label of the referenced entities with weight."),
 *   field_types = {
 *     "entity_reference_hierarchy"
 *   }
 * )
 */
class EntityReferenceHierarchyLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'weight_output' => 'attribute',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['weight_output'] = [
      '#type' => 'radios',
      '#options' => [
        'suffix' => t('After title'),
        'attribute' => t('In a data attribute'),
      ],
      '#title' => t('Output weight'),
      '#default_value' => $this->getSetting('weight_output'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('weight_output')) {
      case 'attribute':
        $position = $this->t('custom data-* attribute');
        break;

      case 'suffix':
        $position = $this->t('suffix after title');
        break;
    }
    $summary[] = $this->t('Show weight as @position', ['@position' => $position]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      if (!empty($values[$delta]['weight'])) {
        switch ($this->getSetting('weight_output')) {
          case 'attribute':
            $elements[$delta]['#attributes']['data-weight'] = $values[$delta]['weight'];
            break;

          case 'suffix':
            $elements[$delta]['#suffix'] = ' (' . $values[$delta]['weight'] . ')';
            break;
        }
      }
    }

    return $elements;
  }

}
