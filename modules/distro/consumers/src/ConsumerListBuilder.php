<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Access Token entities.
 */
class ConsumerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['uuid'] = $this->t('UUID');
    $header['label'] = $this->t('Label');
    $header['is_default'] = $this->t('Is Default?');
    $context = ['type' => 'header'];
    $this->moduleHandler()->alter('consumers_list', $header, $context);
    $header = $header + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\consumers\Entity\Consumer */
    $row['uuid'] = $entity->uuid();
    $row['label'] = $entity->toLink();
    $ops = [
      '#type' => 'operations',
      '#links' => [
        [
          'title' => $this->t('Make Default'),
          'url' => $entity->toUrl('make-default-form', [
            'query' => $this->getDestinationArray(),
          ]),
        ],
      ],
    ];
    $row['is_default'] = $entity->get('is_default')->value
      ? ['data' => $this->t('Default')]
      : ['data' => $ops];

    $context = ['type' => 'row', 'entity' => $entity];
    $this->moduleHandler()->alter('consumers_list', $row, $context);
    $row = $row + parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (
      $entity->access('update') &&
      $entity->hasLinkTemplate('make-default-form') &&
      !$entity->get('is_default')->value
    ) {
      $operations['make-default'] = [
        'title' => $this->t('Make Default'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('make-default-form')),
      ];
    }
    return $operations;
  }

}
