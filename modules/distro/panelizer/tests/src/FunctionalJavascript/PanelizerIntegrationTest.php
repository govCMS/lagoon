<?php

namespace Drupal\Tests\panelizer\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\panels_ipe\FunctionalJavascript\PanelsIPETestTrait;

/**
 * Tests the JavaScript functionality of Panels IPE with Panelizer.
 *
 * @group panelizer
 */
class PanelizerIntegrationTest extends WebDriverTestBase {

  use PanelsIPETestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The route that IPE tests should be ran on.
   */
  protected $test_route;

  /**
   * The window size set when calling $this->visitIPERoute().
   */
  protected $window_size = [1024, 768];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'node',
    'panels',
    'panels_ipe',
    'panelizer',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with appropriate permissions to use Panels IPE.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'access panels in-place editing',
      'administer blocks',
      'administer node display',
      'administer panelizer',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create the "Basic Page" content type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic Page',
    ]);

    // Enable Panelizer for the "Basic Page" content type.
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->submitForm(['panelizer[enable]' => 1], t('Save'));

    // Create a new Basic Page.
    $this->drupalGet('node/add/page');
    $this->submitForm(['title[0][value]' => 'Test Node'], t('Save'));

    $this->test_route = 'node/1';
  }

  /**
   * Tests that the IPE editing session is specific to a user.
   */
  public function testUserEditSession() {
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');

    // Change the layout to lock the IPE.
    $this->changeLayout('Columns: 2', 'layout_twocol');
    $this->assertSession()->elementExists('css', '.layout--twocol');
    $this->assertSession()->elementNotExists('css', '.layout--onecol');

    // Create a second node.
    $this->drupalGet('node/add/page');
    $this->submitForm(['title[0][value]' => 'Test Node 2'], t('Save'));
    $this->test_route = 'node/2';

    // Ensure the second node does not use the session of the other node.
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');
    $this->assertSession()->elementNotExists('css', '.layout--twocol');
  }

  /**
   * Tests that the IPE is loaded on the current test route.
   */
  public function testIPEIsLoaded() {
    $this->visitIPERoute();

    $this->assertIPELoaded();
  }

  /**
   * Tests that adding a block with default configuration works.
   */
  public function testIPEAddBlock() {
    $this->visitIPERoute();

    $this->addBlock('System', 'system_breadcrumb_block');
  }

  /**
   * Tests that changing layout from one (default) to two columns works.
   */
  public function testIPEChangeLayout() {
    $this->visitIPERoute();

    // Change the layout to two columns.
    $this->changeLayout('Columns: 2', 'layout_twocol');
    $this->waitUntilVisible('.layout--twocol', 10000, 'Layout changed to two column.');
  }

  /**
   * Visits the test route and sets an appropriate window size for IPE.
   */
  protected function visitIPERoute() {
    $this->drupalGet($this->test_route);

    // Set the window size to ensure that IPE elements are visible.
    call_user_func_array([$this->getSession(), 'resizeWindow'], $this->window_size);
  }

  /**
   * {@inheritdoc}
   */
  protected function changeLayout($category, $layout_id) {
    // Open the "Change Layout" tab.
    $this->clickAndWait('[data-tab-id="change_layout"]');

    // Wait for layouts to be pulled into our collection.
    $this->waitUntilNotPresent('.ipe-icon-loading');

    // Select the target category.
    $this->clickAndWait('[data-category="' . $category . '"]');

    // Select the target layout.
    $this->clickAndWait('[data-layout-id="' . $layout_id . '"]');

    // Wait for the form to load/submit.
    $this->waitUntilNotPresent('.ipe-icon-loading');

    // See if the layout has a settings form (new in Drupal 8.8), and if so,
    // submit it without making any changes. This is the only difference
    // between this method and the one inherited from PanelsIPETestTrait.
    $layout_form = $this->getSession()
      ->getPage()
      ->find('css', '.panels-ipe-layout-form');
    if ($layout_form) {
      $layout_form->pressButton('Change Layout');
    }

    // Wait for the edit tab to become active (happens automatically after
    // form submit).
    $this->waitUntilVisible('[data-tab-id="edit"].active');
  }

}
