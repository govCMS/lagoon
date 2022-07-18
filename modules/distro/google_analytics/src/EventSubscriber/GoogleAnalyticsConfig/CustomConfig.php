<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent;
use Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds custom Dimensions and Metrics to config and events.
 */
class CustomConfig implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Current Drupal User Account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentAccount;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * Custom Mapping of Vars.
   *
   * @var array
   */
  protected $custom_map = [];

  /**
   * Custom Variables passed to GA.
   * @var array
   */
  protected $custom_vars = [];

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $account, RequestStack $request, Token $token) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->currentAccount = $account;
    $this->request = $request->getCurrentRequest();
    $this->token = $token;

    // Populate custom map/vars
    $this->populateCustomConfig();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::ADD_CONFIG][] = ['onAddConfig'];
    $events[GoogleAnalyticsEvents::ADD_EVENT][] = ['onAddEvent'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onAddConfig(GoogleAnalyticsConfigEvent $event) {
    // Don't execute event if there is nothing in the mapping fields.
    if (empty($this->custom_map)) {
      return;
    }

    // Only populate the config on UA accounts.
    if ($event->getGaAccount()->isUniversalAnalyticsAccount()) {
      $event->addConfig('custom_map', $this->custom_map['custom_map']);
    }
  }

  public function onAddEvent(GoogleAnalyticsEventsEvent $event) {
    // Don't execute event if there is nothing in the mapping fields.
    if (empty($this->custom_vars)) {
      return;
    }
    $event->addEvent(['custom' => $this->custom_vars]);
  }

  protected function populateCustomConfig() {
    // Add custom dimensions and metrics.
    $custom_parameters = $this->config->get('custom.parameters');
    if (!empty($custom_parameters)) {
      // Add all the configured variables to the content.
    foreach ($custom_parameters as $index => $custom_parameter) {
      // Replace tokens in values.
      $types = [];
      if ($this->request->attributes->has('node')) {
        $node = $this->request->attributes->get('node');
        if ($node instanceof NodeInterface) {
          $types += ['node' => $node];
        }
      }
      $custom_parameter['value'] = $this->token->replace($custom_parameter['value'], $types, ['clear' => TRUE]);

      // Suppress empty values.
      if ((isset($custom_parameter['name']) && !mb_strlen(trim($custom_parameter['name']))) || !mb_strlen(trim($custom_parameter['value']))) {
        continue;
      }

        // Per documentation the max length of a dimension is 150 bytes.
        // A metric has no length limitation. It's not documented if this
        // limit means 150 bytes after url encoding or before.
        // See https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#customs
        if ($custom_parameter['type'] == 'dimension' && mb_strlen($custom_parameter['value']) > 150) {
          $custom_parameter['value'] = substr($custom_parameter['value'], 0, 150);
        }

        // Cast metric values for json_encode to data type numeric.
        if ($custom_parameter['type'] == 'metric') {
          settype($custom_parameter['value'], 'float');
        };

        // Build the arrays of values.
        $this->custom_map['custom_map'][$index] = ($custom_parameter['name'] ?? "");
        if (isset($custom_parameter['name'])) {
          $this->custom_vars[$custom_parameter['name']] = $custom_parameter['value'];
        }
      }
    }
  }

}
