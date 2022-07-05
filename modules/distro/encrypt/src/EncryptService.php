<?php

namespace Drupal\encrypt;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\key\KeyRepositoryInterface;

/**
 * Class EncryptService.
 *
 * @package Drupal\encrypt
 */
class EncryptService implements EncryptServiceInterface {

  /**
   * The EncryptionMethod plugin manager.
   *
   * @var \Drupal\encrypt\EncryptionMethodManager
   */
  protected $encryptManager;

  /**
   * The KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\encrypt\EncryptionMethodManager $encrypt_manager
   *   The EncryptionMethod plugin manager.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The KeyRepository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EncryptionMethodManager $encrypt_manager, KeyRepositoryInterface $key_repository, ConfigFactoryInterface $config_factory = NULL) {
    $this->encryptManager = $encrypt_manager;
    $this->keyRepository = $key_repository;
    $this->configFactory = $config_factory ?: \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function loadEncryptionMethods($with_deprecated = TRUE) {
    $encryption_methods = $this->encryptManager->getDefinitions();

    // Unless configured to do so, hide the deprecated encryption plugins.
    $allow_deprecated = $this->configFactory->get('encrypt.settings')->get('allow_deprecated_plugins');
    if (!$allow_deprecated && !$with_deprecated) {
      foreach ($encryption_methods as $plugin_id => $definition) {
        // Skip deprecated methods.
        if ($definition['deprecated']) {
          unset($encryption_methods[$plugin_id]);
        }
      }
    }

    return $encryption_methods;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, EncryptionProfileInterface $encryption_profile) {
    if ($this->validate($text, $encryption_profile)) {
      $key = $encryption_profile->getEncryptionKey();
      return $encryption_profile->getEncryptionMethod()->encrypt($text, $key->getKeyValue());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, EncryptionProfileInterface $encryption_profile) {
    if (!$encryption_profile->getEncryptionMethod()->canDecrypt()) {
      throw new EncryptionMethodCanNotDecryptException();
    }
    if ($this->validate($text, $encryption_profile)) {
      $key = $encryption_profile->getEncryptionKey();
      return $encryption_profile->getEncryptionMethod()->decrypt($text, $key->getKeyValue());
    }
  }

  /**
   * Determines whether the input is valid for encryption / decryption.
   *
   * @param string $text
   *   The text to encrypt / decrypt.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The encryption profile to validate.
   *
   * @return bool
   *   Whether the encryption profile validated correctly.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   Error with validation failures.
   */
  protected function validate($text, EncryptionProfileInterface $encryption_profile) {
    $errors = $encryption_profile->validate($text);
    if (!empty($errors)) {
      // Throw an exception with the errors from the encryption method.
      throw new EncryptException(implode('; ', $errors));
    }
    return TRUE;
  }

}
