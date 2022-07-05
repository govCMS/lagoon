<?php

namespace Drupal\key\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;

/**
 * Defines a key input that generates a key value.
 *
 * @KeyInput(
 *   id = "generate",
 *   label = @Translation("Generate"),
 *   description = @Translation("A key input that generates a key value.")
 * )
 */
class GenerateKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'generated' => FALSE,
      'display_once' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // If the key value has already been generated.
    if ($config['generated']) {
      $form['key_value_message'] = [
        '#markup' => t('The key value has already been generated and will not be changed.'),
      ];
      $form['display_once'] = [
        '#type' => 'value',
        '#value' => $config['display_once'],
      ];
    }
    else {
      $form['key_value_message'] = [
        '#markup' => t('The key value will be automatically generated using the selected key type settings.'),
      ];

      // Allow the user to choose to display the key value once.
      $form['display_once'] = [
        '#type' => 'checkbox',
        '#title' => t('Display value'),
        '#description' => t('Check this to display the generated value once.'),
        '#default_value' => $config['display_once'],
      ];
    }

    $form['generated'] = [
      '#type' => 'value',
      '#value' => $config['generated'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processSubmittedKeyValue(FormStateInterface $form_state) {
    $key_input_settings = $form_state->getValues();
    $key_value_data = $form_state->get('key_value');

    // If the key value has already been generated, use the existing value.
    // Otherwise, generate a key.
    if ($key_input_settings['generated']) {
      $processed_values = [
        'submitted' => $key_value_data['current'],
        'processed_submitted' => $key_value_data['current'],
      ];
    }
    else {
      /** @var \Drupal\key\Entity\Key $key */
      $key = $form_state->getFormObject()->getEntity();
      $key_type = $key->getKeyType();

      // Generate the key value using the key type configuration.
      $key_value = $key_type::generateKeyValue($form_state->getUserInput()['key_type_settings']);

      $processed_values = [
        'submitted' => $key_value,
        'processed_submitted' => $key_value,
      ];

      $form_state->setValue('generated', TRUE);

      // If the user requested to display the generated password.
      if ($key_input_settings['display_once']) {
        $this->messenger()->addMessage(t('A key value of the requested type has been generated and is displayed below as a Base64-encoded string. You will need to decode it to get the actual key value, which may or may not be human-readable. The key value will not be displayed again, so take note of it now, if necessary.<br>%key_value', ['%key_value' => base64_encode($key_value)]));
      }
    }

    return $processed_values;
  }

}
