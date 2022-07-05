<?php

namespace Drupal\key\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Exception\KeyValueNotSetException;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Drupal\key\KeyInterface;

/**
 * Adds a key provider that allows a key to be stored in configuration.
 *
 * @KeyProvider(
 *   id = "config",
 *   label = @Translation("Configuration"),
 *   description = @Translation("The Configuration key provider stores the key in Drupal's configuration system."),
 *   storage_method = "config",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class ConfigKeyProvider extends KeyProviderBase implements KeyPluginFormInterface, KeyProviderSettableValueInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'base64_encoded' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // If this key type is for an encryption key.
    if ($form_state->getFormObject()->getEntity()->getKeyType()->getPluginDefinition()['group'] == 'encryption') {
      // Add an option to indicate that the value is stored Base64-encoded.
      $form['base64_encoded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Base64-encoded'),
        '#description' => $this->t('Checking this will store the key with Base64 encoding.'),
        '#default_value' => isset($this->getConfiguration()['base64_encoded']) ? $this->getConfiguration()['base64_encoded'] : $this->defaultConfiguration()['base64_encoded'],
      ];
    }

    return $form;
  }

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
  public function getKeyValue(KeyInterface $key) {
    $key_value = isset($this->configuration['key_value']) ? $this->configuration['key_value'] : '';

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $key_value = base64_decode($key_value);
    }

    return $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $key_value = base64_encode($key_value);
    }

    $this->configuration['key_value'] = $key_value;

    if (isset($this->configuration['key_value'])) {
      return TRUE;
    }
    else {
      throw new KeyValueNotSetException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    // Nothing needs to be done, since the value will have been deleted
    // with the Key entity.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function obscureKeyValue($key_value, array $options = []) {
    // Key values are not obscured when this provider is used.
    return $key_value;
  }

}
