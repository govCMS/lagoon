<?php

namespace Drupal\key_test\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;

/**
 * Defines a multi-value key input class.
 *
 * @KeyInput(
 *   id = "key_test_multi",
 *   label = @Translation("Test multivalue")
 * )
 */
class MutliValueKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'first' => '',
      'second' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    return $form + [
      'first' => [
        '#type' => 'textfield',
        '#default_value' => $this->configuration['first'],
        '#title' => $this->t('First'),
      ],
      'second' => [
        '#type' => 'textfield',
        '#default_value' => $this->configuration['second'],
        '#title' => $this->t('Second'),
      ],
    ];
  }

}
