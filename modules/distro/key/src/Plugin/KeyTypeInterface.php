<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for all Key Type plugins.
 */
interface KeyTypeInterface {

  /**
   * Allows the Key Type plugin to validate the key value.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the plugin form.
   * @param string|null $key_value
   *   The key value to be validated.
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value);

  /**
   * Generate a key value of this type using the submitted configuration.
   *
   * @param array $configuration
   *   The configuration for the key type plugin.
   *
   * @return string
   *   The generated key value.
   */
  public static function generateKeyValue(array $configuration);

}
