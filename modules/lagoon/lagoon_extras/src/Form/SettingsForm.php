<?php

namespace Drupal\lagoon_extras\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for the module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'lagoon_extras.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lagoon_extras_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['title'] = [
      '#type' => 'item',
      '#description' => $this->t('Application configuration to assist with platform management.'),
    ];

    $form['verbose_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable verbose logging'),
      '#description' => $this->t('The platform module provides overrides for base services with additional logging this adds
      <code>debug_backtrace</code> to assist with identifying issues.'),
      '#default_value' => $config->get('verbose_logging'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('verbose_logging', $form_state->getValue(['verbose_logging']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
