<?php

namespace Drupal\Tests\bigmenu\Functional;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Big Menu interface.
 *
 * @group bigmenu
 */
class BigMenuUiTest extends BrowserTestBase {

  /**
   * A user with administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bigmenu',
    'menu_link_content',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer menu']);
    $this->menu = Menu::load('main');
  }

  /**
   * Tests the Big Menu interface.
   */
  public function testBigMenuUi() {
    $assert = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Add new menu items in a hierarchy.
    $item1 = $this->createMenuLink('Item 1');
    $item1_1 = $this->createMenuLink('Item 1 - 1', 'internal:/', $item1->uuid());
    $this->createMenuLink('Item 1 - 1 - 1', 'internal:/', $item1_1->uuid());
    $item2 = $this->createMenuLink('Item 2 (with disabled children)');
    $this->createMenuLink('Item 2 - 1 (disabled)', 'internal:/', $item2->uuid(), FALSE);

    // Test visiting a menu with an invalid menu link query param, should show
    // empty table and no error.
    $this->drupalGet('admin/structure/menu/manage/main', [
      'query' => [
        'menu_link' => $this->randomMachineName(),
      ],
    ]);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('There are no menu links yet');

    $this->drupalGet('admin/structure/menu/manage/main');
    $this->assertLinkExists('#menu-overview', 'Item 1');
    $this->assertLinkNotExists('#menu-overview', 'Item 1 - 1');
    $this->assertLinkNotExists('#menu-overview', 'Item 1 - 1 - 1');
    $assert->elementNotExists('css', '.breadcrumb');

    // Check 'Edit child items' is available for 'Item 1'.
    $href = $this->menu->toUrl('edit-form', [
      'query' => ['menu_link' => 'menu_link_content:' . $item1->uuid()],
    ])->toString();
    $assert->linkByHrefExists($href);

    // Check 'Edit child items' is available when all children are not enabled.
    $href = $this->menu->toUrl('edit-form', [
      'query' => ['menu_link' => 'menu_link_content:' . $item2->uuid()],
    ])->toString();
    $assert->linkByHrefExists($href);

    $this->clickLink('Edit child items');
    $this->assertLinkExists('#menu-overview', 'Item 1');
    $this->assertLinkExists('#menu-overview', 'Item 1 - 1');
    $this->assertLinkNotExists('#menu-overview', 'Item 1 - 1 - 1');
    $this->assertLinkExists('.breadcrumb', 'Back to Main navigation top level');

    $this->clickLink('Edit child items');
    $this->assertLinkNotExists('#menu-overview', 'Item 1');
    $this->assertLinkExists('#menu-overview', 'Item 1 - 1');
    $this->assertLinkExists('#menu-overview', 'Item 1 - 1 - 1');
    $this->assertLinkExists('.breadcrumb', 'Back to Main navigation top level');
    $this->assertLinkExists('.breadcrumb', 'Item 1');

    // Test allowing more than one level of depth to appear.
    $this->config('bigmenu.settings')->set('max_depth', 2)->save();
    $this->drupalGet('admin/structure/menu/manage/main');
    $this->assertLinkExists('#menu-overview', 'Item 1');
    $this->assertLinkExists('#menu-overview', 'Item 1 - 1');
    $this->assertLinkNotExists('#menu-overview', 'Item 1 - 1 - 1');
  }

  /**
   * Tests for deleting a menu link when viewing children.
   */
  public function testBigMenuUiDeleteMenuLink() {
    $assert = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Add new menu items in a hierarchy.
    $item1 = $this->createMenuLink('Item 1');
    $this->createMenuLink('Item 1 - 1', 'internal:/', $item1->uuid());
    $this->drupalGet('admin/structure/menu/manage/main');
    $this->clickLink('Edit child items');
    $this->clickLinkInRow('Item 1 - 1', 'Delete');
    $this->submitForm([], 'Delete');
    $assert->pageTextContains('The menu link Item 1 - 1 has been deleted.');
    $this->clickLinkInRow('Item 1', 'Delete');
    $this->submitForm([], 'Delete');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('The menu link Item 1 has been deleted.');
    $assert->pageTextContains('There are no menu links yet');
  }

  /**
   * Create a menu link.
   *
   * @param string $title
   *   The title.
   * @param string $uri
   *   The uri.
   * @param string|null $parent
   *   The parent.
   * @param bool $enabled
   *   If the link is enabled.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The menu link.
   */
  protected function createMenuLink($title, $uri = 'internal:/', $parent = NULL, $enabled = TRUE): MenuLinkContentInterface {
    $values = [
      'title' => $title,
      'link' => [['uri' => $uri]],
      'menu_name' => $this->menu->id(),
      'enabled' => $enabled,
    ];
    if ($parent) {
      $values['parent'] = 'menu_link_content:' . $parent;
    }
    $link = MenuLinkContent::create($values);
    $link->save();
    return $link;
  }

  /**
   * Assert a link doesn't exist, scoped to a container.
   *
   * @param string $container
   *   The container selector.
   * @param string $label
   *   The exact label of the link.
   */
  protected function assertLinkNotExists($container, $label) {
    $links = $this->getSession()->getPage()
      ->find('css', $container)
      ->findAll('named_exact', ['link', $label]);
    $this->assertEmpty($links);
  }

  /**
   * Assert a link exist, scoped to a container.
   *
   * @param string $container
   *   The container selector.
   * @param string $label
   *   The exact label of the link.
   */
  protected function assertLinkExists($container, $label) {
    $links = $this->getSession()->getPage()
      ->find('css', $container)
      ->findAll('named_exact', ['link', $label]);
    $this->assertNotEmpty($links);
  }

  /**
   * Clicks a link in a row.
   *
   * @param string $row_text
   *   Text to find a row.
   * @param string $link
   *   The link to click.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the row can't be found.
   */
  protected function clickLinkInRow($row_text, $link) {
    /** @var \Behat\Mink\Element\NodeElement $row */
    $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $row_text));
    if (!$row) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'table row', 'value', $row_text);
    }

    $row->clickLink($link);
  }

}
