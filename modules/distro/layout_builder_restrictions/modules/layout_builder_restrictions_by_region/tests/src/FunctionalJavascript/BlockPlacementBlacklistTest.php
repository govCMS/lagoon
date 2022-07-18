<?php

namespace Drupal\Tests\layout_builder_restrictions_by_region\FunctionalJavascript;

use Drupal\Tests\layout_builder_restrictions\FunctionalJavascript\LayoutBuilderRestrictionsTestBase;

/**
 * Demonstrate that blocks can be individually restricted.
 *
 * @group layout_builder_restrictions_by_region
 */
class BlockPlacementBlacklistTest extends LayoutBuilderRestrictionsTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
   * Verify that both tempstore and config storage function correctly.
   */
  public function testBlockRestrictionStorage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Only allow two-column layout.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layout-restriction-restricted"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layouts-layout-twocol-section"]');
    $element->click();

    // Verify form behavior when restriction is applied to all regions.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-all');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]', 'All regions');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');

    // Verify form behavior when restriction is applied on a per-region basis.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-per-region"]');
    $element->click();
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-per-region');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]', 'First');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]', 'Unrestricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]', 'Second');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]', 'Unrestricted');

    // Test temporary storage.
    // Add restriction to First region.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Allow specific Content fields blocks:');

    // Restrict all 'Content' fields from options.
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-allowed-blocks-content-fields-restriction-whitelisted--")]');
    $element->click();
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Verify First region is 'Restricted' and Second region
    // remains 'Unrestricted'.
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]', 'Restricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]', 'Unrestricted');

    // Reload First region allowed block form to verify temp storage.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxNotChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxChecked('Allow specific Content fields blocks:');
    $page->pressButton('Close');

    // Load Second region allowed block form to verify temp storage.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Allow specific Content fields blocks:');
    $page->pressButton('Close');

    // Verify All Regions unaffected.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-all"]');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Allow specific Content fields blocks:');
    $page->pressButton('Close');

    // Switch back to Per-region restrictions prior to saving.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-per-region"]');
    $element->click();

    // Allow one-column layout.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layouts-layout-onecol"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-onecol"]/summary');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-onecol-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');
    // Save to config.
    $page->pressButton('Save');

    // Verify no block restrictions bleed to other layouts/regions upon save
    // to database.
    $this->navigateToManageDisplay();
    // Check two-column layout.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]', 'Restricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]', 'Unrestricted');

    // Verify All Regions unaffected.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-all"]');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');

    // Check one-column layout.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-onecol"]/summary');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-onecol-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');
  }

  /**
   * Verify that the UI can restrict blocks in Layout Builder settings tray.
   */
  public function testBlockRestriction() {
    $blocks = $this->generateTestBlocks();
    $node_id = $this->generateTestNode();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Only allow two-column layout.
    $this->navigateToManageDisplay();
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

    $this->navigateToNodeLayout($node_id);
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
    $this->navigateToManageDisplay();
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

    // Set 'Content' fields to blacklisted, but do not restrict.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-allowed-blocks-content-fields-restriction")]/input[@value="blacklisted"]');
    $element->click();
    // Set block types to blacklisted, but do not restrict.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-allowed-blocks-custom-block-types-restriction")]/input[@value="blacklisted"]');
    $element->click();
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->navigateToNodeLayout($node_id);
    // Select 'Add block' link in First region.
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Body');
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');
    // Inline block types are still allowed.
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Basic');
    $assert_session->linkExists('Alternate');

    // Impose Inline Block type & Content restrictions.
    // Load Allowed Blocks form for First region.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->checkboxChecked('Restrict specific Content fields blocks:');
    $assert_session->checkboxNotChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxChecked('Allow all existing & new Inline blocks blocks.');
    $assert_session->checkboxNotChecked('Restrict specific Inline blocks blocks:');

    // Blacklist inline, custom, and content blocks.
    $element = $page->find('xpath', '//*[starts-with(@id, "edit-allowed-blocks-inline-blocks-restriction-blacklisted--")]');
    $element->click();
    $element = $page->find('xpath', '//*[starts-with(@id, "edit-allowed-blocks-custom-blocks-restriction-blacklisted--")]');
    $element->click();
    $inline_blocks = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-inline-blocks-allowed-blocks-inline-block")]');
    foreach ($inline_blocks as $block) {
      $block->click();
    }
    $content_fields = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-content-fields-allowed-blocks-")]');
    foreach ($content_fields as $block) {
      $block->click();
    }
    $custom_blocks = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-custom-blocks-allowed-blocks-")]');
    foreach ($custom_blocks as $block) {
      $block->click();
    }
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    // Check independent restrictions on Custom block and Inline blocks.
    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->linkNotExists('Body');
    $assert_session->linkNotExists('Basic Block 1');
    $assert_session->linkNotExists('Basic Block 2');
    $assert_session->linkNotExists('Alternate Block 1');
    // Inline block types are not longer allowed.
    $assert_session->linkNotExists('Create custom block');

    // Blacklist some blocks / block types.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxChecked('Restrict specific Content fields blocks:');

    // Un-blaclist the 'body' field as an option.
    $page->uncheckField('allowed_blocks[Content fields][allowed_blocks][field_block:node:bundle_with_section_field:body]');
    // Un-blacklist "basic" Custom block types.
    $page->uncheckField('allowed_blocks[Custom block types][allowed_blocks][basic]');
    // Un-blacklist all custom blocks.
    $custom_blocks = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-custom-blocks-allowed-blocks-")]');
    foreach ($custom_blocks as $block) {
      $block->click();
    }
    // Un-blacklist "alternate" Inline block type.
    $page->uncheckField('allowed_blocks[Inline blocks][allowed_blocks][inline_block:alternate]');

    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->navigateToNodeSettingsTray($node_id);
    $assert_session->linkExists('Body');
    // ... but other 'content' fields aren't.
    $assert_session->linkNotExists('Promoted to front page');
    $assert_session->linkNotExists('Sticky at top of lists');
    // "Basic" Custom blocks are allowed.
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    // Only the basic inline block type is allowed.
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Basic');
    $assert_session->linkExists('Alternate');

    // Custom block instances take precedence over custom block type setting.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $element = $page->find('xpath', '//*[starts-with(@id, "edit-allowed-blocks-custom-blocks-restriction-blacklisted--")]');
    $element->click();
    $custom_blocks = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-custom-blocks-allowed-blocks-")]');
    foreach ($custom_blocks as $block) {
      $block->click();
    }
    // Allow Alternate Block 1.
    $page->uncheckField('allowed_blocks[Custom blocks][allowed_blocks][block_content:' . $blocks['Alternate Block 1'] . ']');
    // Allow Basic Block 1.
    $page->uncheckField('allowed_blocks[Custom blocks][allowed_blocks][block_content:' . $blocks['Basic Block 1'] . ']');
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkNotExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');

    // Next, add restrictions to another region and verify no contamination
    // between regions.
    // Add restriction to Second region.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="second"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    // System blocks are disallowed.
    $element = $page->find('xpath', '//*[starts-with(@id, "edit-allowed-blocks-system-restriction-whitelisted--")]');
    $element->click();
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Powered by Drupal');
    $page->pressButton('Close');

    $element = $page->find('xpath', '//*[contains(@class, "layout__region--second")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Powered by Drupal');
    $page->pressButton('Close');

    // Next, allow a three-column layout and verify no contamination.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-layouts-layouts-layout-threecol-section"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section"]/summary');
    $element->click();
    // Restrict on a per-region basis.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-restriction-behavior-per-region"]');
    $element->click();

    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="first"]', 'First');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="first"]', 'Unrestricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="second"]', 'Second');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="second"]', 'Unrestricted');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="third"]', 'Third');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="third"]', 'Unrestricted');

    // Manage restrictions for First region.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="first"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Allow specific Content fields blocks:');
    $assert_session->checkboxChecked('Allow all existing & new Custom blocks blocks.');
    $assert_session->checkboxNotChecked('Allow specific Custom blocks blocks:');
    $assert_session->checkboxChecked('Allow all existing & new Inline blocks blocks.');
    $assert_session->checkboxNotChecked('Allow specific Inline blocks blocks:');
    $assert_session->checkboxChecked('Allow all existing & new System blocks.');
    $assert_session->checkboxNotChecked('Allow specific System blocks:');
    $assert_session->checkboxChecked('Allow all existing & new core blocks.');
    $assert_session->checkboxNotChecked('Allow specific core blocks:');

    // Disallow Core blocks in the ThreeCol first region.
    $element = $page->find('xpath', '//*[starts-with(@id, "edit-allowed-blocks-core-restriction-blacklisted--")]');
    $element->click();
    $core_blocks = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-core-blocks-allowed-blocks-")]');
    foreach ($core_blocks as $block) {
      $block->click();
    }
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="third"]', 'Third');
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-threecol-section-table"]/tbody/tr[@data-region="third"]', 'Restricted');
    $page->pressButton('Save');

    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Primary admin actions');
    $page->pressButton('Close');

    $element = $page->find('xpath', '//*[contains(@class, "layout__region--second")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Primary admin actions');
    $page->pressButton('Close');

    // Add three-column layout below existing section.
    $element = $page->find('xpath', '//*[@data-layout-builder-highlight-id="section-1"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Three column');
    $assert_session->assertWaitOnAjaxRequest();
    $element = $page->find('xpath', '//*[contains(@class, "ui-dialog-off-canvas")]//*[starts-with(@id,"edit-actions-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $this->navigateToNodeLayout($node_id);
    // Verify core blocks are unavailable to First region in
    // three-column layout.
    $element = $page->find('xpath', '//*[contains(@class, "layout--threecol-section")]/*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->linkNotExists('Primary admin actions');

    // Finally, test all_regions functionality.
    $this->navigateToManageDisplay();

    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    // Switch Two-column layout restrictions to all regions.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-restriction-behavior-all"]');
    $element->click();
    $assert_session->elementContains('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]', 'Unrestricted');
    $page->pressButton('Save');

    // Verify no restrictions.
    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout--twocol-section")]/*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Promoted to front page');
    $page->pressButton('Close');
    $assert_session->assertWaitOnAjaxRequest();

    $element = $page->find('xpath', '//*[contains(@class, "layout--twocol-section")]/*[contains(@class, "layout__region--second")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkExists('Promoted to front page');
    $page->pressButton('Close');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    // Add a restriction for all_regions.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-by-layout-layout-twocol-section-table"]/tbody/tr[@data-region="all_regions"]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->checkboxChecked('Allow all existing & new Content fields blocks.');
    $assert_session->checkboxNotChecked('Restrict specific Content fields blocks:');

    $element = $page->find('xpath', '//*[contains(@class, "form-item-allowed-blocks-content-fields-restriction")]/input[@value="blacklisted"]');
    $element->click();
    $content_fields = $page->findAll('xpath', '//*[starts-with(@id, "edit-allowed-blocks-content-fields-allowed-blocks-")]');
    foreach ($content_fields as $block) {
      $block->click();
    }
    $element = $page->find('xpath', '//*[starts-with(@id,"edit-submit--")]');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    // Verify restrictions applied to both regions.
    $this->navigateToNodeLayout($node_id);
    $element = $page->find('xpath', '//*[contains(@class, "layout--twocol-section")]/*[contains(@class, "layout__region--first")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Promoted to front page');
    $page->pressButton('Close');
    $assert_session->assertWaitOnAjaxRequest();

    $element = $page->find('xpath', '//*[contains(@class, "layout--twocol-section")]/*[contains(@class, "layout__region--second")]//a');
    $element->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->linkNotExists('Promoted to front page');
    $page->pressButton('Close');
    $assert_session->assertWaitOnAjaxRequest();

    $page->pressButton('Save');
  }

}
