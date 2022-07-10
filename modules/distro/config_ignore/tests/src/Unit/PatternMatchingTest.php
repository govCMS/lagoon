<?php

namespace Drupal\Tests\config_ignore\Unit;

use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Tests\UnitTestCase;

/**
 * Test the pattern matching.
 *
 * This is a unit test which tests the protected method, but it is much faster.
 *
 * @group config_ignore_new
 */
class PatternMatchingTest extends UnitTestCase {

  /**
   * Test the ignored config matching against names.
   *
   * @param array $patterns
   *   The config of ignored names.
   * @param array $test
   *   An array with config names as keys and the expected outcome as value.
   *
   * @dataProvider patternProvider
   */
  public function testPatternMatching(array $patterns, array $test) {

    $filter = new IgnoreFilter(['ignored' => $patterns], 'config_ignore', [], new MemoryStorage());

    // In order to test much faster we access the protected method.
    $method = new \ReflectionMethod(IgnoreFilter::class, 'matchConfigName');
    $method->setAccessible(TRUE);
    foreach ($test as $name => $expected) {
      static::assertEquals($expected, $method->invoke($filter, $name), $name);
    }

    if (!in_array(TRUE, $test, TRUE) || !in_array(FALSE, $test, TRUE)) {
      // Make sure there is always a positive and negative test.
      $this->markAsRisky();
    }
  }

  /**
   * Get the ignored config and test against the names.
   *
   * @return array
   *   The patterns and what should and shouldn't match.
   */
  public function patternProvider() {
    // For each pattern there needs to be a positive and a negative case.
    return [
      'system.site ignored' => [
        ['system.site'],
        [
          'system.site' => TRUE,
          'system.performance' => FALSE,
        ],
      ],
      'system ignored' => [
        ['system.*'],
        [
          'system.site' => TRUE,
          'system.performance' => TRUE,
          'node.site' => FALSE,
        ],
      ],
      'site ignored' => [
        ['*.site'],
        [
          'system.site' => TRUE,
          'system.performance' => FALSE,
          'other.site' => TRUE,
        ],
      ],
      'node ignored' => [
        ['node.*'],
        [
          'system.site' => FALSE,
          'node.settings' => TRUE,
          'node.settings.other' => TRUE,
        ],
      ],
      'middle ignored' => [
        ['start.*.end'],
        [
          'start.something' => FALSE,
          'start.something.end' => TRUE,
          'start.something.else.end' => TRUE,
          'start.something.ending' => FALSE,
        ],
      ],
      'enforced excluded' => [
        ['system.*', '~system.site'],
        [
          'system.site' => FALSE,
          'system.performance' => TRUE,
        ],
      ],
      'system sub-key ignored' => [
        ['system.*:foo'],
        [
          'system.site' => TRUE,
          'system.performance' => TRUE,
          'node.foo' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Test the cases that are not allowed in testPatternMatching.
   */
  public function testEverythingAndNothing() {
    $method = new \ReflectionMethod(IgnoreFilter::class, 'matchConfigName');
    $method->setAccessible(TRUE);

    $none = new IgnoreFilter(['ignored' => []], 'config_ignore', [], new MemoryStorage());
    $all = new IgnoreFilter(['ignored' => ['*']], 'config_ignore', [], new MemoryStorage());

    static::assertFalse($method->invoke($none, $this->randomMachineName()));
    static::assertTrue($method->invoke($all, $this->randomMachineName()));
  }

}
