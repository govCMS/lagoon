<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Access Token entity.
 *
 * @see \Drupal\consumers\Entity\Consumer.
 */
class AccessControlHandler extends EntityAccessControlHandler {

  public static $name = 'consumer';

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\consumers\Entity\Consumer $entity */
    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Permissions only apply to own entities.
    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
    $is_owner_access = AccessResult::allowedIf($is_owner)
      ->addCacheableDependency($entity);
    $operations = ['view', 'update', 'delete'];
    if (!in_array($operation, $operations)) {
      $reason = sprintf(
        'Supported operations on the entity are %s',
        implode(', ', $operations)
      );
      return AccessResult::neutral($reason);
    }

    return $is_owner_access->andIf(AccessResult::allowedIfHasPermission(
      $account,
      sprintf('%s own %s entities', $operation, static::$name)
    )->cachePerPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, sprintf('add %s entities', static::$name));
  }

}
