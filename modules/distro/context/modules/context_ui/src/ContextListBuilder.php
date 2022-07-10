<?php

namespace Drupal\context_ui;

use Drupal\context\ContextManager;
use Drupal\context\Entity\Context;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormInterface;
use Drupal\context\Form\AjaxFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a class to crate the Context List.
 */
class ContextListBuilder extends ConfigEntityListBuilder implements FormInterface {

  use AjaxFormTrait;

  /**
   * The Context modules context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ContextListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context module context manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The Drupal form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    ContextManager $contextManager,
    FormBuilderInterface $formBuilder,
    MessengerInterface $messenger
  ) {
    parent::__construct($entity_type, $storage);

    $this->contextManager = $contextManager;
    $this->formBuilder = $formBuilder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('context.manager'),
      $container->get('form_builder'),
      $container->get('messenger')
    );
  }

  /**
   * Use a form instead of the entity list builder to display contexts.
   *
   * {@inheritdoc}
   */
  public function render() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_ui_admin_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $groups = $this->contextManager->getContextsByGroup();

    $form['contexts'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Context'),
        $this->t('Description'),
        $this->t('Group'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no contexts defined.'),
      '#attributes' => [
        'id' => 'contexts',
      ],
    ];

    $group_options = [];

    // @todo Make this a bit prettier.
    foreach ($groups as $group => $contexts) {
      $group_options[$group] = ($group === 'not_grouped') ? $this->t('Not grouped') : $group;
    }

    // Count the number of entities to get a good delta for the weights.
    $weight_delta = round(count($this->getEntityIds()) / 2);

    foreach ($groups as $group => $contexts) {
      $group_class = Html::getClass($group);

      $form['contexts']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'context-group-select',
        'subgroup' => 'context-group-' . $group_class,
        'hidden' => FALSE,
      ];

      $form['contexts']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'context-weight',
        'subgroup' => 'context-weight-' . $group_class,
      ];

      $form['contexts']['group-' . $group_class] = [
        '#attributes' => [
          'class' => ['group-label', 'group-label-' . $group_class],
          'no_striping' => TRUE,
        ],
      ];

      $form['contexts']['group-' . $group_class] = [
        '#attributes' => [
          'class' => ['region-title'],
        ],
        'title' => [
          '#markup' => ($group === 'not_grouped') ? $this->t('Not grouped') : $group,
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ],
      ];

      /** @var \Drupal\context\ContextInterface $context */
      foreach ($contexts as $context_id => $context) {
        $operations = [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => $context->toUrl('edit-form'),
          ],
          'duplicate' => [
            'title' => $this->t('Duplicate'),
            'url' => $context->toUrl('duplicate-form'),
            'attributes' => $this->getAjaxAttributes(),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => $context->toUrl('delete-form'),
            'attributes' => $this->getAjaxAttributes(),
          ],
          'disable' => [
            'title' => $context->disabled() ? $this->t('Enable') : $this->t('Disable'),
            'url' => $context->toUrl('disable-form'),
            'attributes' => $this->getAjaxAttributes(),
          ],
        ];

        $form['contexts'][$context_id] = [
          '#attributes' => [
            'class' => ['draggable'],
          ],
          'label' => [
            '#markup' => $context->getLabel(),
            '#wrapper_attributes' => $context->disabled() ? ['style' => 'opacity:0.6'] : NULL,
          ],
          'description' => [
            '#markup' => $context->getDescription(),
          ],
          'group' => [
            '#type' => 'select',
            '#title' => $this->t('Group for @context context', ['@context' => $context->getLabel()]),
            '#title_display' => 'invisible',
            '#default_value' => $context->getGroup(),
            '#options' => $group_options,
            '#attributes' => [
              'class' => ['context-group-select', 'context-group-' . $group_class],
            ],
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @context context', ['@context' => $context->getLabel()]),
            '#default_value' => $context->getWeight(),
            '#delta' => $weight_delta,
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['context-weight', 'context-weight-' . $group_class],
            ],
          ],
          'operations' => [
            '#type' => 'operations',
            '#links' => $operations,
          ],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if (count($groups) > 0) {
      $form['actions']['submit'] = [
        '#type'        => 'submit',
        '#value'       => $this->t('Save contexts'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $contexts = $this->storage->loadMultiple(array_keys($form_state->getValue('contexts')));

    /*** @var \Drupal\context\ContextInterface $context */
    foreach ($contexts as $context_id => $context) {
      $context_values = $form_state->getValue(['contexts', $context_id]);

      $context->setWeight($context_values['weight']);

      // Not grouped contexts needs a specific group value.
      if ($context_values['group'] === 'not_grouped') {
        $context->setGroup(Context::CONTEXT_GROUP_NONE);
      }
      else {
        $context->setGroup($context_values['group']);
      }

      $context->save();
    }

    $this->messenger->addMessage($this->t('The context settings have been updated.'));
  }

}
