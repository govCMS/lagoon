<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Condition\RequestPath;

/**
 * Provides a 'Request path exclusion' condition.
 *
 * @Condition(
 *   id = "request_path_exclusion",
 *   label = @Translation("Request path exclusion"),
 * )
 */
class RequestPathExclusion extends RequestPath {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Hide the negate checkbox.
    $form['negate']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['negate' => TRUE] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Defaults negation to TRUE.
    $this->configuration['negate'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // As a failsafe, ensure it's always set to negate before evaluating.
    $this->configuration['negate'] = TRUE;
    // Allow this to pass through gracefully when blank.
    $pages = mb_strtolower($this->configuration['pages']);
    if (!$pages) {
      return FALSE;
    }

    return parent::evaluate();
  }

}
