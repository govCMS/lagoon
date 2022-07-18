<?php

namespace Drupal\Tests\menu_trail_by_path\Functional;

use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_trail_by_path\MenuTrailByPathActiveTrail;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Menu\AssertMenuActiveTrailTrait;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests that the menu links have the correct css-classes.
 *
 * @group menu_trail_by_path
 */
class MenuTrailByPathActiveTrailHtmlClassTest extends BrowserTestBase {

  use AssertMenuActiveTrailTrait;
  use PathAliasTestTrait;

  /**
   * Modules to install.
   *
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'block',
    'menu_link_content',
    'menu_trail_by_path',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Array key's should be the menu title's, if multi-level than separated by ' » '.
   *
   * @var Url[]
   */
  protected $menuUrls = [];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user
    $this->authenticatedUser = $this->drupalCreateUser();

    // Create content type
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create nodes
    $node1 = $this->drupalCreateNode();
    $this->createPathAlias('/node/' . $node1->id(), '/news');

    $node2 = $this->drupalCreateNode();
    $this->createPathAlias('/node/' . $node2->id(), '/news/category-a');

    // Set menuUrls
    $this->menuUrls = [
      'Home'                 => Url::fromUri('route:<front>'),
      'User password'        => Url::fromUri('route:user.pass'),
      'User login'           => Url::fromUri('route:user.login'),
      'User'                 => Url::fromUri('route:user.page'),
      'News'                 => Url::fromUri('route:entity.node.canonical;node=' . $node1->id()),
      'News » News overview' => Url::fromUri('route:entity.node.canonical;node=' . $node1->id()),
      'News » Category A'    => Url::fromUri('route:entity.node.canonical;node=' . $node2->id()),
    ];

    // Build the main menu.
    $this->buildMenu();
    $this->drupalPlaceBlock(
      'system_menu_block:main', [
        'id'     => 'system-menu-block-main',
        'label'  => 'Main menu',
        'region' => 'header',
      ]
    );
  }

  /**
   * Test url: Home
   */
  public function testUrlHome() {
    $this->drupalGet(clone $this->menuUrls['Home']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('Home') => 'Home',
      ], TRUE
    );
  }

  /**
   * Test url: User password
   */
  public function testUrlUserPassword() {
    $this->drupalGet(clone $this->menuUrls['User password']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('User password') => 'User password',
      ], TRUE
    );

    // Change the global setting to use the core implementation.
    $this->config('menu_trail_by_path.settings')
      ->set('trail_source', MenuTrailByPathActiveTrail::MENU_TRAIL_CORE)
      ->save();
    $this->rebuildAll();

    $this->drupalGet(clone $this->menuUrls['User password']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('User password') => 'User password',
      ], TRUE
    );

    // Change the global setting to use the core implementation.
    $this->config('menu_trail_by_path.settings')
      ->set('trail_source', MenuTrailByPathActiveTrail::MENU_TRAIL_DISABLED)
      ->save();
    $this->rebuildAll();

    $this->drupalGet(clone $this->menuUrls['User password']);
    $this->assertSession()->responseNotContains('menu-item--active-trail');

    // Set a menu specific setting to override the default.
    $menu = Menu::load('main');
    $menu->setThirdPartySetting('menu_trail_by_path', 'trail_source', MenuTrailByPathActiveTrail::MENU_TRAIL_PATH);
    $menu->save();
    $this->drupalGet(clone $this->menuUrls['User password']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('User password') => 'User password',
      ], TRUE
    );
  }

  /**
   * Test url: User login
   */
  public function testUrlUserLogin() {
    $this->drupalGet(clone $this->menuUrls['User login']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('User login') => 'User login',
      ], TRUE
    );
  }

  /**
   * Test url: User
   */
  public function testUrlUser() {
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet(clone $this->menuUrls['User']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('User') => 'User',
      ], FALSE
    );
  }

  /**
   * Test url: News
   */
  public function testUrlNews() {
    $this->drupalGet(clone $this->menuUrls['News']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('News') => 'News',
      ], TRUE
    );
  }

  /**
   * Test url: News » News overview
   */
  public function testUrlNewsNewsOverview() {
    $this->drupalGet(clone $this->menuUrls['News » News overview']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('News » News overview') => 'News overview',
      ], TRUE
    );

    // Also test the parent item, due to the tree url key construction of assertMenuActiveTrail we need two separate calls
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('News') => 'News',
      ], TRUE
    );
  }

  /**
   * Test url: News » Category A
   */
  public function testUrlNewsCategorya() {
    $this->drupalGet(clone $this->menuUrls['News » Category A']);
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('News') => 'News',
        $this->menuUrlBasePath('News » Category A') => 'Category A',
      ], TRUE
    );
  }

  /**
   * Test url: News » Category A » Item A
   */
  public function testUrlNewsCategoryaItema() {
    $node3 = $this->drupalCreateNode();
    \Drupal::service('path.alias_storage')->save('/node/' . $node3->id(), '/news/category-a/item-a');

    $this->drupalGet($node3->toUrl());
    $this->assertMenuActiveTrail(
      [
        $this->menuUrlBasePath('News') => 'News',
        $this->menuUrlBasePath('News » Category A') => 'Category A',
      ], FALSE
    );

    // Change the global setting to use the core implementation.
    $this->config('menu_trail_by_path.settings')
      ->set('trail_source', MenuTrailByPathActiveTrail::MENU_TRAIL_CORE)
      ->save();
    $this->rebuildAll();

    $this->drupalGet($node3->toUrl());
    $this->assertSession()->responseNotContains('menu-item--active-trail');
  }

  /**
   * Build a menu with the data of $this->menuUrls
   *
   * @param string $menu_name
   */
  protected function buildMenu($menu_name = 'main') {
    $menu_handler = \Drupal::service('plugin.manager.menu.link');
    $menu_handler->deleteLinksInMenu($menu_name);

    $menu_links       = [];
    $menu_link_weight = -30;
    foreach ($this->menuUrls as $title => $url) {
      $titles      = explode(' » ', $title);
      $title_short = array_pop($titles);
      $parent      = ($titles) ? $menu_links[implode(' » ', $titles)]->getPluginId() : NULL;

      $menu_links[$title] = MenuLinkContent::create(
        [
          'menu_name' => $menu_name,
          'title'     => $title_short,
          'link'      => ['uri' => $url->toUriString()],
          'parent'    => $parent,
          'expanded'  => TRUE,
          'weight'    => $menu_link_weight,
        ]
      );
      $menu_links[$title]->save();
      $menu_link_weight++;
    }
  }

  /**
   * Helper for getting the base: "link_path" that assertMenuActiveTrail expects.
   *
   * @param $name
   * @return string
   */
  protected function menuUrlBasePath($name) {
    $url = $this->menuUrls[$name];
    return '/' . preg_replace('/^' . preg_quote(base_path(), '/') . '/', '', $url->toString());
  }

}
