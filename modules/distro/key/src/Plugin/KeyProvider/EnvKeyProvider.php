<?php

namespace Drupal\key\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\KeyInterface;

/**
 * A key provider that allows a key to be stored in an environment variable.
 *
 * @KeyProvider(
 *   id = "env",
 *   label = @Translation("Environment"),
 *   description = @Translation("The Environment key provider allows a key to be retrieved from an environment variable."),
 *   storage_method = "env",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class EnvKeyProvider extends KeyProviderBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'env_variable' => '',
      'base64_encoded' => FALSE,
      'strip_line_breaks' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['env_variable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment variable'),
      '#description' => $this->t('Name of the environment variable.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['env_variable'],
    ];

    $form['strip_line_breaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing line breaks'),
      '#description' => $this->t('Check this to remove any trailing line breaks from the variable. Leave unchecked if there is a chance that a line break could be a valid character in the key.'),
      '#default_value' => $this->getConfiguration()['strip_line_breaks'],
    ];

    // If this key type is for an encryption key.
    if ($form_state->getFormObject()->getEntity()->getKeyType()->getPluginDefinition()['group'] == 'encryption') {
      // Add an option to indicate that the value is stored Base64-encoded.
      $form['base64_encoded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Base64-encoded'),
        '#description' => $this->t('Check this if the key in the variable is Base64-encoded.'),
        '#default_value' => $this->getConfiguration()['base64_encoded'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();
    $env_variable = $key_provider_settings['env_variable'];
    $key_value = getenv($env_variable);

    // Does the env variable exist.
    if (!$key_value) {
      $form_state->setErrorByName('env_variable', $this->t('The environment variable does not exist or it is empty.'));
      return;
    }

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
    $env_variable = $this->configuration['env_variable'];
    $key_value = getenv($env_variable);

    if (!$key_value) {
      return NULL;
    }

    if (isset($this->configuration['strip_line_breaks']) && $this->configuration['strip_line_breaks'] == TRUE) {
      $key_value = rtrim($key_value, "\n\r");
    }

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $key_value = base64_decode($key_value);
    }

    return $key_value;
  }

}
