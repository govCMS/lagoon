<?php

namespace Drupal\key\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for plugins that integrate with the delete form.
 */
interface KeyPluginDeleteFormInterface {

  /**
   * Form constructor.
   *
   * Allows a plugin to modify the form displayed when confirming the
   * deletion of a key. It could, for example, add additional warning
   * text or fields.
   *
   * @param array $form
   *   An associative array containing the initial structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildDeleteForm(array &$form, FormStateInterface $form_state);

  /**
   * Form validation handler.
   *
   * Allows a plugin to perform additional validation to the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateDeleteForm(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitDeleteForm(array &$form, FormStateInterface $form_state);

}
