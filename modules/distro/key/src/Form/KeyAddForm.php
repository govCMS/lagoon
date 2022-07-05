<?php

namespace Drupal\key\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeyAddForm.
 *
 * @package Drupal\key\Form
 */
class KeyAddForm extends KeyFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only when the form is first built.
    if (!$form_state->isRebuilding()) {
      // Set the key value data to NULL, since this is a new key.
      $form_state->set('key_value', [
        'original' => NULL,
        'processed_original' => NULL,
        'obscured' => NULL,
        'current' => '',
      ]);
    }

    return parent::buildForm($form, $form_state);
  }

}
