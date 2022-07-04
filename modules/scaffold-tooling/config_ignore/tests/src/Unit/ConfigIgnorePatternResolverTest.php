<?php

namespace Drupal\Tests\config_ignore\Unit;

use Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the config ignore pattern resolver.
 *
 * @coversDefaultClass \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber
 *
 * @group config_ignore
 */
class ConfigIgnorePatternResolverTest extends UnitTestCase {

  /**
   * Tests the config ignore pattern resolver with an invalid patterns.
   *
   * @param string $pattern
   *   The pattern to be tested.
   * @param string $expected_exception_message
   *   The expected exception message.
   *
   * @throws \ReflectionException
   *   If the class does not exist.
   *
   * @covers ::getIgnoredConfigs
   * @dataProvider dataProviderTestInvalidPattern
   */
  public function testInvalidPattern($pattern, $expected_exception_message) {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $this->getIgnoredConfigs([$pattern], ['foo.bar']);
  }

  /**
   * Provides testing cases for ::testInvalidPattern().
   *
   * @return array
   *   A list of arrays. Each array has two items:
   *   - The config ignore pattern.
   *   - The expected exception message.
   *
   * @see self::testInvalidPattern()
   */
  public function dataProviderTestInvalidPattern() {
    return [
      'tilda & asterisk' => [
        '~foo.bar.*',
        "A config ignore pattern entry cannot contain both, '~' and '*'.",
      ],
      'asterisk in key' => [
        'foo.bar:key.*',
        "The key part of the config ignore pattern cannot contain the wildcard character '*'.",
      ],
    ];
  }

  /**
   * Tests the config ignore pattern resolver.
   *
   * @throws \ReflectionException
   *   If the class does not exist.
   *
   * @covers ::getIgnoredConfigs
   */
  public function testGetIgnoredConfigs() {
    $ignoredConfigs = $this->getIgnoredConfigs(
      // Ignored config patterns.
      [
        // Non-existing simple ignore pattern.
        'non.existing',
        // Simple ignore pattern.
        'foo.bar',
        // Suffix wildcard ignore pattern.
        'foo.bar.*',
        // Excluding foo.bar.suffix4.
        '~foo.bar.suffix4',
        // Prefix wildcard ignore pattern.
        '*.foo.bar',
        // Excluding prefix2.foo.bar.
        '~prefix2.foo.bar',
        // Middle wildcard ignore pattern.
        'foo.*.bar',
        // Excluding foo.middle1.bar.
        '~foo.middle1.bar',
        // Ignore pattern with key.
        'foo.baz.qux:path.to.key',
        // A 2nd key of the same config is appended.
        'foo.baz.qux:a.second.key',
        // Ignore pattern with key when the same config has been already added.
        'foo.bar:some.key',
        // Ignore pattern with key that will be overwritten later with the same
        // config but without key.
        'baz.qux:with.some.key',
        // Only this will be outputted as it covers also the one with a key.
        'baz.qux',
      ],
      // All configs.
      [
        'foo.bar',
        'foo.bar.suffix1',
        'foo.bar.suffix2',
        'foo.bar.suffix3',
        'foo.bar.suffix4',
        'prefix1.foo.bar',
        'prefix2.foo.bar',
        'prefix3.foo.bar',
        'foo.middle1.bar',
        'foo.middle2.bar',
        'foo.middle3.bar',
        'foo.baz.qux',
        'baz.qux',
      ]
    );

    $this->assertSame([
      'foo.bar' => NULL,
      'foo.bar.suffix1' => NULL,
      'foo.bar.suffix2' => NULL,
      'foo.bar.suffix3' => NULL,
      'prefix1.foo.bar' => NULL,
      'prefix3.foo.bar' => NULL,
      'foo.middle2.bar' => NULL,
      'foo.middle3.bar' => NULL,
      'foo.baz.qux' => [
        ['path', 'to', 'key'],
        ['a', 'second', 'key'],
      ],
      'baz.qux' => NULL,
    ], $ignoredConfigs);
  }

  /**
   * Returns all ignored configs by expanding the wildcards.
   *
   * Basically, it provides mocked services and it's a wrapper around the
   * protected method ConfigIgnoreEventSubscriber::getIgnoredConfigs().
   *
   * @param array $ignore_config_patterns
   *   A list of config ignore patterns.
   * @param array $all_configs
   *   A list of names of all configs.
   *
   * @return array
   *   A list of ignored configs as is returned by
   *   ConfigIgnoreEventSubscriber::getIgnoredConfigs()
   *
   * @throws \ReflectionException
   *   If the class does not exist.
   *
   * @see \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber::getIgnoredConfigs()
   */
  protected function getIgnoredConfigs(array $ignore_config_patterns, array $all_configs) {
    $configIgnoreSettings = $this->prophesize(ImmutableConfig::class);
    $configIgnoreSettings->get('ignored_config_entities')->willReturn($ignore_config_patterns);

    $configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $configFactory->get('config_ignore.settings')->willReturn($configIgnoreSettings->reveal());

    $transformation_storage = $this->prophesize(StorageInterface::class);
    $transformation_storage->listAll()->willReturn($all_configs);

    $subscriber = new ConfigIgnoreEventSubscriber(
      $configFactory->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->prophesize(StorageInterface::class)->reveal(),
      $this->prophesize(StorageInterface::class)->reveal()
    );

    // Make ConfigIgnoreEventSubscriber::getIgnoredConfigs() accessible.
    $class = new \ReflectionClass($subscriber);
    $getIgnoredConfigsMethod = $class->getMethod('getIgnoredConfigs');
    $getIgnoredConfigsMethod->setAccessible(TRUE);

    return $getIgnoredConfigsMethod->invokeArgs($subscriber, [$transformation_storage->reveal()]);
  }

}
