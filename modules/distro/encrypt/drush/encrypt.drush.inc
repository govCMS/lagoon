<?php

/**
 * @file
 * Drush integration for Encrypt module.
 */

use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Implements hook_drush_help().
 */
function encrypt_drush_help($section) {
  switch ($section) {
    case 'meta:encrypt:title':
      return dt('Encrypt commands');

    case 'meta:encrypt:summary':
      return dt('Interact with the encryption service.');

  }
}

/**
 * Implements hook_drush_command().
 */
function encrypt_drush_command() {
  $items = [];

  $items['encrypt-encrypt'] = [
    'description' => "Encrypt text with the provided encryption profile.",
    'arguments' => [
      'profile' => 'The machine name of the encryption profile to use.',
    ],
    'options' => [
      'base64' => 'Output the encrypted text in base64 encoded format.',
    ],
    'examples' => [
      "drush encrypt-encrypt profile_name 'text to encrypt'" => 'Encrypts the given text with the specified encryption profile.',
      "drush encrypt-encrypt --base64 profile_name 'text to encrypt'" => 'Encrypts the given text with the specified encryption profile and base64-encodes output.',
    ],
    'aliases' => ['encrypt', 'enc'],
  ];

  $items['encrypt-decrypt'] = [
    'description' => "Decrypt text with the provided encryption profile.",
    'arguments' => [
      'profile' => 'The machine name of the encryption profile to use.',
    ],
    'options' => [
      'base64' => 'Output the encrypted text in base64 encoded format.',
    ],
    'examples' => [
      "drush encrypt-decrypt profile_name 'text to decrypt'" => 'Decrypts the given text with the specified encryption profile.',
      "drush encrypt-decrypt --base64 profile_name 'text to decrypt'" => 'Decrypts the given base64-encoded text with the specified encryption profile.',
    ],
    'aliases' => ['decrypt', 'dec'],
  ];

  $items['encrypt-validate-profile'] = [
    'description' => "Validates a provided encryption profile to check if all dependencies are met.",
    'arguments' => [
      'profile' => 'The machine name of the encryption profile to validate.',
    ],
    'examples' => [
      "drush encrypt-validate-profile profile_name" => 'Validates the given encryption profile.',
    ],
    'aliases' => ['evp'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-default' => ['error'],
      'field-labels' => ['error' => 'Error messages:'],
      'output-data-type' => 'format-table',
    ],
  ];

  return $items;
}

/**
 * Encrypt text with the given encryption profile.
 *
 * @param string $encryption_profile_name
 *   The encryption profile machine name.
 * @param string $text
 *   The text to encrypt.
 *
 * @return string
 *   The encrypted text.
 */
function drush_encrypt_encrypt($encryption_profile_name = FALSE, $text = FALSE) {

  if ($encryption_profile_name && $text) {
    $service = \Drupal::service('encryption');
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      return drush_set_error('', dt('Encryption profile "@name" could not be loaded.', [
        '@name' => $encryption_profile_name,
      ]));
    }
    else {
      $encrypted_text = $service->encrypt($text, $encryption_profile);
      if (drush_get_option('base64')) {
        $encrypted_text = base64_encode($encrypted_text);
      }
      drush_print($encrypted_text);
    }
  }
  else {
    return drush_set_error('error', dt('Please specify the encryption profile and the text to be encrypted', []));
  }
}

/**
 * Decrypt text with the given encryption profile.
 *
 * @param string $encryption_profile_name
 *   The encryption profile machine name.
 * @param string $text
 *   The text to decrypt.
 *
 * @return string
 *   The decrypted text.
 */
function drush_encrypt_decrypt($encryption_profile_name = FALSE, $text = FALSE) {

  if ($encryption_profile_name && $text) {
    $service = \Drupal::service('encryption');
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      return drush_set_error('error', dt('Encryption profile "@name" could not be loaded.', [
        '@name' => $encryption_profile_name,
      ]));
    }
    else {
      if (drush_get_option('base64')) {
        $text = base64_decode($text);
      }
      $decrypted_text = $service->decrypt($text, $encryption_profile);
      drush_print($decrypted_text);
    }
  }
  else {
    return drush_set_error('error', dt('Please specify the decryption profile and the encrypted text to be decrypted', []));
  }
}

/**
 * Validates the given encryption profile.
 *
 * @param string $encryption_profile_name
 *   The ID of the encryption profile to validate.
 *
 * @return array
 *   An error of validation errors for the given encryption profile.
 */
function drush_encrypt_validate_profile($encryption_profile_name = FALSE) {

  if ($encryption_profile_name) {
    $output = [];
    $encryption_profile = EncryptionProfile::load($encryption_profile_name);
    if (!$encryption_profile) {
      return drush_set_error('', dt('Encryption profile "@name" could not be loaded.', [
        '@name' => $encryption_profile_name,
      ]));
    }
    else {
      $errors = $encryption_profile->validate();
      if ($errors) {
        foreach ($errors as $error_msg) {
          $row = [
            'error' => $error_msg,
          ];
          $output[] = $row;
        }
        return $output;
      }
      else {
        \Drupal::logger('encrypt')->log('success', 'Encryption profile validates successfully.');
      }
    }
  }
  else {
    return drush_set_error('error', dt('Please specify the encryption profile to be validated', []));
  }
}
