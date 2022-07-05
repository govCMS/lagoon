<?php

namespace Drupal\key\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Defines a generic key type for encryption.
 *
 * @KeyType(
 *   id = "encryption",
 *   label = @Translation("Encryption"),
 *   description = @Translation("A generic key type to use for an encryption key that does not belong to any other defined key type."),
 *   group = "encryption",
 *   key_value = {
 *     "plugin" = "text_field"
 *   }
 * )
 */
class EncryptionKeyType extends KeyTypeBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_size' => 128,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Define the most common key size options.
    $key_size_options = [
      '128' => 128,
      '256' => 256,
    ];

    $key_size = $this->getConfiguration()['key_size'];
    $key_size_other_value = '';
    if (!in_array($key_size, $key_size_options)) {
      $key_size_other_value = $key_size;
      $key_size = 'other';
    }

    $form['key_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Key size'),
      '#description' => $this->t('The size of the key in bits, with 8 bits per byte.'),
      '#options' => $key_size_options + ['other' => $this->t('Other')],
      '#default_value' => $key_size,
      '#required' => TRUE,
    ];
    $form['key_size_other_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key size (other value)'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter a custom key size in bits.'),
      '#default_value' => $key_size_other_value,
      '#maxlength' => 20,
      '#size' => 20,
      '#states' => [
        'visible' => [
          'select[name="key_type_settings[key_size]"]' => ['value' => 'other'],
        ],
        'required' => [
          'select[name="key_type_settings[key_size]"]' => ['value' => 'other'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // If 'Other' was selected for the key size, use the custom entered value.
    $key_size = $form_state->getValue('key_size');
    if ($key_size == 'other') {
      $form_state->setValue('key_size', $form_state->getValue('key_size_other_value'));
    }
    $form_state->unsetValue('key_size_other_value');
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
  public static function generateKeyValue(array $configuration) {
    if (!empty($configuration['key_size'])) {
      $bytes = $configuration['key_size'] / 8;
    }
    else {
      // If no key size has been defined, use 32 bytes as the default.
      $bytes = 32;
    }
    $random_key = random_bytes($bytes);

    return $random_key;
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    if (!$form_state->getValue('key_size')) {
      return;
    }

    // Validate the key size.
    $bytes = $form_state->getValue('key_size') / 8;
    if (strlen($key_value) != $bytes) {
      $form_state->setErrorByName('key_size', $this->t('The selected key size does not match the actual size of the key.'));
    }
  }

}
