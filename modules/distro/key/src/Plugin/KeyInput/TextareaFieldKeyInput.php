<?php

namespace Drupal\key\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a key input that provides a simple text field.
 *
 * @KeyInput(
 *   id = "textarea_field",
 *   label = @Translation("Textarea field"),
 *   description = @Translation("A simple textarea field.")
 * )
 */
class TextareaFieldKeyInput extends TextFieldKeyInput {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['key_value']['#type'] = 'textarea';

    return $form;
  }

}
