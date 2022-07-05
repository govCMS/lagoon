<?php

namespace Drupal\key\Plugin\KeyType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Defines a generic key type for authentication with multiple values.
 *
 * @KeyType(
 *   id = "authentication_multivalue",
 *   label = @Translation("Authentication (Multivalue)"),
 *   description = @Translation("A generic key type to use for an authentication key that contains multiple values."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {}
 *   }
 * )
 */
class AuthenticationMultivalueKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    // Return an empty JSON element.
    return '[]';
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    if (empty($key_value)) {
      return;
    }

    // If a field named "key_value" exists in the key input settings, use it for
    // the error element, if necessary. Otherwise, use the entire form.
    if (isset($form['settings']['input_section']['key_input_settings']['key_value'])) {
      $error_element = $form['settings']['input_section']['key_input_settings']['key_value'];
    }
    else {
      $error_element = $form;
    }

    $value = $this->unserialize($key_value);
    if ($value === NULL) {
      $form_state->setError($error_element, $this->t('The key value does not contain valid JSON.'));
      return;
    }

    $definition = $this->getPluginDefinition();
    $fields = $definition['multivalue']['fields'];

    foreach ($fields as $id => $field) {
      if (!is_array($field)) {
        $field = ['label' => $field];
      }

      if (isset($field['required']) && $field['required'] === FALSE) {
        continue;
      }

      if (!isset($value[$id])) {
        $form_state->setError($error_element, $this->t('The key value is missing the field %field.', ['%field' => $id]));
      }
      elseif (empty($value[$id])) {
        $form_state->setError($error_element, $this->t('The key value field %field is empty.', ['%field' => $id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(array $array) {
    return Json::encode($array);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($value) {
    return Json::decode($value);
  }

}
