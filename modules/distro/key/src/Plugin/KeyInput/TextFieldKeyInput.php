<?php

namespace Drupal\key\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;

/**
 * Defines a key input that provides a simple text field.
 *
 * @KeyInput(
 *   id = "text_field",
 *   label = @Translation("Text field"),
 *   description = @Translation("A simple text field.")
 * )
 */
class TextFieldKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_value' => '',
      'base64_encoded' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $key_value_data = $form_state->get('key_value');

    /** @var \Drupal\key\Entity\Key $key */
    $key = $form_state->getFormObject()->getEntity();
    $form['key_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key value'),
      '#required' => $key->getKeyProvider()->getPluginDefinition()['key_value']['required'],
      '#maxlength' => 4096,
      '#default_value' => $key_value_data['current'],
      // Tell the browser not to autocomplete this field.
      '#attributes' => ['autocomplete' => 'off'],
    ];

    // If this key input is for an encryption key.
    if ($key->getKeyType()->getPluginDefinition()['group'] == 'encryption') {
      // Add an option to indicate that the value is Base64-encoded.
      $form['base64_encoded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Base64-encoded'),
        '#description' => $this->t('Check this if the key value being submitted has been Base64-encoded.'),
        '#default_value' => $this->getConfiguration()['base64_encoded'],
      ];
    }

    return $form;
  }

}
