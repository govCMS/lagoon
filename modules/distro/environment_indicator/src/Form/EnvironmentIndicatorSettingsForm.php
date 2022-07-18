<?php

namespace Drupal\environment_indicator\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic Environment Indicator controls form.
 */
class EnvironmentIndicatorSettingsForm extends ConfigFormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_indicator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.settings');
    $form = parent::buildForm($form, $form_state);
    $form['toolbar_integration'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Toolbar integration'),
      '#options' => [
        'toolbar' => $this->t('Toolbar'),
      ],
      '#description' => $this->t('Select the toolbars that you want to integrate with.'),
      '#default_value' => $config->get('toolbar_integration') ?: [],
    ];
    $form['favicon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show favicon'),
      '#description' => $this->t('If checked, a favicon will be added with the environment colors when the indicator is shown.'),
      '#default_value' => $config->get('favicon') ?: FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['environment_indicator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.settings');
    $properties = ['toolbar_integration', 'favicon'];
    array_walk($properties, function ($property) use ($config, $form_state) {
      $config->set($property, $form_state->getValue($property));
    });
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
