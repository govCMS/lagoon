<?php

namespace Drupal\govcms_akamai_purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_http\Plugin\Purge\Purger\HttpBundledPurger;

/**
 * GovCMS Bundled Purger.
 *
 * @PurgePurger(
 *   id = "govcms_httpbundled",
 *   label = @Translation("GovCMS Bundled Purger"),
 *   configform = "\Drupal\purge_purger_http\Form\HttpBundledPurgerForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Configurable purger that sends a request to the Akamai Purge Service for a set of invalidation instructions."),
 *   multi_instance = FALSE,
 *   types = {},
 * )
 */
class BundledPurger extends HttpBundledPurger {

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {

    // Create a simple closure to mass-update states on the objects.
    $set_state = function ($state) use ($invalidations) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState($state);
      }
    };

    // Inform tokens it's govcms.
    foreach ($invalidations as $invalidation) {
      $invalidation->setProperty('govcms', TRUE);
    }

    // Build up a single HTTP request, execute it and log errors.
    $token_data = ['invalidations' => $invalidations];
    $uri = $this->getUri($token_data);
    $opt = $this->getOptions($token_data);

    // Do not verify SSL certificate.
    $opt['verify'] = FALSE;
    $opt['headers']['X-Purge-Token'] = getenv('AKAMAI_PURGE_TOKEN');
    $opt['headers']['X-Lagoon-Project'] = getenv('LAGOON_PROJECT');

    try {
      $this->client->request($this->settings->request_method, $uri, $opt);
      $set_state(InvalidationInterface::SUCCEEDED);
    }
    catch (\Exception $e) {
      $set_state(InvalidationInterface::FAILED);

      // Log as much useful information as we can.
      $headers = $opt['headers'];
      unset($opt['headers']);
      $debug = json_encode(
        str_replace("\n", ' ',
          [
            'msg' => $e->getMessage(),
            'uri' => $uri,
            'method' => $this->settings->request_method,
            'guzzle_opt' => $opt,
            'headers' => $headers,
          ]
        )
      );
      $this->logger()->emergency("item failed due @e, details (JSON): @debug",
        ['@e' => get_class($e), '@debug' => $debug]
      );
    }
  }

}
