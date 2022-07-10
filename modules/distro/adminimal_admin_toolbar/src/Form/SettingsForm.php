<?php

namespace Drupal\adminimal_admin_toolbar\Form;

/**
 * @file
 * Contains \Drupal\adminimal_admin_toolbar\Form\SettingsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure menu link weight settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adminimal_admin_toolbar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adminimal_admin_toolbar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adminimal_admin_toolbar.settings');

    $form['avoid_custom_font'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Avoid loading "Open Sans" font'),
      '#default_value' => $config->get('avoid_custom_font'),
      '#description' => $this->t(
        'Google Open Sans will not be downloaded if this is checked (useful for languages that are not well supported by the "Open sans" font. Like Japanese for example).'
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('adminimal_admin_toolbar.settings');

    $config->set('avoid_custom_font', $form_state->getValue('avoid_custom_font'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
