<?php

namespace Drupal\context_ui\Form;

use Drupal\context\ContextManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\context\ContextInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Condition delete form.
 */
class ConditionDeleteForm extends ConfirmFormBase {

  /**
   * The context to delete a condition from.
   *
   * @var \Drupal\context\Entity\Context
   */
  protected $context;

  /**
   * The condition to delete from the context.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * The Context module context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * Construct a condition delete form.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context module context manager.
   */
  public function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_ui_condition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label condition?', [
      '%label' => $this->condition->getPluginDefinition()['label'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->context->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContextInterface $context = NULL, $condition_id = NULL) {
    $this->context = $context;
    $this->condition = $context->getCondition($condition_id);

    $form = parent::buildForm($form, $form_state);

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    // Submit the form with AJAX if possible.
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitFormAjax',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove the condition and save the context.
    $this->context->removeCondition($this->condition->getConfiguration()['id'])->save();

    // If this is not an AJAX request then redirect and show a message.
    if (!$this->getRequest()->isXmlHttpRequest()) {
      $this->messenger()->addMessage($this->t('The condition %name has been removed.', [
        '%name' => $this->condition->getPluginDefinition()['label'],
      ]
      ));

      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

  /**
   * Handle when the form is submitted trough AJAX.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function submitFormAjax() {
    $contextForm = $this->contextManager->getForm($this->context, 'edit');

    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new ReplaceCommand('#context-conditions', $contextForm['conditions']));

    return $response;
  }

}
