<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Views' condition.
 *
 * @Condition(
 *   id = "view_inclusion",
 *   label = @Translation("View inclusion")
 * )
 */
class ViewInclusion extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * View constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    $options = [];
    foreach ($views as $key => $view) {
      foreach ($view->get('display') as $display) {
        if ($display['display_plugin'] === 'page') {
          $viewRoute = 'view-' . $key . '-' . $display['id'];
          $options[$viewRoute] = $view->label() . ' - ' . $display['display_title'];
        }
      }
    }

    $configuration = $this->getConfiguration();

    $form['views_pages'] = [
      '#title' => $this->t('Views pages'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => isset($configuration['view_inclusion']) && !empty($configuration['view_inclusion']) ? array_keys($configuration['view_inclusion']) : [],
    ];

    $form = parent::buildConfigurationForm($form, $form_state);
    // Hide the negate checkbox.
    $form['negate']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['view_inclusion' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['view_inclusion'] = array_filter($form_state->getValue('views_pages'));
    // Defaults negation to FALSE to match the defaultConfiguration.
    $this->configuration['negate'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (empty($this->configuration['view_inclusion'])) {
      return $this->t('No views page is selected.');
    }

    return $this->t(
      'Return true on the following views pages: @pages',
      [
        '@pages' => str_replace(
          '-',
          '.',
          implode(', ', $this->configuration['view_inclusion'])
        ),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['view_inclusion'])) {
      // Return TRUE if empty.
      return TRUE;
    }

    $route = str_replace('.', '-', $this->currentRouteMatch->getRouteName());

    return in_array($route, $this->configuration['view_inclusion'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    // $contexts[] = 'config:view_list';
    return $contexts;
  }

}
