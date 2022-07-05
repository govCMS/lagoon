<?php

namespace Drupal\encrypt;

/**
 * Provides an interface defining an EncryptionProfile manager.
 */
interface EncryptionProfileManagerInterface {

  /**
   * Get an encryption profile based on the ID.
   *
   * @param string $encryption_profile_id
   *   ID of EncryptionProfile entity.
   *
   * @return \Drupal\encrypt\EncryptionProfileInterface
   *   The EncryptionProfile entity.
   */
  public function getEncryptionProfile($encryption_profile_id);

  /**
   * Get all EncryptionProfile entities.
   *
   * @return \Drupal\encrypt\EncryptionProfileInterface[]
   *   An array of all EncryptionProfile entities.
   */
  public function getAllEncryptionProfiles();

  /**
   * Get EncryptionProfile entities by encryption method plugin ID.
   *
   * @param string $encryption_method_id
   *   The plugin ID of the EncryptionMethod.
   *
   * @return \Drupal\encrypt\EncryptionProfileInterface[]
   *   An array of EncryptionProfile entities.
   */
  public function getEncryptionProfilesByEncryptionMethod($encryption_method_id);

  /**
   * Get EncryptionProfile entities by encryption Key entity ID.
   *
   * @param string $key_id
   *   The plugin ID of the EncryptionMethod.
   *
   * @return \Drupal\encrypt\EncryptionProfileInterface[]
   *   An array of EncryptionProfile entities.
   */
  public function getEncryptionProfilesByEncryptionKey($key_id);

  /**
   * Get EncryptionProfiles as options list for a select element.
   *
   * @return array
   *   An array of encryption profile names, indexed by id.
   */
  public function getEncryptionProfileNamesAsOptions();

}
