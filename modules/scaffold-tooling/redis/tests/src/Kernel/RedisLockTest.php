<?php

namespace Drupal\Tests\redis\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\KernelTests\Core\Lock\LockTest;
use Drupal\Tests\redis\Traits\RedisTestInterfaceTrait;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the Redis non-persistent lock backend.
 *
 * Extends the core test to include test coverage for lockMayBeAvailable()
 * method invoked on a non-yet acquired lock.
 *
 * @group redis
 */
class RedisLockTest extends LockTest {

  use RedisTestInterfaceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'redis',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    self::setUpSettings();
    parent::register($container);

    $container->register('lock', LockBackendInterface::class)
      ->setFactory([new Reference('redis.lock.factory'), 'get']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lock = $this->container->get('lock');
  }

  /**
   * {@inheritdoc}
   */
  public function testBackendLockRelease() {
    $redis_interface = self::getRedisInterfaceEnv();
    // Verify that the correct lock backend is being instantiated by the
    // factory.
    $this->assertInstanceOf('\Drupal\redis\Lock\\' . $redis_interface, $this->lock);

    // Verify that a lock that has never been acquired is marked as available.
    // @todo Remove this line when #3002640 lands.
    // @see https://www.drupal.org/project/drupal/issues/3002640
    $this->assertTrue($this->lock->lockMayBeAvailable('lock_a'));

    parent::testBackendLockRelease();
  }

}
