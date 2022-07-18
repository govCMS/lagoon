<?php

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\layout_library\Entity\Layout;
use Drupal\Tests\layout_builder_restrictions\Traits\MoveBlockHelperTrait;

/**
 * Tests moving blocks via the form.
 *
 * @group layout_builder_restrictions
 */
class MoveBlockRestrictionTest extends LayoutBuilderRestrictionsTestBase {

  use ContentTypeCreationTrait;
  use MoveBlockHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'contextual',
    'node',
    'layout_builder',
    'layout_library',
    'layout_builder_restrictions',
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

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'configure any layout',
      'administer blocks',
      'administer node display',
      'administer node fields',
      'access contextual links',
      'create and edit custom blocks',
    ]));

    $layout = Layout::create([
      'id' => 'alpha',
      'label' => 'Alpha',
      'targetEntityType' => 'node',
      'targetBundle' => 'bundle_with_section_field',
    ]);
    $layout->save();

  }

  /**
   * Move a plugin block in the Layout Library.
   */
  public function testLayoutLibraryMovePluginBlock() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Add a layout to the library.
    $this->drupalGet('admin/structure/layouts');
    $page->clickLink('Edit layout');
    $page->clickLink('Add section');
    $this->assertNotEmpty($assert_session->waitForText('Choose a layout for this section'));
    $page->clickLink('One column');
    $this->assertNotEmpty($assert_session->waitForText('Configure section'));
    $page->pressButton('Add section');
    $this->assertNotEmpty($assert_session->waitForText('You have unsaved changes'));
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForText('Choose a block'));
    $page->clickLink('Powered by Drupal');
    $this->assertNotEmpty($assert_session->waitForText('Configure block'));
    $page->fillField('settings[label]', 'Powered by Drupal');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-system-powered-by-block'));
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForText('Choose a block'));
    $page->clickLink('Site branding');
    $this->assertNotEmpty($assert_session->waitForText('Configure block'));
    $page->fillField('settings[label]', 'Site branding');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-system-branding-block'));

    $page->pressButton('Save layout');

    // Two blocks have been saved to the Layout, in the order
    // Powered by Drupal, Site branding.
    // Change the order & save.
    $this->drupalGet('admin/structure/layouts');
    $page->clickLink('Edit layout');
    // Move the body block into the first region above existing block.
    $this->openMoveForm(0, 'content', 'block-system-powered-by-block', ['Powered by Drupal (current)', 'Site branding']);
    $page->selectFieldOption('Region', '0:content');
    $this->assertBlockTable(['Powered by Drupal (current)', 'Site branding']);
    $this->moveBlockWithKeyboard('up', 'Site branding', ['Site branding*', 'Powered by Drupal (current)']);
    $page->pressButton('Move');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');

    // Confirm the order has successfully changed.
    $this->drupalGet('admin/structure/layouts');
    $page->clickLink('Edit layout');
    $expected_block_order = [
      '.block-system-branding-block',
      '.block-system-powered-by-block',
    ];
    $this->assertRegionBlocksOrder(0, 'content', $expected_block_order);
  }

  /**
   * Tests moving a plugin block.
   */
  public function testMovePluginBlock() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->navigateToManageDisplay();
    $page->clickLink('Manage layout');
    $expected_block_order = [
      '.block-extra-field-blocknodebundle-with-section-fieldlinks',
      '.block-field-blocknodebundle-with-section-fieldbody',
    ];
    $this->assertRegionBlocksOrder(0, 'content', $expected_block_order);

    // Add a top section using the Two column layout.
    $page->clickLink('Add section');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas');
    $page->clickLink('Two column');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add section"]'));
    $page->pressButton('Add section');
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order);
    // Add a 'Powered by Drupal' block in the 'first' region of the new section.
    $first_region_block_locator = '[data-layout-delta="0"].layout--twocol-section [data-region="first"] [data-layout-block-uuid]';
    $assert_session->elementNotExists('css', $first_region_block_locator);
    $assert_session->elementExists('css', '[data-layout-delta="0"].layout--twocol-section [data-region="first"] .layout-builder__add-block')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas a:contains("Powered by Drupal")'));
    $page->clickLink('Powered by Drupal');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add block"]'));
    $page->pressButton('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $first_region_block_locator));

    // Ensure the request has completed before the test starts.
    $this->waitForNoElement('#drupal-off-canvas');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a block restriction after the fact to test basic restriction.
    // Restrict all 'Content' fields from options.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all"]');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-whitelisted');
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-whitelisted"]');
    $element->click();
    $page->pressButton('Save');

    $page->clickLink('Manage layout');
    $expected_block_order_1 = [
      '.block-extra-field-blocknodebundle-with-section-fieldlinks',
      '.block-field-blocknodebundle-with-section-fieldbody',
    ];
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order_1);

    // Attempt to reorder body field in current region.
    $this->openMoveForm(1, 'content', 'block-field-blocknodebundle-with-section-fieldbody', ['Links', 'Body (current)']);
    $this->moveBlockWithKeyboard('up', 'Body (current)', ['Body (current)*', 'Links']);
    $page->pressButton('Move');
    $this->assertNotEmpty($assert_session->waitForText('Content cannot be placed'));
    // Verify that a validation error is provided.
    $modal = $page->find('css', '#drupal-off-canvas p');
    $this->assertSame("There is a restriction on Body placement in the layout_onecol content region for bundle_with_section_field content.", trim($modal->getText()));

    $dialog_div = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $close_button = $dialog_div->findButton('Close');
    $this->assertNotNull($close_button);
    $close_button->press();

    $page->pressButton('Save layout');
    $page->clickLink('Manage layout');
    // The order should not have changed after save.
    $this->assertRegionBlocksOrder(1, 'content', $expected_block_order_1);

    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all"]');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-whitelisted');
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-content-fields-restriction-whitelisted"]');
    $element->click();
    $page->pressButton('Save');

    $this->navigateToManageDisplay();
    $page->clickLink('Manage layout');
    // Move the body block into the first region above existing block.
    $this->openMoveForm(1, 'content', 'block-field-blocknodebundle-with-section-fieldbody', ['Links', 'Body (current)']);
    $page->selectFieldOption('Region', '0:first');
    $this->assertBlockTable(['Powered by Drupal', 'Body (current)']);
    $this->moveBlockWithKeyboard('up', 'Body', ['Body (current)*', 'Powered by Drupal']);
    $page->pressButton('Move');
    $this->assertNotEmpty($assert_session->waitForText('Content cannot be placed'));
    $modal = $page->find('css', '#drupal-off-canvas p');
    // Content cannot be moved between sections if a restriction exists.
    $this->assertSame("There is a restriction on Body placement in the layout_twocol_section first region for bundle_with_section_field content.", trim($modal->getText()));
  }

  /**
   * Tests moving a content block.
   */
  public function testMoveContentBlock() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $blocks = $this->generateTestBlocks();
    $node_id = $this->generateTestNode();

    $this->navigateToManageDisplay();
    $page->clickLink('Manage layout');
    // Add a top section using the Two column layout.
    $page->clickLink('Add section');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas');
    $page->clickLink('Two column');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'input[value="Add section"]'));
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    // Add Basic Block 1 to the 'first' region.
    $assert_session->elementExists('css', '[data-layout-delta="0"].layout--twocol-section [data-region="first"] .layout-builder__add-block')->click();
    $this->assertNotEmpty($assert_session->waitForText('Basic Block 1'));
    $page->clickLink('Basic Block 1');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');
    $this->waitForNoElement('#drupal-off-canvas');

    // Add Alternate Block 1 to the 'first' region.
    $assert_session->elementExists('css', '[data-layout-delta="0"].layout--twocol-section [data-region="first"] .layout-builder__add-block')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas a:contains("Alternate Block 1")'));
    $page->clickLink('Alternate Block 1');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');
    $this->waitForNoElement('#drupal-off-canvas');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Restrict all Custom blocks.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-all"]');
    $assert_session->checkboxChecked('edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-all');
    $assert_session->checkboxNotChecked('edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-whitelisted');
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-whitelisted"]');
    $element->click();
    $page->pressButton('Save');

    $page->clickLink('Manage layout');
    $expected_block_order = [
      '.block-block-content' . $blocks['Basic Block 1'],
      '.block-block-content' . $blocks['Alternate Block 1'],
    ];
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);
    $this->navigateToManageDisplay();
    $page->clickLink('Manage layout');

    // Attempt to reorder Alternate Block 1.
    $this->openMoveForm(0, 'first', 'block-block-content' . $blocks['Alternate Block 1'], ['Basic Block 1', 'Alternate Block 1 (current)']);
    $this->moveBlockWithKeyboard('up', 'Alternate Block 1', ['Alternate Block 1 (current)*', 'Basic Block 1']);
    $page->pressButton('Move');
    $this->assertNotEmpty($assert_session->waitForText('Content cannot be placed'));
    // Verify that a validation error is provided.
    $modal = $page->find('css', '#drupal-off-canvas p');
    $this->assertSame("There is a restriction on Alternate Block 1 placement in the layout_twocol_section first region for bundle_with_section_field content.", trim($modal->getText()));

    $dialog_div = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $close_button = $dialog_div->findButton('Close');
    $this->assertNotNull($close_button);
    $close_button->press();

    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $page->clickLink('Manage layout');
    // The order should not have changed after save.
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);

    // Allow Alternate Block, but not Basic block.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    // Do not apply individual block level restrictions.
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-all"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-block-types-restriction-whitelisted"]');
    $element->click();
    // Whitelist all "Alternate" block types.
    $page->checkField('layout_builder_restrictions[allowed_blocks][Custom block types][available_blocks][alternate]');
    $page->pressButton('Save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Reorder Alternate block.
    $page->clickLink('Manage layout');
    $expected_block_order_moved = [
      '.block-block-content' . $blocks['Alternate Block 1'],
      '.block-block-content' . $blocks['Basic Block 1'],
    ];
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);
    $this->openMoveForm(0, 'first', 'block-block-content' . $blocks['Alternate Block 1'], ['Basic Block 1', 'Alternate Block 1 (current)']);
    $this->moveBlockWithKeyboard('up', 'Alternate Block 1', ['Alternate Block 1 (current)*', 'Basic Block 1']);
    $page->pressButton('Move');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order_moved);

    // Demonstrate that Basic block types are still restricted.
    $this->openMoveForm(0, 'first', 'block-block-content' . $blocks['Basic Block 1'], ['Alternate Block 1', 'Basic Block 1 (current)']);
    $this->moveBlockWithKeyboard('up', 'Basic Block 1', ['Basic Block 1 (current)*', 'Alternate Block 1']);
    $page->pressButton('Move');
    $this->assertNotEmpty($assert_session->waitForText('Content cannot be placed'));
    // Verify that a validation error is provided.
    $modal = $page->find('css', '#drupal-off-canvas p');
    $this->assertSame("There is a restriction on Basic Block 1 placement in the layout_twocol_section first region for bundle_with_section_field content.", trim($modal->getText()));
    $dialog_div = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $close_button = $dialog_div->findButton('Close');
    $this->assertNotNull($close_button);
    $close_button->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $page->clickLink('Manage layout');

    // Allow all Custom block types.
    $this->navigateToManageDisplay();
    $element = $page->find('xpath', '//*[@id="edit-layout-layout-builder-restrictions-allowed-blocks"]/summary');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-block-types-restriction-all"]');
    $element->click();
    $element = $page->find('xpath', '//*[@id="edit-layout-builder-restrictions-allowed-blocks-custom-blocks-restriction-all"]');
    $element->click();
    $page->pressButton('Save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Reorder both Alternate & Basic block block.
    $page->clickLink('Manage layout');
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order_moved);
    $this->openMoveForm(0, 'first', 'block-block-content' . $blocks['Basic Block 1'], ['Alternate Block 1', 'Basic Block 1 (current)']);
    $this->moveBlockWithKeyboard('up', 'Basic Block 1', ['Basic Block 1 (current)*', 'Alternate Block 1']);
    $page->pressButton('Move');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $modal = $page->find('css', '#drupal-off-canvas p');
    $this->assertNull($modal);
    $page->pressButton('Save layout');
    // Reorder Alternate block.
    $page->clickLink('Manage layout');
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order);
    $this->openMoveForm(0, 'first', 'block-block-content' . $blocks['Alternate Block 1'], ['Basic Block 1', 'Alternate Block 1 (current)']);
    $this->moveBlockWithKeyboard('up', 'Alternate Block 1', ['Alternate Block 1 (current)*', 'Basic Block 1']);
    $page->pressButton('Move');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $page->clickLink('Manage layout');
    $this->assertRegionBlocksOrder(0, 'first', $expected_block_order_moved);
  }

}
