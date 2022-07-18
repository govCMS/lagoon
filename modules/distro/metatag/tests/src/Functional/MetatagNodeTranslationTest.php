<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ensures that meta tag values are translated correctly on nodes.
 *
 * @group metatag
 */
class MetatagNodeTranslationTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_translation',
    'field_ui',
    'metatag',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default language code to use in this test.
   *
   * @var array
   */
  protected $defaultLangcode = 'fr';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup basic environment.
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_permissions = [
      'administer content types',
      'administer content translation',
      'administer languages',
      'administer nodes',
      'administer node fields',
      'bypass node access',
      'create content translations',
      'delete content translations',
      'translate any entity',
      'update content translations',
    ];

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests the metatag value translations.
   */
  public function testMetatagValueTranslation() {
    $save_label_i18n = 'Save (this translation)';

    // Set up a content type.
    $name = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => 'metatag_node', 'name' => $name]);

    // Add a metatag field to the content type.
    $this->drupalGet('admin/structure/types');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $this->drupalGet('admin/structure/types/manage/metatag_node');
    $session->statusCodeEquals(200);
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->submitForm($edit, $this->t('Save content type'));
    $session->statusCodeEquals(200);

    $this->drupalGet('admin/structure/types/manage/metatag_node/fields/add-field');
    $session->statusCodeEquals(200);
    $edit = [
      'label' => 'Meta tags',
      'field_name' => 'meta_tags',
      'new_storage_type' => 'metatag',
    ];
    $this->submitForm($edit, $this->t('Save and continue'));
    $session->statusCodeEquals(200);
    $this->submitForm([], $this->t('Save field settings'));
    $session->statusCodeEquals(200);
    $edit = [
      'translatable' => TRUE,
    ];
    $this->submitForm($edit, $this->t('Save settings'));
    $session->statusCodeEquals(200);
    $this->drupalGet('admin/structure/types/manage/metatag_node/fields/node.metatag_node.field_meta_tags');
    $session->statusCodeEquals(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.
    // Load the node form.
    $this->drupalGet('node/add/metatag_node');
    $session->statusCodeEquals(200);

    // Check the default values are correct.
    $session->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $session->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    // Create a node.
    $edit = [
      'title[0][value]' => 'Node Français',
      'body[0][value]' => 'French summary.',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'French summary.');

    $this->drupalGet('node/1/translations/add/en/es');
    $session->statusCodeEquals(200);
    // Check the default values are there.
    $session->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $session->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    $edit = [
      'title[0][value]' => 'Node Español',
      'body[0][value]' => 'Spanish summary.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $session->statusCodeEquals(200);

    $this->drupalGet('es/node/1');
    $session->statusCodeEquals(200);
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');

    $this->drupalGet('node/1/edit');
    $session->statusCodeEquals(200);
    // Check the default values are there.
    $session->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $session->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    // Set explicit values on the description metatag instead using the
    // defaults.
    $this->drupalGet('node/1/edit');
    $session->statusCodeEquals(200);
    $edit = [
      'field_meta_tags[0][basic][description]' => 'Overridden French description.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $session->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Overridden French description.');
    $this->assertNotEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');

    $this->drupalGet('es/node/1/edit');
    $session->statusCodeEquals(200);
    $edit = [
      'field_meta_tags[0][basic][description]' => 'Overridden Spanish description.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $session->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Overridden Spanish description.');
    $this->assertNotEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');
  }

}
