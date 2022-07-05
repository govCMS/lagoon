<?php

namespace Drupal\key\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Url;

/**
 * Provides a select form element that displays available keys.
 *
 * Properties:
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 * - #key_filters: An array of filters to apply to the list of keys.
 * - #key_description: A boolean value that determines if information about
 *   keys is added to the element's description.
 *
 * @FormElement("key_select")
 */
class KeySelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Add a process function.
    array_unshift($info['#process'], [$class, 'processKeySelect']);

    // Add a property for key filters.
    $info['#key_filters'] = [];

    // Add a property for key description.
    $info['#key_description'] = TRUE;

    return $info;
  }

  /**
   * Processes a key select list form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processKeySelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Get the list of available keys and define the options.
    $options = \Drupal::service('key.repository')->getKeyNamesAsOptions($element['#key_filters']);
    $element['#options'] = $options;

    // Prefix the default description with information about keys,
    // unless disabled.
    if ($element['#key_description']) {
      $original_description = (isset($element['#description'])) ? $element['#description'] : '';
      // @todo this causes escaping.
      $key_description = t('Choose an available key. If the desired key is not listed, <a href=":link">create a new key</a>.', [':link' => Url::fromRoute('entity.key.add_form')->toString()]);
      $element['#description'] = $key_description . ' ' . $original_description;
    }

    return $element;
  }

}
