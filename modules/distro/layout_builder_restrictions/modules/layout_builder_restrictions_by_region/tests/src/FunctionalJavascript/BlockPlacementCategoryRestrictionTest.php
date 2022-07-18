<?php

namespace Drupal\Tests\layout_builder_restrictions_by_region\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Demonstrate that blocks can be restricted by category.
 *
 * @group layout_builder_restrictions_by_region
 */
class BlockPlacementCategoryRestrictionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'layout_builder',
    'layout_builder_restrictions',
    'layout_builder_restrictions_by_region',
    'node',
    'field_ui',
    'block_content',
  ];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node bundle.
    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'administer node display',
      'administer node fields',
      'configure any layout',
      'configure layout builder restrictions',
      'create and edit custom blocks',
    ]));

    // Enable entity_view_mode_restriction_by_region plugin.
    // Disable entity_view_mode_restriction plugin.
    $layout_builder_restrictions_plugins = [
      'entity_view_mode_restriction' => [
        'weight' => 1,
        'enabled' => FALSE,
      ],
      'entity_view_mode_restriction_by_region' => [
        'weight' => 0,
        'enabled' => TRUE,
      ],
    ];
    $config = \Drupal::service('config.factory')->getEditable('layout_builder_restrictions.plugins');
    $config->set('plugin_config', $layout_builder_restrictions_plugins)->save();
  }

  /**
   * Verify that the UI can restrict blocks in Layout Builder settings tray.
   */
  public function testBlockRestriction() {
    $this->blockTestSetup();

    $this->getSession()->resizeWindow(1200, 4000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    // Checking is_enable will show allow_custom.
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');
    $assert_session->linkExists('Manage layout');

    // Only allow two-column layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layout-restriction-restricted"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layouts-layout-twocol-section"]');
    $element->click();

    // Switch to per-region restriction.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-per-region"]');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]', 'Restricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]', 'Unrestricted');
    $page->pressButton('Save');

    $this->clickLink('Manage layout');
    // Remove default one-column layout and replace with two-column layout.
    $this->clickLink('Remove Section 1');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Two column');
    $assert_session->assertWaitOnAjaxRequest();
    $element = $page->find('xpath', '//*[contains(@class, "ui-dialog-off-canvas")]//*[starts-with(@id,"edit-actions-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Select 'Add block' link in First region.
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Initially, the body field is available.
    $assert_session->linkExists('Body');
    // Initially, custom blocks instances are available.
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');
    // Initially, all inline block types are allowed.
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Basic');
    $assert_session->linkExists('Alternate');
    $page->pressButton('Close');
    $page->pressButton('Save');

    // Load Allowed Blocks form for First region.
    $this->drupalGet("$field_ui_prefix/display/default");
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-per-region"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Impose Custom Block type restrictions.
    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Restrict specific Content fields blocks:');
    $assert_session->checkboxChecked('Allow all existing & new Custom block types blocks.');
    $assert_session->checkboxNotChecked('Restrict specific Custom block types blocks:');

    // Set 'Content' fields category to be restricted.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-allowed-blocks-content-fields-restriction")]/input[@value="restrict_all"]');
    $element->click();
    // Set block types category to be restricted.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-allowed-blocks-custom-block-types-restriction")]/input[@value="restrict_all"]');
    $element->click();
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->drupalGet("$field_ui_prefix/display/default");
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default/layout");

    // Select 'Add block' link in First region.
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Body');
    $assert_session->linkNotExists('Basic Block 1');
    $assert_session->linkNotExists('Basic Block 2');
    $assert_session->linkNotExists('Alternate Block 1');
    // Inline block types are still allowed.
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Basic');
    $assert_session->linkExists('Alternate');
  }

  /**
   * Helper function to set up block restriction-related tests.
   */
  protected function blockTestSetup() {
    // Create 2 custom block types, with 3 block instances.
    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
    ]);
    $bundle->save();
    $bundle = BlockContentType::create([
      'id' => 'alternate',
      'label' => 'Alternate',
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());
    $blocks = [
      'Basic Block 1' => 'basic',
      'Basic Block 2' => 'basic',
      'Alternate Block 1' => 'alternate',
    ];
    foreach ($blocks as $info => $type) {
      $block = BlockContent::create([
        'info' => $info,
        'type' => $type,
        'body' => [
          [
            'value' => 'This is the block content',
            'format' => filter_default_format(),
          ],
        ],
      ]);
      $block->save();
      $blocks[$info] = $block->uuid();
    }
    $this->blocks = $blocks;
  }

}
