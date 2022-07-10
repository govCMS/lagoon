<?php

namespace Drupal\context_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormState;
use Drupal\context\Form\AjaxFormTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to edit context.
 */
class ContextEditForm extends ContextFormBase {

  use AjaxFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Store contexts on the form state so that plugins can use these values
    // when building their forms.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['require_all_conditions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require all conditions'),
      '#description' => $this->t('If checked, all conditions must be met for this context to be active. Otherwise, the first condition that is met will activate this context.'),
      '#default_value' => $this->entity->requiresAllConditions(),
    ];

    $form['conditions'] = [
      '#prefix' => '<div id="context-conditions">',
      '#suffix' => '</div>',
      '#markup' => '<h3>' . $this->t('Conditions') . '</h3>',
      '#tree' => TRUE,
      '#process' => [
        [$this, 'processConditions'],
      ],
    ];

    $form['reactions'] = [
      '#prefix' => '<div id="context-reactions">',
      '#suffix' => '</div>',
      '#markup' => '<h3>' . $this->t('Reactions') . '</h3>',
      '#tree' => TRUE,
      '#process' => [
        [$this, 'processReactions'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $status = parent::save($form, $formState);

    if ($status) {
      $this->messenger()->addMessage($this->t('The context %label has been saved.', [
        '%label' => $this->entity->getLabel(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The context was not saved.'));
    }

  }

  /**
   * Process function for the conditions.
   *
   * @param array $element
   *   The element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array with the condition element.
   */
  public function processConditions(array &$element, FormStateInterface $form_state) {
    $conditions = $this->entity->getConditions();

    $element['add_condition'] = [
      '#type' => 'link',
      '#title' => $this->t('Add condition'),
      '#url' => Url::fromRoute('context.conditions_library', [
        'context' => $this->entity->id(),
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax', 'button', 'button--small',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    if (!count($conditions)) {
      $element['reactions']['empty'] = [
        '#type' => 'container',
        '#markup' => $this->t('No conditions has been added. When there are no added conditions the context will be considered sitewide.'),
      ];
    }

    $element['condition_tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => ['condition_tabs'],
    ];

    foreach ($conditions as $condition_id => $condition) {
      $element['condition-' . $condition_id] = [
        '#type' => 'details',
        '#title' => $condition->getPluginDefinition()['label'],
        '#group' => 'condition_tabs',
      ];

      $element['condition-' . $condition_id]['options'] = $condition->buildConfigurationForm([], $form_state);
      $element['condition-' . $condition_id]['options']['#parents'] = ['conditions', $condition_id];

      $element['condition-' . $condition_id]['remove'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove condition'),
        '#url' => Url::fromRoute('context.condition_delete', [
          'context' => $this->entity->id(),
          'condition_id' => $condition_id,
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax', 'button', 'button--small',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];
    }

    return $element;
  }

  /**
   * Process function for the reactions.
   *
   * @param array $element
   *   The element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array with the reaction element.
   */
  public function processReactions(array &$element, FormStateInterface $form_state) {
    $reactions = $this->entity->getReactions();

    $element['add_reaction'] = [
      '#type' => 'link',
      '#title' => $this->t('Add reaction'),
      '#url' => Url::fromRoute('context.reactions_library', [
        'context' => $this->entity->id(),
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax', 'button', 'button--small',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    if (!count($reactions)) {
      $element['empty'] = [
        '#type' => 'container',
        '#markup' => $this->t('No reactions has been added.'),
      ];
    }

    $element['reaction_tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => ['reaction_tabs'],
    ];

    foreach ($reactions as $reaction_id => $reaction) {
      $element['reaction-' . $reaction_id] = [
        '#type' => 'details',
        '#title' => $reaction->getPluginDefinition()['label'],
        '#group' => 'reaction_tabs',
      ];

      $reaction_values = $form_state->getValue(['reactions', $reaction_id], []);
      $reaction_state = (new FormState())->setValues($reaction_values);

      $element['reaction-' . $reaction_id]['options'] = $reaction->buildConfigurationForm([], $reaction_state, $this->entity);
      $element['reaction-' . $reaction_id]['options']['#parents'] = ['reactions', $reaction_id];

      $element['reaction-' . $reaction_id]['remove'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove reaction'),
        '#url' => Url::fromRoute('context.reaction_delete', [
          'context' => $this->entity->id(),
          'reaction_id' => $reaction_id,
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax', 'button', 'button--small',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];
    }

    return $element;
  }

  /**
   * Actions function.
   *
   * @param array $form
   *   Form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return mixed
   *   Return mixed object.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    $element['submit'] = [
      '#type' => 'submit',
      '#dropbutton' => 'save',
      '#value' => t('Save and continue'),
      '#submit' => ['::submitForm', '::save'],
    ];
    $element['submit_exit'] = [
      '#type' => 'submit',
      '#dropbutton' => 'save',
      '#value' => t('Save and exit'),
      '#submit' => ['::submitForm', '::save', '::collection'],
    ];

    return $element;
  }

  /**
   * Collection function makes redirection to route name.
   *
   * @param array $form
   *   Form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   From state object.
   */
  public function collection(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.context.collection');

  }

}
