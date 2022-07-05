<?php

namespace Drupal\ga_login\Plugin\TfaValidation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\user\UserDataInterface;
use Otp\GoogleAuthenticator;
use Otp\Otp;
use ParagonIE\ConstantTime\Encoding;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TOTP validation class for performing TOTP validation.
 *
 * @TfaValidation(
 *   id = "ga_login_totp",
 *   label = @Translation("GA Login Time-based OTP(TOTP)"),
 *   description = @Translation("GA Login Totp Validation Plugin"),
 *   setupPluginId = "ga_login_totp_setup",
 * )
 */
class GALoginTotpValidation extends TfaBasePlugin implements TfaValidationInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * Object containing the external validation library.
   *
   * @var object
   */
  public $auth;

  /**
   * The time-window in which the validation should be done.
   *
   * @var int
   */
  protected $timeSkew;

  /**
   * Whether or not the prefix should use the site name.
   *
   * @var bool
   */
  protected $siteNamePrefix;

  /**
   * Name prefix.
   *
   * @var string
   */
  protected $namePrefix;

  /**
   * Configurable name of the issuer.
   *
   * @var string
   */
  protected $issuer;

  /**
   * Whether the code has already been used or not.
   *
   * @var bool
   */
  protected $alreadyAccepted;

  /**
   * The Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);
    $this->auth = new \StdClass();
    $this->auth->otp = new Otp();
    $this->auth->ga = new GoogleAuthenticator();
    // Allow codes within tolerance range of 2 * 30 second units.
    $plugin_settings = $config_factory->get('tfa.settings')->get('validation_plugin_settings');
    $settings = isset($plugin_settings['ga_login_totp']) ? $plugin_settings['ga_login_totp'] : [];
    $settings = array_replace([
      'time_skew' => 2,
      'site_name_prefix' => TRUE,
      'name_prefix' => 'TFA',
      'issuer' => 'Drupal',
    ], $settings);

    $this->timeSkew = $settings['time_skew'];
    $this->siteNamePrefix = $settings['site_name_prefix'];
    $this->namePrefix = $settings['name_prefix'];
    $this->issuer = $settings['issuer'];
    $this->alreadyAccepted = FALSE;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.data'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption'),
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return ($this->getSeed() !== FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $message = $this->t('Verification code is application generated and @length digits long.', ['@length' => $this->codeLength]);
    if ($this->getUserData('tfa', 'tfa_recovery_code', $this->uid, $this->userData)) {
      $message .= '<br/>' . $this->t("Can't access your account? Use one of your recovery codes.");
    }
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application verification code'),
      '#description' => $message,
      '#required'  => TRUE,
      '#attributes' => [
        'autocomplete' => 'off',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['login'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Verify'),
    ];

    return $form;
  }

  /**
   * The configuration form for this validation plugin.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Config object for tfa settings.
   * @param array $state
   *   Form state array determines if this form should be shown.
   *
   * @return array
   *   Form array specific for this validation plugin.
   */
  public function buildConfigurationForm(Config $config, array $state = []) {
    $settings_form['time_skew'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of Accepted Codes'),
      '#default_value' => $this->timeSkew,
      '#description' => $this->t('Number of past codes to consider valid. Codes are generated every 30 seconds, so setting this value to 10 would allow each code to work for five minutes.'),
      '#size' => 2,
      '#states' => $state,
      '#required' => TRUE,
    ];

    $settings_form['site_name_prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use site name as OTP QR code name prefix.'),
      '#default_value' => $this->siteNamePrefix,
      '#description' => $this->t('If checked, the site name will be used instead of a static string. This can be useful for multi-site installations.'),
      '#states' => $state,
    ];

    // Hide custom name prefix when site name prefix is selected.
    $state['visible'] += [
      ':input[name="validation_plugin_settings[ga_login_totp][site_name_prefix]"]' => ['checked' => FALSE],
    ];

    $settings_form['name_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP QR Code Prefix'),
      '#default_value' => ($this->namePrefix) ?: 'tfa',
      '#description' => $this->t('Prefix for OTP QR code names. Suffix is account username.'),
      '#size' => 15,
      '#states' => $state,
    ];

    $settings_form['issuer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Issuer'),
      '#default_value' => $this->issuer,
      '#description' => $this->t('The provider or service this account is associated with.'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!$this->validate($values['code'])) {
      $this->errorMessages['code'] = $this->t('Invalid application code. Please try again.');
      if ($this->alreadyAccepted) {
        $form_state->clearErrors();
        $this->errorMessages['code'] = $this->t('Invalid code, it was recently used for a login. Please try a new code.');
      }
      return FALSE;
    }
    else {
      // Store accepted code to prevent replay attacks.
      $this->storeAcceptedCode($values['code']);
      return TRUE;
    }
  }

  /**
   * Simple validate for web services.
   *
   * @param int $code
   *   OTP Code.
   *
   * @return bool
   *   True if validation was successful otherwise false.
   */
  public function validateRequest($code) {
    if ($this->validate($code)) {
      $this->storeAcceptedCode($code);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($code) {
    // Strip whitespace.
    $code = preg_replace('/\s+/', '', $code);
    if ($this->alreadyAcceptedCode($code)) {
      $this->isValid = FALSE;
    }
    else {
      // Get OTP seed.
      $seed = $this->getSeed();
      $this->isValid = ($seed && $this->auth->otp->checkTotp(Encoding::base32DecodeUpper($seed), $code, $this->timeSkew));
    }
    return $this->isValid;
  }

  /**
   * Returns whether code has already been used or not.
   *
   * @return bool
   *   True is code already used otherwise false.
   */
  public function isAlreadyAccepted() {
    return $this->alreadyAccepted;
  }

  /**
   * Get seed for this account.
   *
   * @return string
   *   Decrypted account OTP seed or FALSE if none exists.
   */
  protected function getSeed() {
    // Lookup seed for account and decrypt.
    $result = $this->getUserData('tfa', 'tfa_totp_seed', $this->uid, $this->userData);

    if (!empty($result)) {
      $encrypted = base64_decode($result['seed']);
      $seed = $this->decrypt($encrypted);
      if (!empty($seed)) {
        return $seed;
      }
    }
    return FALSE;
  }

  /**
   * Save seed for account.
   *
   * @param string $seed
   *   Un-encrypted seed.
   */
  public function storeSeed($seed) {
    // Encrypt seed for storage.
    $encrypted = $this->encrypt($seed);

    $record = [
      'tfa_totp_seed' => [
        'seed' => base64_encode($encrypted),
        'created' => $this->time->getRequestTime(),
      ],
    ];

    $this->setUserData('tfa', $record, $this->uid, $this->userData);
  }

  /**
   * Delete the seed of the current validated user.
   */
  protected function deleteSeed() {
    $this->deleteUserData('tfa', 'tfa_totp_seed', $this->uid, $this->userData);
  }

  /**
   * Get the value of the time-window in which the validation should be done.
   *
   * @return int
   *   The current value of the time skew.
   */
  public function getTimeSkew() {
    return $this->timeSkew;
  }

}
