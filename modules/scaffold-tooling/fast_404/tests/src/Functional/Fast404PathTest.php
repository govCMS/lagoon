<?php

namespace Drupal\Tests\fast404\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the path checking functionality.
 *
 * @group fast404
 */
class Fast404PathTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['fast404', 'node', 'path', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'administer url aliases',
      'create url aliases',
      'access content overview',
      'administer taxonomy',
      'access administration pages',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the path checking functionality.
   */
  public function testPathCheck() {
    // Ensure path check isn't activated by default.
    $this->drupalGet('/does_not_exist');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('The requested page could not be found.');

    \Drupal::service('cache.page')->deleteAll();

    $settings['settings']['fast404_path_check'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('/does_not_exist');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('Not Found');
    $this->assertSession()->responseContains('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "/does_not_exist" was not found on this server.</p></body></html>');

    // Ensure requests to the front page are not blocked.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure items in the router are not blocked.
    $this->drupalGet('/user');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('user/1');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure nodes with URL aliases are not blocked.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = [];
    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, $this->t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertText($node1->label(), 'Alias works.');
    $this->assertResponse(200);

    // Confirm that the alias with a trailing slash works.
    $this->drupalGet($edit['path[0][alias]'] . '/');
    $this->assertText($node1->label(), 'Alias works.');
    $this->assertResponse(200);

    // Ensure terms with URL aliases are not blocked.
    $vocabulary = Vocabulary::create([
      'name' => $this->t('Tags'),
      'vid' => 'tags',
    ]);
    $vocabulary->save();

    // Create a term in the default 'Tags' vocabulary with URL alias.
    $vocabulary = Vocabulary::load('tags');
    $description = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'description[0][value]' => $description,
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add', $edit, $this->t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertText($description, 'Term can be accessed on URL alias.');

  }

}
