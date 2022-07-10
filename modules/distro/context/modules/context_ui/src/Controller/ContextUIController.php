<?php

namespace Drupal\context_ui\Controller;

use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\context\ContextManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\context\ContextInterface;
use Drupal\context\ContextReactionManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Plugin\Exception\PluginException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Context UI Controller.
 */
class ContextUIController extends ControllerBase {

  /**
   * The context reaction manager.
   *
   * @var \Drupal\context\ContextReactionManager
   */
  protected $contextReactionManager;

  /**
   * The Context module context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The Drupal core condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Construct a new context controller.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context module context manager.
   * @param \Drupal\context\ContextReactionManager $contextReactionManager
   *   The Context module context reaction plugin manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The Drupal core condition manager.
   */
  public function __construct(
    ContextManager $contextManager,
    ContextReactionManager $contextReactionManager,
    ConditionManager $conditionManager
  ) {
    $this->contextManager = $contextManager;
    $this->contextReactionManager = $contextReactionManager;
    $this->conditionManager = $conditionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.manager'),
      $container->get('plugin.manager.context_reaction'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * Retrieves group suggestions for a context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with groups matching the query.
   */
  public function groupsAutocomplete(Request $request) {
    $query = $request->query->get('q');

    $matches = [];

    foreach ($this->contextManager->getContexts() as $context) {
      if (stripos($context->getGroup(), $query) === 0) {
        $matches[] = $context->getGroup();
      }
    }

    $response = [];

    // Format the unique matches to be used with the autocomplete field.
    foreach (array_unique($matches) as $match) {
      $response[] = [
        'value' => $match,
        'label' => Html::escape($match),
      ];
    }

    return new JsonResponse($response);
  }

  /**
   * Displays a list of conditions that can be added to the context.
   *
   * @param \Drupal\context\ContextInterface $context
   *   The context to display available conditions for.
   *
   * @return array
   *   An array with the build information.
   */
  public function listConditions(ContextInterface $context) {

    // Get a list of all available conditions.
    $conditions = $this->conditionManager->getDefinitions();

    $header = [
      $this->t('Condition'),
    ];

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by condition name'),
      '#attributes' => [
        'class' => ['context-table-filter'],
        'data-element' => '.condition-add-table',
        'title' => $this->t('Enter a part of the condition name to filter by.'),
      ],
    ];

    $rows = [];

    // Add a table row for each condition.
    foreach ($conditions as $condition_id => $condition) {
      // Only add the condition to the list of it's not already been added to
      // the context.
      if ($context->hasCondition($condition_id)) {
        continue;
      }

      $rows[] = [
        'condition' => [
          'data' => [
            '#type' => 'link',
            '#title' => $condition['label'] . ' (' . $condition['provider'] . ')',
            '#url' => Url::fromRoute('context.condition_add', [
              'context' => $context->id(),
              'condition_id' => $condition_id,
            ]),
            '#attributes' => [
              'class' => ['use-ajax', 'context-table-filter-text-source'],
            ],
            '#options' => [
              'html' => TRUE,
            ],
          ],
        ],
      ];
    }

    $build['conditions'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no conditions left that can be added to this context.'),
      '#attributes' => [
        'class' => ['condition-add-table'],
      ],
    ];

    $build['#attached']['library'][] = 'context_ui/admin';

    return $build;
  }

  /**
   * Displays a list of reactions that can be added to the context.
   *
   * @param \Drupal\context\ContextInterface $context
   *   The context to display available.
   *
   * @return array
   *   An array with the build information.
   */
  public function listReactions(ContextInterface $context) {

    // Get a list of all available conditions.
    $reactions = $this->contextReactionManager->getDefinitions();

    $header = [
      $this->t('Reactions'),
    ];

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by reaction name'),
      '#attributes' => [
        'class' => ['context-table-filter'],
        'data-element' => '.reaction-add-table',
        'title' => $this->t('Enter a part of the reaction name to filter by.'),
      ],
    ];

    $rows = [];

    // Add a table row for each context reaction.
    foreach ($reactions as $reaction_id => $reaction) {
      // Only add the reaction to the list of it's not already been added to
      // the context.
      if ($context->hasReaction($reaction_id)) {
        continue;
      }

      $rows[] = [
        'reaction' => [
          'data' => [
            '#type' => 'link',
            '#title' => $reaction['label'],
            '#url' => Url::fromRoute('context.reaction_add', [
              'context' => $context->id(),
              'reaction_id' => $reaction_id,
            ]),
            '#attributes' => [
              'class' => ['use-ajax', 'context-table-filter-text-source'],
            ],
            '#options' => [
              'html' => TRUE,
            ],
          ],
        ],
      ];
    }

    $build['reactions'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no reactions left that can be added to this context.'),
      '#attributes' => [
        'class' => ['reaction-add-table'],
      ],
    ];

    $build['#attached']['library'][] = 'context_ui/admin';

    return $build;
  }

