<?php

namespace Drupal\entity_hierarchy_microsite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class for a list builder for microsite entities.
 */
class MicrositeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Name'),
      'home' => $this->t('Home page'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'name' => $entity->toLink(NULL, 'edit-form'),
      'home' => $entity->getHome() ? $entity->getHome()->toLink() : $this->t('N/A'),
    ] + parent::buildRow($entity);
  }

}
