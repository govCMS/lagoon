<?php

namespace Drupal\context\Reaction;

use Drupal\context\ContextInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a context reaction form base.
 */
abstract class ContextReactionFormBase extends FormBase {

  /**
   * The context.
   *
   * @var \Drupal\context\ContextInterface
   */
  protected $context;

  /**
   * The context reaction.
   *
   * @var \Drupal\context\ContextReactionInterface
   */
  protected $reaction;

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\context\ContextInterface $context
   *   The context that contains the reaction.
   * @param int $reaction_id
   *   The id of the reaction that is being configured.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContextInterface $context = NULL, $reaction_id = NULL) {
    $this->context = $context;
    $this->reaction = $this->context->getReaction($reaction_id);

    $form['reaction'] = [
      '#tree' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->context->save();
  }

}
