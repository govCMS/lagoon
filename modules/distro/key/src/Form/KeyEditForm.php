<?php

namespace Drupal\key\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class KeyEditForm.
 *
 * @package Drupal\key\Form
 */
class KeyEditForm extends KeyFormBase {

  /**
   * Keeps track of extra confirmation step on key edit.
   *
   * @var bool
   */
  protected $editConfirmed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Only when the form is first built.
    if (!$form_state->isRebuilding()) {
      /* @var $key \Drupal\key\Entity\Key */
      $key = $this->entity;
      $this->originalKey = clone $key;

      $key_type = $key->getKeyType();
      $key_provider = $key->getKeyProvider();
      $key_input = $key->getKeyInput();

      $obscure_options = [];

      // Add settings from plugins.
      $obscure_options['key_type_id'] = $key_type->getPluginId();
      $obscure_options['key_type_group'] = $key_type->getPluginDefinition()['group'];
      $obscure_options['key_provider_id'] = $key_provider->getPluginId();

      $key_value = [];

      // Get the existing key value.
      $key_value['original'] = $key->getKeyValue();

      // Process the original key value.
      $key_value['processed_original'] = $key_input->processExistingKeyValue($key_value['original']);

      // Obscure the processed key value.
      $obscured_key_value = $key_provider->obscureKeyValue($key_value['processed_original'], $obscure_options);
      if ($obscured_key_value != $key_value['processed_original']) {
        $key_value['obscured'] = $obscured_key_value;
      }
      else {
        $key_value['obscured'] = '';
      }

      // Set the current value.
      $key_value['current'] = (!empty($key_value['obscured'])) ? $key_value['obscured'] : $key_value['processed_original'];

      // Store the key value information in form state for use by plugins.
      $form_state->set('key_value', $key_value);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Edit key %label', ['%label' => $this->entity->label()]);

    // If editing has not been confirmed yet, display a warning and require
    // confirmation.
    if (!$this->editConfirmed) {
      $form['confirm_edit'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Be extremely careful when editing a key! It may result in broken site functionality. Are you sure you want to edit this key?'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      return EntityForm::form($form, $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // If editing has not been confirmed yet.
    if (!$this->editConfirmed) {
      return [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#button_type' => 'primary',
          '#submit' => [
            [$this, 'confirmEdit'],
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#attributes' => ['class' => ['button']],
          '#url' => Url::fromRoute('entity.key.collection'),
          '#cache' => [
            'contexts' => [
              'url.query_args:destination',
            ],
          ],
        ],
      ];
    }
    else {
      return parent::actions($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If editing has not been confirmed yet.
    if (!$this->editConfirmed) {
      return;
    }
    else {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * Submit handler for the edit confirmation button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function confirmEdit(array &$form, FormStateInterface $form_state) {
    $this->editConfirmed = TRUE;
    $form_state->setRebuild();
  }

}
