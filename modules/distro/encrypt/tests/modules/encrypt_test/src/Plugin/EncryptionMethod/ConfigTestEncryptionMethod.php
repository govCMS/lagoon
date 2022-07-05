<?php

namespace Drupal\encrypt_test\Plugin\EncryptionMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Drupal\encrypt\Plugin\EncryptionMethodPluginFormInterface;

/**
 * ConfigTestEncryptionMethod testing class.
 *
 * @EncryptionMethod(
 *   id = "config_test_encryption_method",
 *   title = @Translation("Config Test Encryption method"),
 *   description = "A test encryption method with configuration.",
 *   key_type = {"encryption"}
 * )
 */
class ConfigTestEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface, EncryptionMethodPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    $prefix = $key . $this->getConfiguration()['mode'];
    return str_rot13($prefix . $text);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    $decoded = str_rot13($text);
    $prefix = $key . $this->getConfiguration()['mode'];
    // Strip out key, to retrieve original text.
    if (substr($decoded, 0, strlen($prefix)) == $prefix) {
      return substr($decoded, strlen($prefix));
    }
    else {
      return "invalid key";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Cipher mode'),
      '#options' => [
        'ECB' => $this->t('ECB'),
        'CBC' => $this->t('CBC'),
        'CFB' => $this->t('CFB'),
        'OFB' => $this->t('OFB'),
      ],
      '#default_value' => 'CBC',
      '#required' => TRUE,
    ];

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

}
