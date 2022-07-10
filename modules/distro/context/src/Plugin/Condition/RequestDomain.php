<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Request domain' condition.
 *
 * @Condition(
 *   id = "request_domain",
 *   label = @Translation("Request domain"),
 * )
 */
class RequestDomain extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(RequestStack $request_stack, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['domains' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $domains = array_map('trim', explode("\n", $this->configuration['domains']));
    $domains = implode(', ', $domains);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following domains: @domains', ['@domains' => $domains]);
    }
    return $this->t('Return true on the following domains: @domains', ['@domains' => $domains]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domains'),
      '#default_value' => $this->configuration['domains'],
      '#description' => $this->t("Specify domains, leave off the http:// and the trailing slash and do not include any paths. Enter one domain per line. Ex: example.com or one.example.com."),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['domains'] = $form_state->getValue('domains');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Convert domain to lowercase.
    $domains = mb_strtolower($this->configuration['domains']);
    if (!$domains) {
      return TRUE;
    }

    // Domain array.
    $domains = array_map('trim', explode("\n", $this->configuration['domains']));

    // Takes the current host.
    $request = $this->requestStack->getCurrentRequest();
    $host = $request->getHost();

    if (empty($host)) {
      return FALSE;
    }

    if (in_array($host, $domains)) {
      return TRUE;
    }

    return FALSE;
  }

}
