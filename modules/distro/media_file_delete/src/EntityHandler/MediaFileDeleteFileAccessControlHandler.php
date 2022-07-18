<?php

namespace Drupal\media_file_delete\EntityHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessControlHandler;

/**
 * Defines a custom access handler for file entities.
 */
class MediaFileDeleteFileAccessControlHandler extends FileAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    if ($operation !== 'delete') {
      return $access;
    }
    if ($access->isForbidden()) {
      return AccessResult::allowedIfHasPermission($account, 'delete any file');
    }
    return $access;
  }

}
