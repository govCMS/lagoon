<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a 'Http status code' condition.
 *
 * @Condition(
 *   id = "http_status_code",
 *   label = @Translation("Http status code"),
 * )
 */
class HttpStatusCode extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a HttpStatusCode condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['status_codes'] = [
      '#title' => $this->t('Http status codes'),
      '#type' => 'checkboxes',
      '#options' => [
        '200' => $this->t('200 - OK'),
        '403' => $this->t('403 - Access denied'),
        '404' => $this->t('404 - Page not found'),
      ],
      '#default_value' => $this->configuration['status_codes'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['status_codes'] = array_filter($form_state->getValue('status_codes'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (empty($this->configuration['status_codes'])) {
      return $this->t('The http status code is not specified');
    }

    if (count($this->configuration['status_codes']) > 1) {
      $status_codes = $this->configuration['status_codes'];
      $last = array_pop($status_codes);
      $status_codes = implode(', ', $status_codes);
      if ($this->isNegated()) {
        return $this->t(
          'The http status code is not @status_codes or @last',
          ['@status_codes' => $status_codes, '@last' => $last]
        );
      }

      return $this->t(
        'The http status code is @status_codes or @last',
        ['@status_codes' => $status_codes, '@last' => $last]
      );
    }
    $status_code = reset($this->configuration['status_codes']);

    if ($this->isNegated()) {
      return $this->t('The http status code is not @status_code', ['@status_code' => $status_code]);
    }

    return $this->t('The http status code is @status_code', ['@status_code' => $status_code]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['status_codes']) && !$this->isNegated()) {
      return TRUE;
    }

    /** @var \Symfony\Component\HttpKernel\Exception\HttpException $exception */
    $exception = $this->requestStack->getCurrentRequest()->attributes->get('exception');

    if (!empty($exception) && $exception instanceof HttpException) {
      $status_code = $exception->getStatusCode();
    }
    else {
      $status_code = 200;
    }

    return !empty($this->configuration['status_codes'][$status_code]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['status_codes' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}
