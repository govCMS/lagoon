<?php

namespace Drupal\config_ignore\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a setting UI for Config Ignore.
 *
 * @package Drupal\config_ignore\Form
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_ignore.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_ignore_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $description = $this->t('One configuration name per line.<br />
Examples: <ul>
<li>user.settings</li>
<li>views.settings</li>
<li>contact.settings</li>
<li>webform.webform.* (will ignore all config entities that starts with <em>webform.webform</em>)</li>
<li>*.contact_message.custom_contact_form.* (will ignore all config entities that starts with <em>.contact_message.custom_contact_form.</em> like fields attached to a custom contact form)</li>
<li>* (will ignore everything)</li>
<li>~webform.webform.contact (will force import for this configuration, even if ignored by a wildcard)</li>
<li>user.mail:register_no_approval_required.body (will ignore the body of the no approval required email setting, but will not ignore other user.mail configuration.)</li>
</ul>');

    $config_ignore_settings = $this->config('config_ignore.settings');
    $form['ignored_config_entities'] = [
      '#type' => 'textarea',
      '#rows' => 25,
      '#title' => $this->t('Configuration entity names to ignore'),
      '#description' => $description,
      '#default_value' => implode(PHP_EOL, $config_ignore_settings->get('ignored_config_entities')),
      '#size' => 60,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config_ignore_settings = $this->config('config_ignore.settings');
    $config_ignore_settings_array = preg_split("/[\r\n]+/", $values['ignored_config_entities']);
    $config_ignore_settings_array = array_filter($config_ignore_settings_array);
    $config_ignore_settings_array = array_values($config_ignore_settings_array);
    $config_ignore_settings->set('ignored_config_entities', $config_ignore_settings_array);
    $config_ignore_settings->save();
    parent::submitForm($form, $form_state);

    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

}
