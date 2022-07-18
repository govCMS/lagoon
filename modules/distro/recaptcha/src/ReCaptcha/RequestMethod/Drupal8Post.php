<?php

namespace Drupal\recaptcha\ReCaptcha\RequestMethod;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends POST requests to the reCAPTCHA service with Drupal 8 httpClient.
 */
class Drupal8Post implements RequestMethod {

  /**
   * Submit the POST request with the specified parameters.
   *
   * @param \ReCaptcha\ReCaptcha\RequestParameters $params
   *   Request parameters.
   *
   * @return string
   *   Body of the reCAPTCHA response.
   */
  public function submit(RequestParameters $params) {

    $options = [
      'headers' => [
        'Content-type' => 'application/x-www-form-urlencoded',
      ],
      'body' => $params->toQueryString(),
      // Stop firing exception on response status code >= 300.
      // See http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html
      'http_errors' => FALSE,
    ];

    $response = \Drupal::httpClient()->post(ReCaptcha::SITE_VERIFY_URL, $options);

    if ($response->getStatusCode() == 200) {
      // The service request was successful.
      return (string) $response->getBody();
    }
    elseif ($response->getStatusCode() < 0) {
      // Negative status codes typically point to network or socket issues.
      return '{"success": false, "error-codes": ["' . ReCaptcha::E_CONNECTION_FAILED . '"]}';
    }
    else {
      // Positive none 200 status code typically means the request has failed.
      return '{"success": false, "error-codes": ["' . ReCaptcha::E_BAD_RESPONSE . '"]}';
    }
  }

}
