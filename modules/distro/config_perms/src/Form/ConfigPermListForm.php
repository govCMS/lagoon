<?php

namespace Drupal\config_perms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\config_perms\Entity\CustomPermsEntity;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigPermListForm.
 *
 * @package Drupal\config_perms\Form
 */
class ConfigPermListForm extends FormBase {

  /**
   * Router Provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routerProvider;

  /**
   * Router Builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilder
   */
  protected $routerBuilder;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $router_provider
   *   The router provider service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(RouteProviderInterface $router_provider, RouteBuilderInterface $router_builder) {
    $this->routerProvider = $router_provider;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('router.route_provider'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_perm_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['perms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Permissions'),
      '#description' => '<p>' . $this->t("Please note that the order in which permissions are granted are as follows:") . '</p>' .
      "<ul>
       <li>" . $this->t("Custom permissions only support routes") . "</li>\n
       <li>" . $this->t("User 1 still maintains full control") . "</li>\n
       <li>" . $this->t("Remove the permission 'Administer site configuration' from roles you wish to give access to only specified custom site configuration permissions") . "</li>\n
      </ul>",
      '#collapsible' => 1,
      '#collapsed' => 0,
    ];

    $perms = CustomPermsEntity::loadMultiple();

    $header = [
      $this->t('Enabled'),
      $this->t('Name'),
      $this->t('Route(s)'),
      '',
      '',
    ];

    $form['perms']['local'] = [
      '#type' => 'table',
      '#header' => $header,
      '#prefix' => '<div id="config_perms-wrapper">',
      '#suffix' => '</div>',
    ];

    /** @var \Drupal\config_perms\Entity\CustomPermsEntity $perm */
    foreach ($perms as $key => $perm) {

      $form['perms']['local'][$key] = ['#tree' => TRUE];

      $form['perms']['local'][$key]['status'] = [
        '#type' => 'checkbox',
        '#default_value' => $perm->status(),
      ];

      $form['perms']['local'][$key]['name'] = [
        '#type' => 'textfield',
        '#default_value' => $perm->label(),
        '#size' => 30,
      ];

      $form['perms']['local'][$key]['route'] = [
        '#type' => 'textarea',
        '#default_value' => $perm->getRoute(),
        '#size' => 50,
        '#rows' => 1,
      ];

      // Delete link.
      $delete_link = $perm->toLink($this->t('Delete'), 'delete-form');
      $form['perms']['local'][$key]['delete'] = $delete_link->toRenderable();
      $form['perms']['local'][$key]['id'] = [
        '#type' => 'hidden',
        '#default_value' => $perm->id(),
      ];
    }

    $num_new = $form_state->getValue('num_new');
    if (empty($num_new)) {
      $form_state->setValue('num_new', '0');
    }

    for ($i = 0; $i < $form_state->getValue('num_new'); $i++) {
      $form['perms']['local']['new']['status'] = [
        '#type' => 'checkbox',
        '#default_value' => '',
      ];
      $form['perms']['local']['new']['name'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#size' => 30,
      ];

      $form['perms']['local']['new']['route'] = [
        '#type' => 'textarea',
        '#default_value' => '',
        '#rows' => 2,
        '#size' => 50,
      ];

    }

    $form['perms']['add']['status'] = [
      '#name' => 'status',
      '#id' => 'edit-local-status',
      '#type' => 'submit',
      '#value' => $this->t('Add permission'),
      '#submit' => ['::configPermsAdminFormAddSubmit'],
      '#ajax' => [
        'callback' => '::configPermsAdminFormAddCallback',
        'wrapper' => 'config_perms-wrapper',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Callback for add button.
   */
  public function configPermsAdminFormAddCallback($form, $form_state) {
    return $form['perms']['local'];
  }

  /**
   * Submit for add button.
   */
  public function configPermsAdminFormAddSubmit($form, &$form_state) {
    $form_state->setValue('num_new', $form_state->getValue('num_new') + 1);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $perms = CustomPermsEntity::loadMultiple();

    foreach ($values['local'] as $key => $perm) {

      if (empty($perm['name']) && empty($perm['route']) && $key != 'new') {
        $entity = CustomPermsEntity::load($perm['id']);
        $entity->delete();
      }
      else {
        if (empty($perm['name'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("The name cannot be empty."));
        }

        if (empty($perm['route'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("The route cannot be empty."));
        }
        if (array_key_exists($this->configPermsGenerateMachineName($perm['name']), $perms) && !isset($perm['id'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("A permission with that name already exists."));
        }
        if (!empty($perm['route'])) {
          $routes = config_perms_parse_path($perm['route']);
          foreach ($routes as $route) {
            if (count($this->routerProvider->getRoutesByNames([$route])) < 1) {
              $form_state->setErrorByName("local][" . $key . "", $this->t("The route @route is invalid.", ['@route' => $perm['route']]));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $perms = CustomPermsEntity::loadMultiple();

    foreach ($values['local'] as $key => $data) {
      // If new permission.
      if ($key == 'new') {
        $entity = CustomPermsEntity::create();
        $entity->set('id', $this->configPermsGenerateMachineName($data['name']));
      }
      else {
        // Update || Insert.
        $entity = $perms[$data['id']];
      }
      $entity->set('label', $data['name']);
      $entity->set('route', $data['route']);
      $entity->set('status', $data['status']);
      $entity->save();
    }

    $this->routerBuilder->rebuild();
    $this->messenger()->addMessage($this->t('The permissions have been saved.'));
  }

  /**
   * Generate a machine name given a string.
   */
  public function configPermsGenerateMachineName($string) {
    return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $string));
  }

}
