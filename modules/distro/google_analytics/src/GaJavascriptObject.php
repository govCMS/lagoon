<?php

namespace Drupal\google_analytics;

use Drupal\Component\Serialization\Json;

/**
 * Class GaJavascript Object.
 *
 * @package Drupal\google_analytics
 */
class GaJavascriptObject implements GaJavascriptInterface {

  /**
   * Default measurement_id for the object.
   *
   * @var string
   */
  protected $measurement_id;

  /**
   * Object config.
   *
   * @var array
   */
  protected $config = [];

  /**
   * Events list.
   *
   * @var array
   */
  protected $events = [];

  /**
   * Custom URL.
   */
  protected $custom_url = '';

  /**
   * Adsense Script
   */
  protected $adsense = '';

  /**
   * GaJavascriptObject constructor.
   *
   * @param string $measurement_id
   *   Object default measurement_id.
   * @param array $config
   *   Object config.
   */
  public function __construct($measurement_id, array $config = []) {
    $this->measurement_id = $measurement_id;
    $this->setConfig($measurement_id, $config);
  }

  /**
   * Static Factory method to allow GaJavascript to interpret their own data.
   *
   * @param array $data
   *   Initial data.
   *
   * @return \Drupal\google_analytics\GaJavascriptObject
   *   GaJavascriptObject.
   *
   */
  public static function fromArray(array $data) {
    $object = new static($data['measurement_id'], $data['config']['measurement_id']);
    return $object;
  }

  /**
   * Static Factory method to format data from JSON into the Javascript Object.
   *
   * @param string $json
   *   Data in JSON format.
   *
   * @return \Drupal\google_analytics\GaJavascriptObject
   *   GA Javascript Object.
   *
   * @throws \ReflectionException
   */
  public static function fromJson(string $json) {
    return self::fromArray(json_decode($json, TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $output = [
      'measurement_id' => $this->getMeasurementId(),
      'config' => $this->config,
      'events' => $this->events,
    ];

    return $output;
  }

  public function getMeasurementId() {
    return $this->measurement_id;
  }

  public function getConfig($measurement_id = NULL) {
    if (isset($this->config[$measurement_id ?? $this->measurement_id])) {
      return $this->config[$measurement_id ?? $this->measurement_id];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig($measurement_id, array $config) {
    $this->config[(string)$measurement_id] = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvents() {
    return $this->events;
  }

  public function addEvent(array $event) {
    $this->events[] = $event;
  }

  public function getCustomUrl() {
    return $this->custom_url;
  }

  public function setCustomUrl($url) {
    $this->custom_url = $url;
  }

  public function setAdsenseScript($domain = 'none') {
    $this->adsense = 'window.google_analytics_domain_name = ' . Json::encode($domain) . ';
                      window.google_analytics_uacct = ' . Json::encode($this->measurement_id) . ';';
  }

  public function getAdsenseScript() {
    return $this->adsense;
  }
}
