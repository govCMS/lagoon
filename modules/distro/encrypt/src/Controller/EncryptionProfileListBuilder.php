<?php

namespace Drupal\encrypt\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of encryption profile entities.
 */
class EncryptionProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new EncryptionProfileListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);
    $this->config = $config_factory->get('encrypt.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['encryption_method'] = $this->t('Encryption method');
    $header['key'] = $this->t('Key');
    if ($this->config->get('check_profile_status')) {
      $header['status'] = $this->t('Status');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();

    // Render encryption method row.
    if ($encryption_method = $entity->getEncryptionMethod()) {
      $row['encryption_method'] = $encryption_method->getLabel();
    }
    else {
      $row['encryption_method'] = $this->t('Error loading encryption method');
    }

    // Render encryption key row.
    if ($key = $entity->getEncryptionKey()) {
      $row['key'] = $key->label();
    }
    else {
      $row['key'] = $this->t('Error loading key');
    }

    // Render status report row.
    if ($this->config->get('check_profile_status')) {
      $errors = $entity->validate();
      $warnings = [];
      // Check if the encryption plugin is deprecated.
      if ($encryption_method->isDeprecated()) {
        $warnings[] = $this->t('The encryption plugin used in this encryption profile is deprecated.');
      }
      if (!empty($errors)) {
        $row['status']['data'] = [
          '#theme' => 'item_list',
          '#items' => $errors,
          '#attributes' => ["class" => ["color-error"]],
        ];
      }
      elseif (!empty($warnings)) {
        $row['status']['data'] = [
          '#theme' => 'item_list',
          '#items' => $warnings,
          '#attributes' => ["class" => ["color-warning"]],
        ];
      }
      else {
        $row['status'] = $this->t('OK');
      }
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('test-form')) {
      $operations['test'] = [
        'title' => $this->t('Test'),
        'weight' => 30,
        'url' => $entity->toUrl('test-form'),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No encryption profiles are available. <a href=":link">Add a profile</a>.', [':link' => Url::fromRoute('entity.encryption_profile.add_form')->toString()]);
    return $build;
  }

}
