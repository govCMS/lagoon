<?php

namespace Drupal\encrypt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the encrypt settings admin page.
 */
class EncryptSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'encrypt_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['encrypt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('encrypt.settings');

    $form['check_profile_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the validation status of encryption profiles.'),
      '#description' => $this->t('On the encryption profiles overview page, automatically validate each encryption profile to check if there are problems with it. Disable when you have a lot of encryption profiles and are encountering performance issues, or if you do not want encryption keys to be loaded by the status check.'),
      '#default_value' => $config->get('check_profile_status'),
    ];

    $form['allow_deprecated_plugins'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the use of deprecated plugins.'),
      '#description' => $this->t('Enable this setting to allow the use of deprecated encryption plugins in new profiles. Leave disabled to only allow their use in existing profiles'),
      '#default_value' => $config->get('allow_deprecated_plugins'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('encrypt.settings')
      ->set('check_profile_status', $form_state->getValue('check_profile_status'))
      ->set('allow_deprecated_plugins', $form_state->getValue('allow_deprecated_plugins'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
