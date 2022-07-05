<?php

namespace Drupal\Tests\key\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;

/**
 * Defines a test for key multi-values.
 *
 * @group key
 */
class KeyMultiValueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'key_test',
    'system',
    'user',
  ];

  /**
   * Tests that multi-value keys get serialized.
   */
  public function testMultiValuesAreSerialized() {
    $key = Key::create([
      'id' => 'multi',
      'label' => 'Multi',
      'description' => 'Multi-value key',
      'key_type' => 'key_test_multi',
      'key_type_settings' => [],
      'key_provider' => 'key_test_state',
      'key_provider_settings' => [
        'state_key' => 'test_multivalue',
      ],
      'key_input' => 'key_test_multi',
      'key_test_multi_settings' => [
        'first' => 'something',
        'second' => 'else',
      ],
    ]);
    $key->setKeyValue([
      'first' => 'woof',
      'second' => 'bark',
    ]);
    $key->save();
    $this->assertEquals(json_encode(['first' => 'woof', 'second' => 'bark']), \Drupal::state()->get('key_test:test_multivalue'));
  }

}
