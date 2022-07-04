<?php

namespace Drupal\Tests\redis\Functional\Lock;

use Drupal\Component\Utility\OpCodeCache;
use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\Tests\system\Functional\Lock\LockFunctionalTest;
use Drupal\Tests\redis\Traits\RedisTestInterfaceTrait;

/**
 * Confirm locking works between two separate requests.
 *
 * @group redis
 */
class RedisLockFunctionalTest extends LockFunctionalTest {

  use RedisTestInterfaceTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['redis'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Write the containers_yaml update by hand, since writeSettings() doesn't
    // support this syntax.
    $filename = $this->siteDirectory . '/settings.php';
    chmod($filename, 0666);
    $contents = file_get_contents($filename);
    $redis_interface = self::getRedisInterfaceEnv();
    $module_path = drupal_get_path('module', 'redis');
    $contents .= "\n\n" . "\$settings['container_yamls'][] = '$module_path/example.services.yml';";
    $contents .= "\n\n" . '$settings["redis.connection"]["interface"] = \'' . $redis_interface . '\';';
    file_put_contents($filename, $contents);
    $settings = Settings::getAll();
    $settings['container_yamls'][] = $module_path . '/example.services.yml';
    $settings['redis.connection']['interface'] = $redis_interface;
    new Settings($settings);
    OpCodeCache::invalidate(DRUPAL_ROOT . '/' . $filename);

    $this->rebuildContainer();

    // Get database schema.
    $db_schema = Database::getConnection()->schema();
    // Make sure that the semaphore table isn't used.
    $db_schema->dropTable('semaphore');
  }

  /**
   * {@inheritdoc}
   */
  public function testLockAcquire() {
    $redis_interface = self::getRedisInterfaceEnv();
    $lock = $this->container->get('lock');
    $this->assertInstanceOf('\Drupal\redis\Lock\\' . $redis_interface, $lock);

    // Verify that a lock that has never been acquired is marked as available.
    // @todo Remove this line when #3002640 lands.
    // @see https://www.drupal.org/project/drupal/issues/3002640
    $this->assertTrue($lock->lockMayBeAvailable('system_test_lock_acquire'));

    parent::testLockAcquire();
  }

  /**
   * {@inheritdoc}
   */
  public function testPersistentLock() {
    $redis_interface = self::getRedisInterfaceEnv();
    $persistent_lock = $this->container->get('lock.persistent');
    $this->assertInstanceOf('\Drupal\redis\PersistentLock\\' . $redis_interface, $persistent_lock);

    // Verify that a lock that has never been acquired is marked as available.
    // @todo Remove this line when #3002640 lands.
    // @see https://www.drupal.org/project/drupal/issues/3002640
    $this->assertTrue($persistent_lock->lockMayBeAvailable('lock1'));

    parent::testPersistentLock();
  }

}
