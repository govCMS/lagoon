<?php

namespace Drupal\govcms_akamai_purge\Form;

use GuzzleHttp\Client;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Contains the path purge form.
 */
class PathPurgeForm extends ConfigFormBase {

  /**
   * Settings name.fasdfa
   *
   * @var string
   */
  const SETTINGS = 'govcms_akamai_purge.paths';

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The HTTP client to make a request.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $stack, Client $client) {
    $this->requestStack = $stack;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('request_stack'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govcms_akamai_purge_paths_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Retrieve the purge host.
   *
   * Attempt to look up environment variables for the configured akamai purge
   * hostname this is used to send a FQDN+path request to the purge intermediary
   * service.
   *
   * @return string
   *   Scheme and hostname for the project.
   */
  public function getHttpHost() {
    $registered_lagoon_routes = explode(',', getenv('LAGOON_ROUTES'));
    $akamai_purge_host = getenv('AKAMAI_PURGE_HOST');

    // Allow environment variable override for the purge host so this can
    // be controlled by operators. Akamai will reject requests if the
    // domain is not known, so this will allow definitions of the
    // purge domain when it cannot be correctly inferred.
    if (!empty($akamai_purge_host)) {
      return $akamai_purge_host;
    }

    foreach ($registered_lagoon_routes as $route) {
      if (strpos($route, 'amazee.io') !== FALSE) {
        continue;
      }

      if (strpos($route, 'govcms.gov.au') !== FALSE) {
        continue;
      }

      $akamai_purge_host = $route;
    }

    return empty($akamai_purge_host) ? $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() : $akamai_purge_host;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $purge_service_hostname = getenv('AKAMAI_PURGE_SERVICE_HOSTNAME');
    $purge_service_port = getenv('AKAMAI_PURGE_SERVICE_PORT');
    $purge_service_scheme = getenv('AKAMAI_PURGE_SERVICE_SCHEME');
    $error = FALSE;

    if (
      empty($purge_service_hostname) ||
      empty($purge_service_port) ||
      empty($purge_service_scheme)
    ) {
      \Drupal::messenger()->addError(t('GovCMS Akamai purge is not configured correctly, please contact support to restore functionality.'));
      $error = TRUE;
    }

    $form['path_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path list'),
      '#description' => $this->t('Please provide a list of URLs that will be invalidated from Akamai\'s edge cache. You need to provide URL paths separated by newlines in the textarea above, the URL paths should not contain the domain'),
      '#disabled' => $error,
      '#required' => TRUE,
    ];


    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Purge');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $paths = $form_state->getValue('path_list');
    $paths = explode(PHP_EOL, $paths);
    $paths = array_filter($paths);
    $paths = array_map('trim', $paths);

    $invalid_paths = [];
    $basename = $this->getHttpHost();

    foreach ($paths as &$path) {
      if (filter_var($path, FILTER_VALIDATE_URL)) {
        // Only accept paths - this will validate if full URLs are provided.
        $invalid_paths[] = $path;
        continue;
      }
      $path = '/' . ltrim($path, '/');
      $path = filter_var($path, FILTER_SANITIZE_URL);

      if (!filter_var($basename . $path, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        $invalid_paths[] = $path;
      }
    }

    if (count($invalid_paths) > 0) {
      $form_state->setErrorByName('path_list', 'Please review and update as expected.');
      $this->messenger()->addError($this->formatPlural(
        count($invalid_paths),
        'The path that you have submitted is not in the correct format',
        'The paths that you have submitted are not in the correct format'
      ));
      foreach ($invalid_paths as $path) {
        $this->messenger()->addError(" - $path");
      }

      return;
    }

    if (count($paths) > 200) {
      $form_state->setErrorByName('path_list', 'Unable to process more than 200 paths in a single request.');
      return;
    }

    $form_state->setValue('path_list', implode(PHP_EOL, $paths));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paths = $form_state->getValue('path_list');
    $paths = explode(PHP_EOL, $paths);

    $purge_service_hostname = getenv('AKAMAI_PURGE_SERVICE_HOSTNAME');
    $purge_service_port = getenv('AKAMAI_PURGE_SERVICE_PORT');
    $purge_service_scheme = getenv('AKAMAI_PURGE_SERVICE_SCHEME');

    $opt = [
      'verify' => FALSE,
      'headers' => [
        'X-Purge-Token' => getenv('AKAMAI_PURGE_TOKEN'),
        'X-Lagoon-Project' => getenv('LAGOON_PROJECT'),
        'Referer' => $this->getHttpHost(),
      ],
      'json' => ['paths' => $paths],
    ];

    try {
      $this->client->request(
        'POST',
        "$purge_service_hostname:$purge_service_port/purge/path",
        $opt
      );
    }
    catch (ClientException $e) {
      $this->messenger()->addError('Unable to purge paths at this time, please contact support.');
      if (is_callable([$e, 'getResponse'])) {
        $response = json_decode($e->getResponse()->getBody(), TRUE);
        $this->getLogger('govcms_akamai_purge')->error('client exception: ' . $response['reason']);
      }
      return;
    }
    catch (\Exception $e) {
      $this->messenger()->addError('Unable to purge paths at this time, please contact support.');
      $this->getLogger('govcms_akamai_purge')->error('path purge error: ' . $e->getMessage());
      return;
    }

    $this->messenger()->addStatus('Your request has successfully been sent for processing. Please allow 10 minutes for the request to be actioned.');
  }

}
