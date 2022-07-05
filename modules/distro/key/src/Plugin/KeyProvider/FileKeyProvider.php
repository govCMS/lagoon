<?php

namespace Drupal\key\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\KeyInterface;

/**
 * Adds a key provider that allows a key to be stored in a file.
 *
 * @KeyProvider(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("The File key provider allows a key to be stored in a file, preferably outside of the web root."),
 *   storage_method = "file",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class FileKeyProvider extends KeyProviderBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'file_location' => '',
      'base64_encoded' => FALSE,
      'strip_line_breaks' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['file_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File location'),
      '#description' => $this->t('The location of the file in which the key will be stored. The path may be absolute (e.g., %abs), relative to the Drupal directory (e.g., %rel), or defined using a stream wrapper (e.g., %str).', [
        '%abs' => '/etc/keys/foobar.key',
        '%rel' => '../keys/foobar.key',
        '%str' => 'private://keys/foobar.key',
      ]),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['file_location'],
    ];

    $form['strip_line_breaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing line breaks'),
      '#description' => $this->t('Check this to remove any trailing line breaks from the file. Leave unchecked if there is a chance that a line break could be a valid character in the key.'),
      '#default_value' => $this->getConfiguration()['strip_line_breaks'],
    ];

    // If this key type is for an encryption key.
    if ($form_state->getFormObject()->getEntity()->getKeyType()->getPluginDefinition()['group'] == 'encryption') {
      // Add an option to indicate that the value is stored Base64-encoded.
      $form['base64_encoded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Base64-encoded'),
        '#description' => $this->t('Check this if the key in the file is Base64-encoded.'),
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
    $file = $key_provider_settings['file_location'];

    // Does the file exist?
    if (!is_file($file)) {
      $form_state->setErrorByName('file_location', $this->t('There is no file at the specified location.'));
      return;
    }

    // Is the file readable?
    if ((!is_readable($file))) {
      $form_state->setErrorByName('file_location', $this->t('The file at the specified location is not readable.'));
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
    $file = $this->configuration['file_location'];

    // Make sure the file exists and is readable.
    if (!is_file($file) || !is_readable($file)) {
      return NULL;
    }

    $key_value = file_get_contents($file);

    if (isset($this->configuration['strip_line_breaks']) && $this->configuration['strip_line_breaks'] == TRUE) {
      $key_value = rtrim($key_value, "\n\r");
    }

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] == TRUE) {
      $key_value = base64_decode($key_value);
    }

    return $key_value;
  }

}
