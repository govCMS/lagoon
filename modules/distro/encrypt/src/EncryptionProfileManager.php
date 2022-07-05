<?php

namespace Drupal\encrypt;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines an EncryptionProfile manager.
 */
class EncryptionProfileManager implements EncryptionProfileManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Construct the EncryptionProfileManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionProfile($encryption_profile_id) {
    return $this->entityManager->getStorage('encryption_profile')->load($encryption_profile_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllEncryptionProfiles() {
    return $this->entityManager->getStorage('encryption_profile')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionProfilesByEncryptionMethod($encryption_method_id) {
    return $this->entityManager->getStorage('encryption_profile')->loadByProperties(['encryption_method' => $encryption_method_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionProfilesByEncryptionKey($key_id) {
    return $this->entityManager->getStorage('encryption_profile')->loadByProperties(['encryption_key' => $key_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionProfileNamesAsOptions() {
    $options = [];
    $encryption_profiles = $this->getAllEncryptionProfiles();

    if ($encryption_profiles) {
      foreach ($encryption_profiles as $encryption_profile) {
        $options[$encryption_profile->id()] = $encryption_profile->label();
      }
    }

    return $options;
  }

}
