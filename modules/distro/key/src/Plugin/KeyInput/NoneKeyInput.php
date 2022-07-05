<?php

namespace Drupal\key\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;

/**
 * Defines a key input for providers that don't accept a value.
 *
 * @KeyInput(
 *   id = "none",
 *   label = @Translation("None"),
 *   description = @Translation("A key input for providers that don't accept a value.")
 * )
 */
class NoneKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['key_value_message'] = [
      '#markup' => $this->t("The selected key provider does not accept a value. See the provider's description for instructions on how and where to store the key value."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processSubmittedKeyValue(FormStateInterface $form_state) {
    return [
      'submitted' => NULL,
      'processed_submitted' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processExistingKeyValue($key_value) {
    return $key_value;
  }

}
