<?php

namespace Drupal\Tests\diff\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Diff module plugins.
 *
 * @group diff
 */
class DiffPluginTest extends DiffPluginTestBase {

  use CommentTestTrait;
  use CoreVersionUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
  ];

  /**
   * Adds a text field.
   *
   * @param string $field_name
   *   The machine field name.
   * @param string $label
   *   The field label.
   * @param string $field_type
   *   The field type.
   * @param string $widget_type
   *   The widget type.
   */
  protected function addArticleTextField($field_name, $label, $field_type, $widget_type) {
    // Create a field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_type,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => $label,
    ])->save();
    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => $widget_type])
      ->save();
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Tests the changed field without plugins.
   */
  public function testFieldWithNoPlugin() {
    // Create an article.
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);

    // Update the article and add a new revision, the "changed" field should be
    // updated which does not have plugins provided by diff.
    $edit = [
      'revision' => TRUE,
      'body[0][value]' => 'change',
    ];
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the difference between the last two revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, NULL, t('Compare selected revisions'));

    // "changed" field is not displayed since there is no plugin for it. This
    // should not break the revisions comparison display.
    $this->assertResponse(200);
    $this->assertLink(t('Split fields'));
  }

  /**
   * Tests the access check for a field while comparing revisions.
   */
  public function testFieldNoAccess() {
    // Add a text and a text field to article.
    $this->addArticleTextField('field_diff_deny_access', 'field_diff_deny_access', 'string', 'string_textfield');

    // Create an article.
    $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article access',
      'field_diff_deny_access' => 'Foo',
    ]);

    // Create a revision of the article.
    $node = $this->getNodeByTitle('Test article access');
    $node->setTitle('Test article no access');
    $node->set('field_diff_deny_access', 'Fighters');
    $node->setNewRevision(TRUE);
    $node->save();

    // Check the "Text Field No Access" field is not displayed.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertResponse(200);
    $this->assertNoText('field_diff_deny_access');
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 2);
  }

  /**
   * Tests plugin applicability and weight relevance.
   *
   * @covers \Drupal\diff_test\Plugin\diff\Field\TestHeavierTextPlugin
   * @covers \Drupal\diff_test\Plugin\diff\Field\TestLighterTextPlugin
   */
  public function testApplicablePlugin() {
    // Add three text fields to the article.
    $this->addArticleTextField('test_field', 'Test Applicable', 'text', 'text_textfield');
    $this->addArticleTextField('test_field_lighter', 'Test Lighter Applicable', 'text', 'text_textfield');
    $this->addArticleTextField('test_field_non_applicable', 'Test Not Applicable', 'text', 'text_textfield');

    // Create an article, setting values on fields.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
      'test_field' => 'first_nice_applicable',
      'test_field_lighter' => 'second_nice_applicable',
      'test_field_non_applicable' => 'not_applicable',
    ]);

    // Edit the article and update these fields, creating a new revision.
    $edit = [
      'test_field[0][value]' => 'first_nicer_applicable',
      'test_field_lighter[0][value]' => 'second_nicer_applicable',
      'test_field_non_applicable[0][value]' => 'nicer_not_applicable',
      'revision' => TRUE,
    ];
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));

    // Check diff for an applicable field of testTextPlugin.
    $this->assertText('Test Applicable');
    $this->assertText('first_nice_heavier_test_plugin');
    $this->assertText('first_nicer_heavier_test_plugin');

    // Check diff for an applicable field of testTextPlugin and
    // testLighterTextPlugin. The plugin selected for this field should be the
    // lightest one.
    $this->assertText('Test Lighter Applicable');
    $this->assertText('second_nice_lighter_test_plugin');
    $this->assertText('second_nicer_lighter_test_plugin');

    // Check diff for a non applicable field of both test plugins.
    $this->assertText('Test Not Applicable');
    $this->assertText('not_applicable');
    $this->assertText('nicer_not_applicable');
  }

  /**
   * Tests field content trimming.
   */
  public function testTrimmingField() {
    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'test_trim',
      'body' => '<p>body</p>',
    ]);
    // Save a second revision.
    $node->save();

    // Create a revision adding a new empty line to the body.
    $node = $this->drupalGetNodeByTitle('test_trim');
    $edit = [
      'revision' => TRUE,
      'body[0][value]' => '<p>body</p>
',
    ];
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Assert the revision comparison.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertNoText('No visible changes.');
    $rows = $this->xpath('//tbody/tr');
    $diff_row = $rows[1]->findAll('xpath', '/td');
    $this->assertEqual(count($rows), 3);
    $this->assertEqual(htmlspecialchars_decode(strip_tags($diff_row[2]->getHtml())), '<p>body</p>');

    // Create a new revision and update the body.
    $edit = [
      'revision' => TRUE,
      'body[0][value]' => '<p>body</p>

<p>body_new</p>
',
    ];
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertNoText('No visible changes.');
    // Assert that empty rows also show a line number.
    $rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($rows), 5);
    $diff_row = $rows[4]->findAll('xpath', '/td');
    $this->assertEqual(htmlspecialchars_decode(strip_tags($diff_row[3]->getHtml())), '4');
    $this->assertEqual(htmlspecialchars_decode(strip_tags($diff_row[0]->getHtml())), '2');
  }

}
