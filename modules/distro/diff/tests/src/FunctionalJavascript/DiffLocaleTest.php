<?php

namespace Drupal\Tests\diff\FunctionalJavascript;

use Drupal\Tests\diff\Functional\CoreVersionUiTestTrait;

/**
 * Test diff functionality with localization and translation.
 *
 * @group diff
 */
class DiffLocaleTest extends DiffTestBase {

  use CoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'locale',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Add French language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Run all independent tests.
   */
  public function testAll() {
    $this->doTestTranslationRevisions();
    $this->doTestUndefinedTranslationFilter();
    $this->doTestTranslationFilter();
  }

  /**
   * Test Diff functionality for the revisions of a translated node.
   */
  protected function doTestTranslationRevisions() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create an article and its translation. Assert aliases.
    $edit = array(
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
    );
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));
    $english_node = $this->drupalGetNodeByTitle('English node');

    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $assert_session->elementExists('css', 'a[href="#edit-revision-information"]')->click();
    $page->fillField('title[0][value]', 'French node');
    $page->uncheckField('revision');
    $this->drupalPostNodeForm(NULL, [], t('Save and keep published (this translation)'));
    $this->rebuildContainer();
    $english_node = $this->drupalGetNodeByTitle('English node');
    $french_node = $english_node->getTranslation('fr');

    // Create a new revision on both languages.
    $edit = array(
      'title[0][value]' => 'Updated title',
      'revision' => TRUE,
    );
    $this->drupalPostNodeForm('node/' . $english_node->id() . '/edit', $edit, t('Save and keep published (this translation)'));
    $edit = array(
      'title[0][value]' => 'Le titre',
      'revision' => TRUE,
    );
    $this->drupalPostNodeForm('fr/node/' . $english_node->id() . '/edit', $edit, t('Save and keep published (this translation)'));

    // View differences between revisions. Check that they don't mix up.
    $this->drupalGet('node/' . $english_node->id() . '/revisions');
    $this->drupalGet('node/' . $english_node->id() . '/revisions/view/1/2/split_fields');
    $assert_session->pageTextContains('Title');
    $assert_session->pageTextContains('English node');
    $assert_session->pageTextContains('Updated title');
    $this->drupalGet('fr/node/' . $english_node->id() . '/revisions');
    $this->drupalGet('fr/node/' . $english_node->id() . '/revisions/view/1/3/split_fields');
    $assert_session->pageTextContains('Title');
    $assert_session->pageTextNotContains('English node');
    $assert_session->pageTextNotContains('Updated title');
    $assert_session->pageTextContains('French node');
    $assert_session->pageTextContains('Le titre');
  }

  /**
   * Tests the translation filtering when navigating trough revisions.
   */
  protected function doTestTranslationFilter() {
    // Create a node in English.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'english_revision_0',
    ]);
    $revision1 = $node->getRevisionId();

    // Translate to french.
    $node->addTranslation('fr', ['title' => 'french_revision_0']);
    $node->save();

    // Create a revision in English.
    $english_node = $node->getTranslation('en');
    $english_node->setTitle('english_revision_1');
    $english_node->setNewRevision(TRUE);
    $english_node->save();
    $revision2 = $node->getRevisionId();

    // Create a revision in French.
    $french_node = $node->getTranslation('fr');
    $french_node->setTitle('french_revision_1');
    $french_node->setNewRevision(TRUE);
    $french_node->save();

    // Create a new revision in English.
    $english_node = $node->getTranslation('en');
    $english_node->setTitle('english_revision_2');
    $english_node->setNewRevision(TRUE);
    $english_node->save();

    // Create a new revision in French.
    $french_node = $node->getTranslation('fr');
    $french_node->setTitle('french_revision_2');
    $french_node->setNewRevision(TRUE);
    $french_node->save();

    // Compare first two revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions/view/' . $revision1 . '/' . $revision2 . '/split_fields');
    $diffs = $this->getSession()->getPage()->findAll('xpath', '//span[@class="diffchange"]');
    $this->assertEqual($diffs[0]->getText(), 'english_revision_0');
    $this->assertEqual($diffs[1]->getText(), 'english_revision_1');

    // Check next difference.
    $this->clickLink('Next change');
    $diffs = $this->getSession()->getPage()->findAll('xpath', '//span[@class="diffchange"]');
    $this->assertEqual($diffs[0]->getText(), 'english_revision_1');
    $this->assertEqual($diffs[1]->getText(), 'english_revision_2');

    // There shouldn't be other differences in the current language.
    $this->assertNoLink('Next change');
  }

  /**
   * Tests the undefined translation filtering when navigating trough revisions.
   */
  protected function doTestUndefinedTranslationFilter() {
    // Create a node in with undefined langcode.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'undefined_language_revision_0',
      'langcode' => 'und',
    ]);
    $revision1 = $node->getRevisionId();

    // Create 3 new revisions of the node.
    $node->setTitle('undefined_language_revision_1');
    $node->setNewRevision(TRUE);
    $node->save();
    $revision2 = $node->getRevisionId();

    $node->setTitle('undefined_language_revision_2');
    $node->setNewRevision(TRUE);
    $node->save();

    $node->setTitle('undefined_language_revision_3');
    $node->setNewRevision(TRUE);
    $node->save();

    // Check the amount of revisions displayed.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $element = $this->getSession()->getPage()->findAll('xpath', '//*[@id="edit-node-revisions-table"]/tbody/tr');
    $this->assertEqual(count($element), 4);

    // Compare the first two revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions/view/' . $revision1 . '/' . $revision2 . '/split_fields');
    $diffs = $this->getSession()->getPage()->findAll('xpath', '//span[@class="diffchange"]');
    $this->assertEqual($diffs[0]->getText(), 'undefined_language_revision_0');
    $this->assertEqual($diffs[1]->getText(), 'undefined_language_revision_1');

    // Compare the next two revisions.
    $this->clickLink('Next change');
    $diffs = $this->getSession()->getPage()->findAll('xpath', '//span[@class="diffchange"]');
    $this->assertEqual($diffs[0]->getText(), 'undefined_language_revision_1');
    $this->assertEqual($diffs[1]->getText(), 'undefined_language_revision_2');
  }

}
