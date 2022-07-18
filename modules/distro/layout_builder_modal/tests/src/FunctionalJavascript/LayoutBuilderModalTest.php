<?php

namespace Drupal\Tests\layout_builder_modal\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests Layout Builder Modal.
 *
 * @group layout_builder_modal
 */
class LayoutBuilderModalTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'field_ui',
    'layout_builder',
    'layout_builder_modal',
    'node',
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

    $this->drupalPlaceBlock('local_tasks_block');

    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());

    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'create and edit custom blocks',
    ], 'foobar'));
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');

    // Enable layout builder.
    $this->submitForm(['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE], 'Save');
  }

  /**
   * Tests the Layout Builder Modal.
   */
  public function testLayoutBuilderModal() {
    $layout_url = 'node/1/layout';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($layout_url);
    $this->click('.layout-builder__add-block .layout-builder__link');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify that the add block has been opened in the modal.
    $assert_session->elementExists('css', '#layout-builder-modal .layout-builder-add-block');

    $textarea = $assert_session->waitForElement('css', '[name="settings[block_form][body][0][value]"]');
    $this->assertNotEmpty($textarea);
    $assert_session->fieldValueEquals('Title', '');
    $page->findField('Title')->setValue('Hello World');
    $textarea->setValue('Body says hello');
    $button = $assert_session->elementExists('css', '#layout-builder-add-block .button--primary');
    $button->press();

    $assert_session->assertWaitOnAjaxRequest();

    // Verify that both the modal and off canvas has been closed.
    $assert_session->elementNotExists('css', '#layout-builder-modal .layout-builder-add-block');
    $assert_session->elementNotExists('css', '#drupal-off-canvas');

    $assert_session->pageTextContains('Body says hello');

    $this->clickContextualLink('.block-inline-blockbasic', 'Configure');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify that the update block has been opened in the modal.
    $assert_session->elementExists('css', '#layout-builder-modal .layout-builder-update-block');
  }

  /**
   * Tests interface with multiple custom block types.
   */
  public function testWithMultipleCustomBlockTypes() {
    $layout_url = 'node/1/layout';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add a second block type.
    $block_type = BlockContentType::create([
      'id' => 'foo_block',
      'label' => 'Foo block',
    ]);
    $block_type->save();
    block_content_add_body_field($block_type->id());

    $this->drupalGet($layout_url);
    $this->click('.layout-builder__add-block .layout-builder__link');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify that back button works.
    $this->clickLink('Back');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#layout-builder-modal');

    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();

    $this->clickLink('Foo block');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify that the add block has been opened in the modal.
    $assert_session->elementExists('css', '#layout-builder-modal .layout-builder-add-block');

    $assert_session->fieldValueEquals('Title', '');
    $page->findField('Title')->setValue('Second block');
    $button = $assert_session->elementExists('css', '#layout-builder-add-block .button--primary');
    $button->press();

    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextContains('Second block');
  }

  /**
   * Tests contextual links.
   */
  public function testContextualLinks() {
    $layout_url = 'node/1/layout';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($layout_url);

    $assert_session->assertWaitOnAjaxRequest();

    $this->click('.layout-builder__add-section .layout-builder__link--add');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Two column');
    $assert_session->assertWaitOnAjaxRequest();
    $button = $assert_session->elementExists('css', '.layout-builder-configure-section .button--primary');
    $button->press();
    $assert_session->assertWaitOnAjaxRequest();
    $this->click('.layout-builder__add-block .layout-builder__link--add');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Authored by');
    $assert_session->assertWaitOnAjaxRequest();
    $button = $assert_session->elementExists('css', '#layout-builder-add-block .button--primary');
    $button->press();
    $assert_session->assertWaitOnAjaxRequest();

    $contextual_links = $page->findAll('css', '.layout-builder-block-update a');
    $dialog_options = [];
    foreach ($contextual_links as $key => $contextual_link) {
      $dialog_options[$key] = $contextual_link->getAttribute('data-dialog-options');
    }

    \Drupal::configFactory()->getEditable('layout_builder_modal.settings')
      ->set('modal_width', 600)
      ->save();

    $this->drupalGet($layout_url);

    $assert_session->assertWaitOnAjaxRequest();

    $contextual_links = $page->findAll('css', '.layout-builder-block-update a');
    $updated_dialog_options = [];
    foreach ($contextual_links as $key => $contextual_link) {
      $updated_dialog_options[$key] = $contextual_link->getAttribute('data-dialog-options');
    }

    foreach ($dialog_options as $key => $dialog_option) {
      $this->assertNotSame($dialog_option, $updated_dialog_options[$key]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this in https://www.drupal.org/project/drupal/issues/2918718.
   */
  protected function clickContextualLink($selector, $link_locator, $force_visible = TRUE) {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $page->waitFor(10, function () use ($page, $selector) {
      return $page->find('css', "$selector .contextual-links");
    });
    if (count($page->findAll('css', "$selector .contextual-links")) > 1) {
      throw new \Exception('More than one contextual links found by selector');
    }

    if ($force_visible && $page->find('css', "$selector .contextual .trigger.visually-hidden")) {
      $this->toggleContextualTriggerVisibility($selector);
    }

    $link = $assert_session->elementExists('css', $selector)->findLink($link_locator);
    $this->assertNotEmpty($link);

    if (!$link->isVisible()) {
      $button = $assert_session->waitForElementVisible('css', "$selector .contextual button");
      $this->assertNotEmpty($button);
      $button->press();
      $link = $page->waitFor(10, function () use ($link) {
        return $link->isVisible() ? $link : FALSE;
      });
    }

    $link->click();

    if ($force_visible) {
      $this->toggleContextualTriggerVisibility($selector);
    }
  }

  /**
   * Tests minimal presence/absence of admin CSS.
   */
  public function testAdminCss() {
    $layout_url = 'node/1/layout';

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($layout_url);
    $this->click('.layout-builder__add-block .layout-builder__link');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify there is a 'primary' class.
    $assert_session->elementExists('css', '#layout-builder-modal .button--primary');
    $button_background = $this->getSession()->evaluateScript('jQuery("#layout-builder-modal .button--primary").css("background-color")');
    $default_background = "rgb(221, 221, 221)";
    $this->assertSame($button_background, $default_background);

    \Drupal::configFactory()->getEditable('layout_builder_modal.settings')
      ->set('theme_display', 'seven')
      ->save();

    $this->drupalGet($layout_url);
    $this->click('.layout-builder__add-block .layout-builder__link');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify there seven background attribute is present.
    $button_background = $this->getSession()->evaluateScript('jQuery("#layout-builder-modal .button--primary").css("background-color")');
    $seven_background = "rgb(0, 113, 184)";
    $this->assertSame($seven_background, $button_background);
  }

}
