<?php

namespace Drupal\metatag_routes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Routing\AdminContext;

/**
 * Form for creating custom definitions.
 *
 * @package Drupal\metatag_routes\Form
 */
class MetatagCustomCreateForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\RouteProvider definition.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Drupal\Core\Routing\AdminContext definition.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteProvider $route_provider,
    PathValidator $path_validator,
    AdminContext $admin_context
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
    $this->pathValidator = $path_validator;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.admin_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_custom_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['metatag_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route / Path'),
      '#description' => $this->t('Enter the route (path) for this new configuration, starting with a leading slash.<br />Note: this must already exist as a path in Drupal.'),
      '#maxlength' => 200,
      '#required' => TRUE,
    ];

    $form['route_name'] = [
      '#type' => 'hidden',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Get the path given by the user.
    $url = trim($form_state->getValue('metatag_url'));

    // Validate the url format.
    if (strpos($url, '/') === FALSE) {
      $form_state->setErrorByName('metatag_url', $this->t('The path must begin with /'));
      return FALSE;
    }

    // Get route name from path.
    $url_object = $this->pathValidator->getUrlIfValid($url);
    if ($url_object) {
      $route_name = $url_object->getRouteName();
      $route_object = $this->routeProvider->getrouteByName($route_name);
      // Avoid administrative routes to have metatags.
      if ($this->adminContext->isAdminRoute($route_object)) {
        $form_state->setErrorByName('metatag_url',
          $this->t('The admin routes should not have metatags.'));
        return FALSE;
      }

      // Avoid including entity routes.
      $params = $url_object->getRouteParameters();
      $entity_type = !empty($params) ? key($params) : NULL;
      $entity_types = ['node', 'taxonomy_term', 'user'];
      if (isset($entity_type) && in_array($entity_type, $entity_types)) {
        $form_state->setErrorByName('metatag_url',
          $this->t('The entities routes metatags must be added by fields. @entity_type - @id', [
            '@entity_type' => $entity_type,
            '@id' => $params[$entity_type],
          ]));
        return FALSE;
      }

      // Validate that the route doesn't have metatags created already.
      $ids = $this->entityTypeManager->getStorage('metatag_defaults')->getQuery()->condition('id', $route_name)->execute();
      if ($ids) {
        $form_state->setErrorByName('metatag_url',
          $this->t('There are already metatags created for this route.'));
        return FALSE;
      }
      $form_state->setValue('route_name', $route_name);
    }
    else {
      $form_state->setErrorByName('metatag_url', $this->t('The path does not exist as an internal Drupal route.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values for form submission.
    $route_name = $form_state->getValue('route_name');
    $url = $form_state->getValue('metatag_url');
    if ($route_name && $url) {
      // Create the new metatag entity.
      $entity = $this->entityTypeManager->getStorage('metatag_defaults')->create([
        'id' => $route_name,
        'label' => $url,
      ]);
      $entity->save();
      $this->messenger()->addStatus($this->t('Created metatags for the path: @url. Internal route: @route.', [
        '@url' => $url,
        '@route' => $route_name,
      ]));

      // Redirect to metatag edit page.
      $form_state->setRedirect('entity.metatag_defaults.edit_form', [
        'metatag_defaults' => $route_name,
      ]);
    }
    else {
      $this->messenger()->addError($this->t('The metatags could not be created for the path: @url.', [
        '@url' => $url,
      ]));

      // Redirect to metatag edit page.
      $form_state->setRedirect('entity.metatag_defaults.collection');
    }
  }

}
