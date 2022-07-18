<?php

namespace Drupal\recaptcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure reCAPTCHA settings for this site.
 */
class ReCaptchaAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recaptcha_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recaptcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recaptcha.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['recaptcha_site_key'] = [
      '#default_value' => $config->get('site_key'),
      '#description' => $this->t('The site key given to you when you <a href=":url">register for reCAPTCHA</a>.', [':url' => 'https://www.google.com/recaptcha/admin']),
      '#maxlength' => 40,
      '#required' => TRUE,
      '#title' => $this->t('Site key'),
      '#type' => 'textfield',
    ];

    $form['general']['recaptcha_secret_key'] = [
      '#default_value' => $config->get('secret_key'),
      '#description' => $this->t('The secret key given to you when you <a href=":url">register for reCAPTCHA</a>.', [':url' => 'https://www.google.com/recaptcha/admin']),
      '#maxlength' => 40,
      '#required' => TRUE,
      '#title' => $this->t('Secret key'),
      '#type' => 'textfield',
    ];

    $form['general']['recaptcha_verify_hostname'] = [
      '#default_value' => $config->get('verify_hostname'),
      '#description' => $this->t('Checks the hostname on your server when verifying a solution. Enable this validation only, if <em>Verify the origin of reCAPTCHA solutions</em> is unchecked for your key pair. Provides crucial security by verifying requests come from one of your listed domains.'),
      '#title' => $this->t('Local domain name validation'),
      '#type' => 'checkbox',
    ];

    $form['general']['recaptcha_use_globally'] = [
      '#default_value' => $config->get('use_globally'),
      '#description' => $this->t('Enable this in circumstances when "www.google.com" is not accessible, e.g. China.'),
      '#title' => $this->t('Use reCAPTCHA globally'),
      '#type' => 'checkbox',
    ];

    // Widget configurations.
    $form['widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget settings'),
      '#open' => TRUE,
    ];
    $form['widget']['recaptcha_theme'] = [
      '#default_value' => $config->get('widget.theme'),
      '#description' => $this->t('Defines which theme to use for reCAPTCHA.'),
      '#options' => [
        'light' => $this->t('Light (default)'),
        'dark' => $this->t('Dark'),
      ],
      '#title' => $this->t('Theme'),
      '#type' => 'select',
    ];
    $form['widget']['recaptcha_type'] = [
      '#default_value' => $config->get('widget.type'),
      '#description' => $this->t('The type of CAPTCHA to serve.'),
      '#options' => [
        'image' => $this->t('Image (default)'),
        'audio' => $this->t('Audio'),
      ],
      '#title' => $this->t('Type'),
      '#type' => 'select',
    ];
    $form['widget']['recaptcha_size'] = [
      '#default_value' => $config->get('widget.size'),
      '#description' => $this->t('The size of CAPTCHA to serve.'),
      '#options' => [
        '' => $this->t('Normal (default)'),
        'compact' => $this->t('Compact'),
      ],
      '#title' => $this->t('Size'),
      '#type' => 'select',
    ];
    $form['widget']['recaptcha_tabindex'] = [
      '#default_value' => $config->get('widget.tabindex'),
      '#description' => $this->t('Set the <a href=":tabindex">tabindex</a> of the widget and challenge (Default = 0). If other elements in your page use tabindex, it should be set to make user navigation easier.', [':tabindex' => Url::fromUri('https://www.w3.org/TR/html4/interact/forms.html', ['fragment' => 'adef-tabindex'])->toString()]),
      '#maxlength' => 4,
      '#title' => $this->t('Tabindex'),
      '#type' => 'number',
      '#min' => -1,
    ];
    $form['widget']['recaptcha_noscript'] = [
      '#default_value' => $config->get('widget.noscript'),
      '#description' => $this->t('If JavaScript is a requirement for your site, you should <strong>not</strong> enable this feature. With this enabled, a compatibility layer will be added to the captcha to support non-js users.'),
      '#title' => $this->t('Enable fallback for browsers with JavaScript disabled'),
      '#type' => 'checkbox',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('recaptcha.settings');
    $config
      ->set('site_key', $form_state->getValue('recaptcha_site_key'))
      ->set('secret_key', $form_state->getValue('recaptcha_secret_key'))
      ->set('verify_hostname', $form_state->getValue('recaptcha_verify_hostname'))
      ->set('use_globally', $form_state->getValue('recaptcha_use_globally'))
      ->set('widget.theme', $form_state->getValue('recaptcha_theme'))
      ->set('widget.type', $form_state->getValue('recaptcha_type'))
      ->set('widget.size', $form_state->getValue('recaptcha_size'))
      ->set('widget.tabindex', $form_state->getValue('recaptcha_tabindex'))
      ->set('widget.noscript', $form_state->getValue('recaptcha_noscript'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
