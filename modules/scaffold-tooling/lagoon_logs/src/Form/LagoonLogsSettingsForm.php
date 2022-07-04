<?php

namespace Drupal\lagoon_logs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for lagoon_logs.
 */
class LagoonLogsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lagoon_logs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lagoon_logs_settings_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('lagoon_logs.settings');

    $form['disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable module'),
      '#description' => $this->t('Suppress sending logs to Lagoon.'),
      '#default_value' => $config->get('disable'),
    ];

    $form['description'] = [
      '#prefix' => '<div class="ll-settings-description">',
      '#suffix' => '</div>',
      '#markup' => $this->t(
        '<p>Current settings for the Lagoon Logs module. The defaults are set in configuration, this page is meant primarily for troubleshooting.</p>' .
        '<ul>' .
          '<li><b>' . $this->t('Logstash host') . ':</b> ' . $config->get('host') . '</li>' .
          '<li><b>' . $this->t('Logstash port') . ':</b> ' . $config->get('port') . '</li>' .
          '<li><b>' . $this->t('Logstash leading identifier') . ':</b> ' . $config->get('identifier') . '</li>' .
        '</ul>'
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('lagoon_logs.settings')
      ->set('disable', $form_state->getValue('disable'))
      ->save();
  }

}
