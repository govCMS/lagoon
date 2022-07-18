<?php

namespace Drupal\entity_hierarchy\Storage;

use Drupal\Core\Lock\LockBackendInterface;

/**
 * Defines a trait for locking tree operations.
 */
trait TreeLockTrait {

  /**
   * Lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * Gets lock backend.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *   Lock backend.
   */
  protected function lockBackend() {
    if (!isset($this->lockBackend)) {
      $this->lockBackend = \Drupal::lock();
    }
    return $this->lockBackend;
  }

  /**
   * Sets lock backend.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   Lock backend.
   *
   * @return $this
   */
  public function setLockBackend(LockBackendInterface $lockBackend) {
    $this->lockBackend = $lockBackend;
    return $this;
  }

  /**
   * Locks tree.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $entityTypeId
   *   Entity Type ID.
   *
   * @throws \Exception
   *   When lock cannot be acquired after 30 seconds.
   */
  protected function lockTree($fieldName, $entityTypeId) {
    $count = 0;
    while (!$this->lockBackend()->acquire($this->getLockName($fieldName, $entityTypeId))) {
      // Wait a while before trying again.
      sleep(1);
      $count++;
      if ($count === 30) {
        throw new \Exception('Unable to acquire lock to update tree.');
      }
    };
  }

  /**
   * Releases lock.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $entityTypeId
   *   Entity Type ID.
   */
  protected function releaseLock($fieldName, $entityTypeId) {
    $this->lockBackend()->release($this->getLockName($fieldName, $entityTypeId));
  }

  /**
   * Gets lock name.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $entityTypeId
   *   Entity Type ID.
   *
   * @return string
   *   Lock name.
   */
  protected function getLockName($fieldName, $entityTypeId) {
    return sprintf('%s_%s', $fieldName, $entityTypeId);
  }

}
