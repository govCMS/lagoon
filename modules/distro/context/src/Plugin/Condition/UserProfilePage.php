<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a 'User profile page status' condition.
 *
 * @Condition(
 *   id = "user_status",
 *   label = @Translation("User profile pages"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User")),
 *   }
 * )
 */
class UserProfilePage extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Service current_route_match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Service entity_field.manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * Service current_user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;

  /**
   * UserProfilePage constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $currentRouteMatch, EntityFieldManager $entityFieldManager, AccountProxy $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
    $this->entityFieldManager = $entityFieldManager;
    $this->currentUser = $currentUser;
  }

  /**
   * UserProfilePage create function.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_field.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $user_fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $ufields = [];
    foreach ($user_fields as $field_key => $field_value) {
      $ufields[$field_key] = $field_key;
    }
    $configuration = $this->getConfiguration();
    $options = [
      'viewing_profile' => $this->t('Viewing any user profile.'),
      'logged_viewing_profile' => $this->t('Logged in and viewing any user profile.'),
      'own_page_true' => $this->t('User viewing own profile.'),
      'field_value' => $this->t('Has a value in selected user field'),
    ];
    $form['user_status'] = [
      '#title' => $this->t('User status'),
      '#description' => 'If nothing is checked, the evaluation will return TRUE. If more than one option is checked, the evaluation will return TRUE if any of the options matches the condition.',
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => isset($configuration['user_status']) ? $configuration['user_status'] : [],
    ];

    $form['user_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('User field'),
      '#options' => $ufields,
      '#default_value' => isset($configuration['user_fields']) ? $configuration['user_fields'] : FALSE,
      '#states' => [
        // Show this field only if the 'field_value' is selected above.
        'visible' => [
          ':input[name*="[user_status][user_status][field_value]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildConfigurationForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user_status' => [],
      'user_fields' => 'uid',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['user_status'] = $form_state->getValue('user_status');
    $this->configuration['user_fields'] = $form_state->getValue('user_fields');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (empty($this->configuration['user_status'])) {
      return $this->t('No user status field is selected.');
    }
    return t('Select user profile page status');

  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $route = $this->currentRouteMatch->getCurrentRouteMatch();
    $configuration = $this->getConfiguration();
    // Check if no option is checked.
    if (empty($configuration['user_status']) || !array_filter($configuration['user_status'])) {
      return TRUE;
    }
    else {
      foreach ($configuration['user_status'] as $key => $value) {
        if (empty($value)) {
          unset($configuration['user_status'][$key]);
        }
      }
      $user_conf = $configuration['user_status'];
    }

    // Match all entity.user.* routes having user parameter,
    // which include regular user profile view (entity.user.canonical),
    // user edit form (entity.user.edit_form),...
    if (strpos($route->getRouteName(), 'entity.user.') === 0) {
      $user_id = $this->currentRouteMatch->getRawParameter('user');
      if ($user_id === NULL) {
        return FALSE;
      }

      if (in_array("viewing_profile", $user_conf)) {
        return TRUE;
      }
      else if (in_array("logged_viewing_profile", $user_conf) && $this->currentUser->isAuthenticated()) {
        return TRUE;
      }
      else if (in_array("own_page_true", $user_conf) && $this->currentUser->isAuthenticated() && $user_id == $this->currentUser->id()) {
        return TRUE;
      }
      else if (in_array("field_value", $user_conf)) {
        $user = User::load($user_id);
        // Check if field is entity_reference or normal field with values.
        $field_target = $user->get($configuration['user_fields'])->target_id;
        if ($field_target) {
          $field = $field_target;
        }
        else {
          $field = $user->get($configuration['user_fields'])->value;
        }
        // Condition check.
        if ($field || !$field == 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
