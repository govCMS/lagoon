<?php

namespace Drupal\Tests\entity_class_formatter\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * @group entity_class_formatter
 */
class EntityClassFormatterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_class_formatter',
    'field',
    'filter',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Define test class for entity field.
   */
  const ENTITY_CLASS = 'test-entity-field-class';

  /**
   * Define test class for string field.
   */
  const STRING_CLASS = 'test-string-field-class';

  /**
   * Define class prefix.
   */
  const CLASS_PREFIX = 'prefix-';

  /**
   * Define class suffix.
   */
  const CLASS_SUFFIX = '-suffix';

  /**
   * Define test attribute name.
   */
  const ATTR_NAME = 'data-test-attr';

  /**
   * Define test attribute value.
   */
  const ATTR_VALUE = 'test attribute value';

  /**
   * Define test class for referenced entity field.
   */
  const REFERENCED_CLASS = 'test-referenced-field-class';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $account = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($account);
  }

  /**
   * {@inheritdoc}
   */
  public function testEntityFieldClass() {
    $field_config = $this->createField('entity_reference');
    $this->createDisplay($field_config->getName());

    $node = $this->drupalCreateNode(['title' => self::ENTITY_CLASS]);

    $entity = $this->drupalCreateNode([
      $field_config->getName() => [
        0 => ['target_id' => $node->id()],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $class = self::CLASS_PREFIX . self::ENTITY_CLASS . self::CLASS_SUFFIX;
    $assert_session->elementExists('css', 'article.' . $class);
  }

  /**
   * {@inheritdoc}
   */
  public function testStringFieldClass() {
    $field_config = $this->createField('string');
    $this->createDisplay($field_config->getName());

    $entity = $this->drupalCreateNode([
      $field_config->getName() => [
        0 => ['value' => self::STRING_CLASS],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $class = self::CLASS_PREFIX . self::STRING_CLASS . self::CLASS_SUFFIX;
    $assert_session->elementExists('css', 'article.' . $class);
  }

  /**
   * {@inheritdoc}
   */
  public function testAttrValue() {
    $field_config = $this->createField('string');
    $this->createDisplay($field_config->getName(), self::ATTR_NAME);

    $entity = $this->drupalCreateNode([
      $field_config->getName() => [
        0 => ['value' => self::ATTR_VALUE],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $class = self::CLASS_PREFIX . self::ATTR_VALUE . self::CLASS_SUFFIX;
    $selector = 'article[' . self::ATTR_NAME . '="' . $class . '"]';
    $assert_session->elementExists('css', $selector);
  }

  /**
   * {@inheritdoc}
   */
  public function testReferencedFieldClass() {
    $field_config = $this->createField('entity_reference');
    $referenced_field_config = $this->createField('string');
    $this->createDisplay($field_config->getName(), '', $referenced_field_config->getName());

    $node = $this->drupalCreateNode([$referenced_field_config->getName() => self::REFERENCED_CLASS]);

    $entity = $this->drupalCreateNode([
      $field_config->getName() => [
        0 => ['target_id' => $node->id()],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $assert_session = $this->assertSession();
    $class = self::CLASS_PREFIX . self::REFERENCED_CLASS . self::CLASS_SUFFIX;
    $assert_session->elementExists('css', 'article.' . $class);
  }

  /**
   * Creates a field.
   *
   * @param string $field_type
   *   The type of field.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   The newly created field.
   */
  protected function createField($field_type) {
    $field_name = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => $field_name,
      'type' => $field_type,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $field_config->save();

    return $field_config;
  }

  /**
   * Creates a display and sets the formatter.
   *
   * @param string $field_name
   *   The name of field.
   * @param string $display_attr
   *   The display attribute name.
   * @param string $display_field
   *   The display field name.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The newly created display.
   */
  protected function createDisplay($field_name, $display_attr = '', $display_field = '') {
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'full',
      'status' => TRUE,
    ]);
    $display->setComponent($field_name, [
      'type' => 'entity_class_formatter',
      'settings' => [
        'prefix' => self::CLASS_PREFIX,
        'suffix' => self::CLASS_SUFFIX,
        'attr' => $display_attr,
        'field' => $display_field,
      ],
    ]);
    $display->save();

    return $display;
  }

}
