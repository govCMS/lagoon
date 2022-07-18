<?php

namespace Drupal\google_analytics;

/**
 * Interface GaJavascriptInterface.
 *
 * @package Drupal\google_analytics
 */
interface GaJavascriptInterface {

  /**
   * Returns the Primary GA measurement ID.
   *
   * @return string
   *   Object measurement ID.
   */
  public function getMeasurementId();

  /**
   * Returns object's config.
   *
   * @param string $measurement_id
   *   The config's measurement_id.
   *
   * @return array
   *   Object's config.
   */
  public function getConfig($measurement_id);

  /**
   * Metadata setter.
   *
   * @param array $config
   *   Metadata array.
   */
  public function setConfig($measurement_id, array $config);

  /**
   * Returns all stored GA Events.
   *
   * @return array
   *   Object's events.
   */
  public function getEvents();

  /**
   * Appends an event to the Javascript object.
   *
   * @param array $event
   *   The event.
   */
  public function addEvent(array $event);

  /**
   * Converts object to array.
   *
   * @return mixed
   *   Array representation.
   */
  public function toArray();

}
