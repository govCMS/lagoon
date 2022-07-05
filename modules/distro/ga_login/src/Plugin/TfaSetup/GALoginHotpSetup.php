<?php

namespace Drupal\ga_login\Plugin\TfaSetup;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ga_login\Plugin\TfaValidation\GALoginHotpValidation;
use ParagonIE\ConstantTime\Encoding;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use chillerlan\QRCode\QRCode;

/**
 * HOTP setup class to setup HOTP validation.
 *
 * @TfaSetup(
 *   id = "ga_login_hotp_setup",
 *   label = @Translation("GA Login Hotp Setup"),
 *   description = @Translation("GA Login Hotp Setup Plugin"),
 *   helpLinks = {
 *    "Google Authenticator (Android/iPhone/BlackBerry)" = "https://support.google.com/accounts/answer/1066447?hl=en",
 *    "Authy (Android/iPhone)" = "http://authy.com/download/",
 *    "FreeOTP (Android)" = "https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp",
 *    "GAuth Authenticator (desktop)" = "https://github.com/gbraad/html5-google-authenticator"
 *   },
 *   setupMessages = {
 *    "saved" = @Translation("Application code verified."),
 *    "skipped" = @Translation("Application codes not enabled.")
 *   }
 * )
 */
class GALoginHotpSetup extends GALoginHotpValidation implements TfaSetupInterface {

  /**
   * Un-encrypted seed.
   *
   * @var string
   */
  protected $seed;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service, $config_factory, $time);
    // Generate seed.
    $this->setSeed($this->createSeed());
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $help_links = $this->getHelpLinks();

    $items = [];
    foreach ($help_links as $item => $link) {
      $items[] = Link::fromTextAndUrl($item, Url::fromUri($link, ['attributes' => ['target' => '_blank']]));
    }

    $form['apps'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Install authentication code application on your mobile or desktop device:'),
    ];
    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The two-factor authentication application will be used during this setup and for generating codes during regular authentication. If the application supports it, scan the QR code below to get the setup code otherwise you can manually enter the text code.'),
    ];
    $form['seed'] = [
      '#type' => 'textfield',
      '#value' => $this->seed,
      '#disabled' => TRUE,
      '#description' => $this->t('Enter this code into your two-factor authentication app or scan the QR code below.'),
    ];

    // QR image of seed.
    $form['qr_image'] = [
      '#prefix' => '<div class="ga-login-qr-code"',
      '#theme' => 'image',
      '#uri' => $this->getQrCodeUri(),
      '#alt' => $this->t('QR code for TFA setup'),
      '#suffix' => '</div>',
    ];

    // QR code css giving it a fixed width.
    $form['page']['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => ".ga-login-qr-code { width:200px }",
      ],
      'qrcode-css',
    ];

    // Include code entry form.
    $form = $this->getForm($form, $form_state);
    $form['actions']['login']['#value'] = $this->t('Verify and save');
    // Alter code description.
    $form['code']['#description'] = $this->t('A verification code will be generated after you scan the above QR code or manually enter the setup code. The verification code is six digits long.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    if (!$this->validate($form_state->getValue('code'))) {
      $this->errorMessages['code'] = $this->t('Invalid application code. Please try again.');
      return FALSE;
    }
    $this->storeAcceptedCode($form_state->getValue('code'));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($code) {
    // The counter is set as 1 because that is the initial value.
    // This ensures that things work even if we reset the application.
    $code = preg_replace('/\s+/', '', $code);
    $counter = $this->auth->otp->checkHotpResync(Encoding::base32DecodeUpper($this->seed), 1, $code, $this->counterWindow);
    $this->setUserData('tfa', ['tfa_hotp_counter' => ++$counter], $this->uid, $this->userData);
    return ((bool) $counter);
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    // Write seed for user.
    $this->storeSeed($this->seed);
    return TRUE;
  }

  /**
   * Get a base64 qrcode image uri of seed.
   *
   * @return string
   *   QR-code uri.
   */
  protected function getQrCodeUri() {
    return (new QRCode)->render('otpauth://hotp/' . $this->accountName() . '?secret=' . $this->seed . '&counter=1&issuer=' . urlencode($this->issuer));
  }

  /**
   * Create OTP seed for account.
   *
   * @return string
   *   Un-encrypted seed.
   */
  protected function createSeed() {
    return $this->auth->ga->generateRandom();
  }

  /**
   * Setter for OTP secret key.
   *
   * @param string $seed
   *   The OTP secret key.
   */
  public function setSeed($seed) {
    $this->seed = $seed;
  }

  /**
   * Get account name for QR image.
   *
   * @return string
   *   URL encoded string.
   */
  protected function accountName() {
    /** @var \Drupal\user\Entity\User $account */
    $account = User::load($this->configuration['uid']);
    $prefix = $this->siteNamePrefix ? preg_replace('@[^a-z0-9-]+@', '-', strtolower(\Drupal::config('system.site')->get('name'))) : $this->namePrefix;
    return urlencode($prefix . '-' . $account->getAccountName());
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    $plugin_text = $this->t('Validation Plugin: @plugin',
      [
        '@plugin' => str_replace(' Setup', '', $this->getLabel()),
      ]
    );
    $output = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('TFA application'),
      ],
      'validation_plugin' => [
        '#type' => 'markup',
        '#markup' => '<p>' . $plugin_text . '</p>',
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Generate verification codes from a mobile or desktop application.'),
      ],
      'link' => [
        '#theme' => 'links',
        '#links' => [
          'admin' => [
            'title' => !$params['enabled'] ? $this->t('Set up application') : $this->t('Reset application'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return $this->pluginDefinition['helpLinks'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return ($this->pluginDefinition['setupMessages']) ?: '';
  }

}
