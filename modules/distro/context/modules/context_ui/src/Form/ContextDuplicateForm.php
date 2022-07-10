<?php

namespace Drupal\context_ui\Form;

use Drupal\context\ContextManager;
use Drupal\Core\Render\Element\MachineName;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for duplicating Context.
 */
class ContextDuplicateForm extends ContextFormBase {

  /**
   * Context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * Constructor.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   Context manager.
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
  public function getQuestion() {
    return $this->t('Are you sure you want to duplicate the %label context?', [
      '%label' => $this->entity->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will duplicate the %label context.', [
      '%label' => $this->entity->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.context.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General details'),
    ];

    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->t('Duplicate of @label', ['@label' => $this->entity->getLabel()]),
      '#required' => TRUE,
      '#description' => $this->t('Enter label for this context.'),
    ];

    $form['general']['name'] = [
      '#type' => 'machine_name',
      '#default_value' => '',
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'contextExists'],
      ],
    ];

    $form['general']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('Enter a description for this context definition.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Duplicate',
    ];

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    MachineName::validateMachineName($form["general"]["name"], $formState, $form);
    $this->entity->duplicate($form["general"]["label"]["#value"], $form["general"]["name"]["#value"], $form["general"]["description"]["#value"]);
    $this->messenger()->addMessage($this->t('The context %title has been duplicated.', [
      '%title' => $this->entity->getLabel(),
    ]));
    $formState->setRedirectUrl($this->getCancelUrl());
  }

}
