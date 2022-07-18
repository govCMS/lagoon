<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for displaying entities.
 *
 * @group field_group
 */
class EntityDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_test',
    'field_ui',
    'field_group',
    'field_group_test',
  ];

  /**
   * The node type id.
   *
   * @var string
   */
  protected $type;

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->type = $type->id();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    // Create a node.
    $node_values = ['type' => $type_name];

    // Create test fields.
    foreach (['field_test', 'field_test_2', 'field_no_access'] as $field_name) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'test_field',
      ]);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type_name,
        'label' => $this->randomMachineName(),
      ]);
      $instance->save();

      // Assign a test value for the field.
      $node_values[$field_name][0]['value'] = mt_rand(1, 127);

      // Set the field visible on the display object.
      $display_options = [
        'label' => 'above',
        'type' => 'field_test_default',
        'settings' => [
          'test_formatter_setting' => $this->randomMachineName(),
        ],
      ];
      $display->setComponent($field_name, $display_options);
    }

    // Save display + create node.
    $display->save();
    $this->node = $this->drupalCreateNode($node_values);
  }

  /**
   * Test field access for field groups.
   */
  public function testFieldAccess() {
    $data = [
      'label' => 'Wrapper',
      'children' => [
        0 => 'field_no_access',
      ],
      'format_type' => 'html_element',
      'format_settings' => [
        'element' => 'div',
        'id' => 'wrapper-id',
      ],
    ];

    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());

    // Test if group is not shown.
    $this->assertEmpty($this->xpath("//div[contains(@id, 'wrapper-id')]"), t('Div that contains fields with no access is not shown.'));
  }

  /**
   * Test the html element formatter.
   */
  public function testHtmlElement() {
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => 'Link',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
      ],
    ];
    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    // $groups =
    // field_group_info_groups('node', 'article', 'view', 'default', TRUE);.
    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertCount(1, $this->xpath("//div[contains(@id, 'wrapper-id')]"), 'Wrapper id set on wrapper div');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class')]"), 'Test class set on wrapper div, class="' . $group->group_name . ' test-class');

    // Test group label.
    $this->assertSession()->responseNotContains('<h3><span>' . $data['label'] . '</span></h3>');

    // Set show label to true.
    $group->format_settings['show_label'] = TRUE;
    $group->format_settings['label_element'] = 'h3';
    $group->format_settings['label_element_classes'] = 'my-label-class';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->responseContains('<h3 class="my-label-class">' . $data['label'] . '</h3>');

    // Change to collapsible with blink effect.
    $group->format_settings['effect'] = 'blink';
    $group->format_settings['speed'] = 'fast';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'speed-fast')]"), 'Speed class is set');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'effect-blink')]"), 'Effect class is set');
  }

  /**
   * Test the fieldset formatter.
   */
  public function testFieldset() {
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => 'Test Fieldset',
      'format_type' => 'fieldset',
      'format_settings' => [
        'id' => 'fieldset-id',
        'classes' => 'test-class',
        'description' => 'test description',
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertCount(1, $this->xpath("//fieldset[contains(@id, 'fieldset-id')]"), 'Correct id set on the fieldset');
    $this->assertCount(1, $this->xpath("//fieldset[contains(@class, 'test-class')]"), 'Test class set on the fieldset');
  }

  /**
   * Test the tabs formatter.
   */
  public function testTabs() {
    $data = [
      'label' => 'Tab 1',
      'weight' => '1',
      'children' => [
        0 => 'field_test',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 1',
        'classes' => 'test-class',
        'description' => '',
        'formatter' => 'open',
      ],
    ];
    $first_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tab 2',
      'weight' => '1',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 1',
        'classes' => 'test-class-2',
        'description' => 'description of second tab',
        'formatter' => 'closed',
      ],
    ];
    $second_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tabs',
      'weight' => '1',
      'children' => [
        0 => $first_tab->group_name,
        1 => $second_tab->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'vertical',
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $tabs_group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test properties.
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]"), 'Test class set on tabs wrapper');
    $this->assertCount(1, $this->xpath("//details[contains(@class, 'test-class-2')]"), 'Test class set on second tab');
    $this->assertSession()->responseContains('<div class="details-description">description of second tab</div>');

    // Test if correctly nested.
    $this->assertCount(2, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class')]"), 'First tab is displayed as child of the wrapper.');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class-2')]"), 'Second tab is displayed as child of the wrapper.');

    // Test if it's a vertical tab.
    $this->assertCount(1, $this->xpath('//div[@data-vertical-tabs-panes=""]'), 'Tabs are shown vertical.');

    // Switch to horizontal.
    $tabs_group->format_settings['direction'] = 'horizontal';
    field_group_group_save($tabs_group);

    $this->drupalGet('node/' . $this->node->id());

    // Test if it's a horizontal tab.
    $this->assertCount(1, $this->xpath('//div[@data-horizontal-tabs-panes=""]'), 'Tabs are shown horizontal.');
  }

  /**
   * Test the accordion formatter.
   */
  public function testAccordion() {
    $data = [
      'label' => 'Accordion item 1',
      'weight' => '1',
      'children' => [
        0 => 'field_test',
      ],
      'format_type' => 'accordion_item',
      'format_settings' => [
        'label' => 'Accordion item 1',
        'classes' => 'test-class',
        'formatter' => 'closed',
      ],
    ];
    $first_item = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Accordion item 2',
      'weight' => '1',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'accordion_item',
      'format_settings' => [
        'label' => 'Tab 2',
        'classes' => 'test-class-2',
        'formatter' => 'open',
      ],
    ];
    $second_item = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Accordion',
      'weight' => '1',
      'children' => [
        0 => $first_item->group_name,
        1 => $second_item->group_name,
      ],
      'format_type' => 'accordion',
      'format_settings' => [
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
        'effect' => 'bounceslide',
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test properties.
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]"), 'Test class set on tabs wrapper');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'effect-bounceslide')]"), 'Correct effect is set on the accordion');
    $this->assertCount(3, $this->xpath("//div[contains(@class, 'test-class')]"), 'Accordion item with test-class is shown');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-2')]"), 'Accordion item with test-class-2 is shown');
    $this->assertCount(1, $this->xpath("//h3[contains(@class, 'field-group-accordion-active')]"), 'Accordion item 2 was set active');

    // Test if correctly nested.
    $this->assertCount(2, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//div[contains(@class, 'test-class')]"), 'First item is displayed as child of the wrapper.');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//div[contains(@class, 'test-class-2')]"), 'Second item is displayed as child of the wrapper.');
  }

}
