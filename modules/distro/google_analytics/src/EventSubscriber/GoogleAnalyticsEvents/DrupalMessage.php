<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsEvents;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts;

/**
 * Adds Drupal Messages to GA Javascript.
 */
class DrupalMessage extends GoogleAnalyticsEventBase {

  /**
   * Drupal Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   * @param \Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts $ga_accounts
   *   The Google Analytics Account Service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger Factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoogleAnalyticsAccounts $ga_accounts, MessengerInterface $messenger) {
    parent::__construct($config_factory, $ga_accounts);
    $this->messenger = $messenger;
  }

  public function addGaEvent(): array {
    $events = [];
    if ($message_types = $this->ga_config->get('track.messages')) {
      $message_types = array_values(array_filter($message_types));
      $status_heading = [
        'status' => t('Status message'),
        'warning' => t('Warning message'),
        'error' => t('Error message'),
      ];

      foreach ($this->messenger->all() as $type => $messages) {
        // Track only the selected message types.
        if (in_array($type, $message_types)) {
          foreach ($messages as $message) {
            // Compatibility with 3.x and UA format.
            if ($this->isLegacy) {
              $events[] = [(string)$status_heading[$type] =>
                ['event_category' => (string)t('Messages'),
                  'event_label'    => strip_tags((string) $message)
                ]
              ];
            }
            else {
              $events[] = [(string)$status_heading[$type] =>
                ['value' => strip_tags((string) $message)]
              ];
            }
          }
        }
      }
    }
    return $events;
  }

}
