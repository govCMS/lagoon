<?php

namespace Drupal\Tests\robotstxt\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests basic functionality of configured robots.txt files.
 *
 * @group Robots.txt
 */
class RobotsTxtBasicTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Provides the default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['robotstxt', 'node', 'robotstxt_test'];

  /**
   * User with proper permissions for module configuration.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * User with content access.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $normalUser;

  /**
   * Checks that an administrator can view the configuration page.
   */
  public function testRobotsTxtAdminAccess() {
    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer robots.txt']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/search/robotstxt');

    // The textarea for configuring robots.txt is shown.
    $this->assertSession()->fieldExists('robotstxt_content');
  }

  /**
   * Checks that a non-administrative user cannot use the configuration page.
   */
  public function testRobotsTxtUserNoAccess() {
    // Create user.
    $this->normalUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('admin/config/search/robotstxt');

    $this->assertSession()->statusCodeEquals(403);

    // The textarea for configuring robots.txt is not shown for users without
    // appropriate permissions.
    $this->assertSession()->fieldNotExists('edit-robotstxt-content');
  }

  /**
   * Test that the robots.txt path delivers content with an appropriate header.
   */
  public function testRobotsTxtPath() {
    $this->drupalGet('robots-test.txt');

    // No local robots.txt file was detected, and an anonymous user is delivered
    // content at the /robots.txt path.
    $this->assertSession()->statusCodeEquals(200);

    // The robots.txt file was served with header
    // Content-Type: "text/plain; charset=UTF-8".
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/plain; charset=UTF-8');
  }

  /**
   * Test that the robots.txt path delivers content the appropriate cache tags.
   */
  public function testRobotsTxtCacheTags() {
    $this->drupalGet('robots-test.txt');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'robotstxt');
  }

  /**
   * Checks that a configured robots.txt file is delivered as configured.
   */
  public function testRobotsTxtConfigureRobotsTxt() {
    // Create an admin user, log in and access settings form.
    $this->adminUser = $this->drupalCreateUser(['administer robots.txt']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/search/robotstxt');

    $test_string = "# SimpleTest {$this->randomMachineName()}";
    $this->submitForm(['robotstxt_content' => $test_string], $this->t('Save configuration'));

    $this->drupalLogout();
    $this->drupalGet('robots-test.txt');

    // No local robots.txt file was detected, and an anonymous user is delivered
    // content at the /robots.txt path.
    $this->assertSession()->statusCodeEquals(200);

    // The robots.txt file was served with header
    // Content-Type: "text/plain; charset=UTF-8".
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/plain; charset=UTF-8');
    $content = $this->getSession()->getPage()->getContent();
    $this->assertTrue($content == $test_string, sprintf('Test string [%s] is displayed in the configured robots.txt file [%s].', $test_string, $content));
  }

}
