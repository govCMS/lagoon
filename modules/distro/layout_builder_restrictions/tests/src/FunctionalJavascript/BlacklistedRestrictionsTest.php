<?php

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

/**
 * Demonstrate that blocks can be individually blacklisted.
 *
 * @group layout_builder_restrictions
 */
class BlacklistedRestrictionsTest extends LayoutBuilderRestrictionsTestBase {

  /**
   * Verify that the UI can restrict blocks in Layout Builder settings tray.
   */
  public function testBlockRestriction() {
    // Create 2 custom block types, with 3 block instances.
    $blocks = $this->generateTestBlocks();
    $node_id = $this->generateTestNode();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Establish baseline behavior prior to any restrictions.
    $this->navigateToNodeSettingsTray($node_id);
    // Initially, the body field is available.
    $assert_session->linkExists('Body');
    // Initially, custom blocks instances are available.
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');
    // Initially, all inline block types are allowed.
    $this->clickLink('Create custom block');
    $this->assertNotEmpty($assert_session->waitForText('Add a new custom block'));
    $assert_session->linkExists('Basic');
    $assert_session->linkExists('Alternate');

    // Impose Custom Block type restrictions.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all"]');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-blacklisted');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-inline-blocks-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-inline-blocks-restriction-blacklisted');
    // Restrict all 'Content' fields from options.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-blacklisted"]');
    $element->click();
    // Restrict all Custom block types from options.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-block-types-restriction-blacklisted"]');
    $element->click();
    $page->pressButton('Save');

    // The 'body' field is still present since it has not been blacklisted.
    $this->navigateToNodeSettingsTray($node_id);
    // The "body" field is still present.
    $assert_session->linkExists('Body');
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');
    // Inline block types are still allowed.
    $this->clickLink('Create custom block');
    $this->assertNotEmpty($assert_session->waitForText('Add a new custom block'));
    $assert_session->linkExists('Basic');
    $assert_session->linkExists('Alternate');

    // Impose Inline Block type restrictions.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all"]');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-blacklisted');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-inline-blocks-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-inline-blocks-restriction-blacklisted');

    // Choose 'blacklist inline block types' without specifying individuals.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-inline-blocks-restriction-blacklisted"]');
    $element->click();
    $page->pressButton('Save');

    // Check independent restrctions on Custom block and Inline blocks.
    $this->navigateToNodeSettingsTray($node_id);
    $assert_session->linkExists('Body');
    $assert_session->linkExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkExists('Alternate Block 1');
    // Inline block types are still allowed.
    $assert_session->linkExists('Create custom block');

    // Blacklist some blocks / block types.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-blacklisted');
    // Blacklist the 'body' field as an option.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Content fields][available_blocks][field_block:node:bundle_with_section_field:body]');
    // Blacklist "basic" Custom block types.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Custom block types][available_blocks][basic]');
    // Blacklist the "alternate" Inline block type.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Inline blocks][available_blocks][inline_block:alternate]');
    $page->pressButton('Save');

    $this->navigateToNodeSettingsTray($node_id);
    // The "body" field is restricted.
    $assert_session->linkNotExists('Body');
    // ... but other 'content' fields aren't.
    $assert_session->linkExists('Promoted to front page');
    $assert_session->linkExists('Sticky at top of lists');
    // "Basic" Custom blocks are restricted.
    $assert_session->linkNotExists('Basic Block 1');
    $assert_session->linkNotExists('Basic Block 2');
    // ... but "alternate" Custom blocks are allowed.
    $assert_session->linkExists('Alternate Block 1');
    // Only the basic inline block type is allowed.
    $this->clickLink('Create custom block');
    $this->assertNotEmpty($assert_session->waitForText('Add a new custom block'));
    $assert_session->linkExists('Basic');
    $assert_session->linkNotExists('Alternate');

    // Custom block instances take precedence over custom block type setting.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-blacklisted"]');
    $element->click();
    // Blacklist Basic Block 1.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Custom blocks][available_blocks][block_content:' . $blocks['Basic Block 1'] . ']');
    // Blacklist Alternate Block 1.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Custom blocks][available_blocks][block_content:' . $blocks['Alternate Block 1'] . ']');
    $page->pressButton('Save');

    $this->navigateToNodeSettingsTray($node_id);
    $assert_session->linkNotExists('Basic Block 1');
    $assert_session->linkExists('Basic Block 2');
    $assert_session->linkNotExists('Alternate Block 1');
  }

}
