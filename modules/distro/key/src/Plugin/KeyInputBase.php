<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a base class for Key Type plugins.
 */
abstract class KeyInputBase extends KeyPluginBase implements KeyInputInterface, KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function processSubmittedKeyValue(FormStateInterface $form_state) {
    // This is the default behavior. If a field named 'key_value' exists in
    // the key input settings, remove it from the settings and return it as
    // the submitted value. If the key value is Base64-encoded, decode it and
    // return the result as the processed_submitted value. Input plugins can
    // override this behavior to perform more complex processing.
    $processed_values = [
      'submitted' => NULL,
      'processed_submitted' => NULL,
    ];
    $key_input_settings = $form_state->getValues();
    $key_value_data = $form_state->get('key_value');
    if (isset($key_input_settings['key_value'])) {
      // If the submitted key value is not empty and equal to the obscured
      // value.
      if (!empty($key_input_settings['key_value']) && $key_input_settings['key_value'] == $key_value_data['obscured']) {
        // Use the processed original value as the submitted value.
        $processed_values['submitted'] = $key_value_data['processed_original'];
      }
      else {
        $processed_values['submitted'] = $key_input_settings['key_value'];
      }

      if (isset($key_input_settings['base64_encoded']) && $key_input_settings['base64_encoded'] == TRUE) {
        $processed_values['processed_submitted'] = base64_decode($processed_values['submitted']);
      }
      else {
        $processed_values['processed_submitted'] = $processed_values['submitted'];
      }

      unset($key_input_settings['key_value']);
      $form_state->setValues($key_input_settings);
    }

    return $processed_values;
  }

  /**
   * {@inheritdoc}
   */
  public function processExistingKeyValue($key_value) {
    // This is the default behavior. The key value is Base64-encoded if
    // it was originally submitted with Base64 encoding. Otherwise, it is
    // returned as-is.
    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $processed_value = base64_encode($key_value);
    }
    else {
      $processed_value = $key_value;
    }

    return $processed_value;
  }

}
