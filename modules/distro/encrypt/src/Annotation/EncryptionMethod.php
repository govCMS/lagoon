<?php

namespace Drupal\encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EncryptionMethod annotation object.
 *
 * @ingroup encrypt
 *
 * @Annotation
 */
class EncryptionMethod extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the encryption method.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Define key type(s) this encryption method should be restricted to.
   *
   * Return an array of KeyType plugin IDs that restrict the allowed key types
   * for usage with this encryption method.
   *
   * @var array
   */
  public $key_type = [];

  /**
   * Define if the encryption method can also decrypt.
   *
   * In some scenario the key linked to the encryption method may not be able
   * to decrypt, i.e. for asymmetrical encryption methods, where the key is a
   * public key.
   *
   * @var bool
   */
  public $can_decrypt = TRUE;

  /**
   * Define if the encryption method is considered deprecated.
   *
   * As time passes, some encryption methods become obsolete, and it is
   * necessary that they no longer be used to create new encryption profiles.
   * Encryption methods marked deprecated can only be used with existing
   * profiles, and the user will be alerted to change to a better method.
   *
   * @var bool
   */
  public $deprecated = FALSE;

}