  /**
   * Add the specified reaction to the context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\context\ContextInterface $context
   *   The context to add the reaction to.
   * @param string $reaction_id
   *   The ID of the reaction to add.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|RedirectResponse
   *   An AJAX response or a redirect response.
   */
  public function addReaction(Request $request, ContextInterface $context, $reaction_id) {

    if ($context->hasReaction($reaction_id)) {
      throw new HttpException(403, 'The specified reaction had already been added to the context.');
    }

    // Create an instance of the reaction and add it to the context.
    try {
      $reaction = $this->contextReactionManager->createInstance($reaction_id);
    }
    catch (PluginException $e) {
      throw new HttpException(400, $e->getMessage());
    }

    // If one of the condition is "Current theme",
    // prevent adding Theme reaction.
    // Else this will cause an infinite loop
    // when checking for active contexts.
    if ($reaction_id == 'theme') {
      $conditions = $context->getConditions();
      foreach ($conditions as $condition) {
        if ($condition->getPluginId() == 'current_theme') {
          if ($request->isXmlHttpRequest()) {
            $response = new AjaxResponse();

            $response->addCommand(new CloseModalDialogCommand());
            $response->addCommand(new OpenModalDialogCommand($this->t("Theme reaction"), $this->t("You can not place Theme reaction if Current theme condition is set."), ['width' => '700']));
            return $response;
          }
        }
      }
    }

    $context->addReaction($reaction->getConfiguration());
    $context->save();

    // If the request is an AJAX request then return an AJAX response with
    // commands to replace the content on the page.
    if ($request->isXmlHttpRequest()) {
      $response = new AjaxResponse();

      $contextForm = $this->contextManager->getForm($context, 'edit');

      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new ReplaceCommand('#context-reactions', $contextForm['reactions']));

      return $response;
    }

    $url = $context->toUrl('edit-form');

    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Add the specified condition to the context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\context\ContextInterface $context
   *   The context to add the condition to.
   * @param string $condition_id
   *   The ID of the condition to add.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|RedirectResponse
   *   An AJAX response or A redirect response.
   */
  public function addCondition(Request $request, ContextInterface $context, $condition_id) {

    if ($context->hasCondition($condition_id)) {
      throw new HttpException(403, 'The specified condition had already been added to the context.');
    }

    // Create an instance of the condition and add it to the context.
    try {
      $condition = $this->conditionManager->createInstance($condition_id);
    }
    catch (PluginException $e) {
      throw new HttpException(400, $e->getMessage());
    }

    // Prevent adding "Current theme" condition,
    // if "Theme" reaction is already set.
    // Else this will cause an infinite loop when checking for active contexts.
    if ($condition_id == 'current_theme') {
      $reactions = $context->getReactions();
      foreach ($reactions as $reaction) {
        if ($reaction->getPluginId() == 'theme') {
          if ($request->isXmlHttpRequest()) {
            $response = new AjaxResponse();

            $response->addCommand(new CloseModalDialogCommand());
            $response->addCommand(new OpenModalDialogCommand($this->t("Current theme condition"), $this->t("You can not set Current theme condition if Theme reaction is set."), ['width' => '700']));
            return $response;
          }
        }
      }
    }

    $context->addCondition($condition->getConfiguration());
    $context->save();

    // If the request is an AJAX request then return an AJAX response with
    // commands to replace the content on the page.
    if ($request->isXmlHttpRequest()) {
      $response = new AjaxResponse();

      $contextForm = $this->contextManager->getForm($context, 'edit');

      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new ReplaceCommand('#context-conditions', $contextForm['conditions']));

      return $response;
    }

    $url = $context->toUrl('edit-form');

    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

}
