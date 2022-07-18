<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search\SearchIndexInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test search functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsSearchTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'search', 'node'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'search content',
      'create page content',
      'edit own page content',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if search tracking is properly added to the page.
   */
  public function testGoogleAnalyticsSearchTracking() {
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')
      ->set('account', $ua_code)
      ->set('privacy.anonymizeip', 0)
      ->set('track.displayfeatures', 1)
      ->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains($ua_code);

    $this->drupalGet('search/node');
    $this->assertSession()->responseNotContains('"page_path":(window.google_analytics_search_results) ?');

    // Enable site search support.
    $this->config('google_analytics.settings')->set('track.site_search', 1)->save();

    // Search for random string.
    $search = ['keys' => $this->randomMachineName(8)];

    // Fire a search, it's expected to get 0 results.
    $this->drupalGet('search/node');
    $this->submitForm($search, $this->t('Search'));
    $this->assertSession()->responseContains('"page_path":(window.google_analytics_search_results) ?');
    // Check GA Site Search query param is 'search' when there are no results.
    $this->assertSession()->responseMatches('/(.+search=' . urlencode("no-results:{$search['keys']}") . ')/');
    $this->assertSession()->responseContains('window.google_analytics_search_results = 0;');

    // Create a node and reindex.
    $this->createNodeAndIndex($search['keys']);
    $this->drupalGet('search/node');
    $this->submitForm($search, $this->t('Search'));
    $this->assertSession()->responseContains('"page_path":(window.google_analytics_search_results) ?');
    // Check the GA Site Search query param is 'search'.
    $this->assertSession()->responseMatches('/(.+search=' . urlencode($search['keys']) . ')/');
    $this->assertSession()->responseContains('window.google_analytics_search_results = 1;');

    // Create a second node with same values and reindex.
    $this->createNodeAndIndex($search['keys']);
    $this->drupalGet('search/node');
    $this->submitForm($search, $this->t('Search'));
    $this->assertSession()->responseContains('"page_path":(window.google_analytics_search_results) ?');
    $this->assertSession()->responseContains('window.google_analytics_search_results = 2;');
  }

  /**
   * Helper function to create the node and reindex search.
   *
   * @param string $test_string
   *   Some unique identifying string to add to the text of the node.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   * @internal
   */
  protected function createNodeAndIndex($test_string) {
    // Create the node.
    $node = $this->drupalCreateNode([
      'title' => "Someone who says $test_string!",
      'body' => [['value' => "We are the knights who say $test_string!"]],
      'type' => 'page',
    ]);

    // Index the node or it cannot found.
    $node_search_plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    // Update the search index.
    $node_search_plugin->updateIndex();
    $search_index = \Drupal::service('search.index');
    assert($search_index instanceof SearchIndexInterface);

    return $node;
  }

}
