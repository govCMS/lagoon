<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Verify that different meta tag API options are supported.
 *
 * @group metatag
 */
class MetatagTagTypesTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    // Needed for token handling.
    'token',

    // Needed for the field UI testing.
    'field_ui',

    // Needed for the basic entity testing.
    'entity_test',

    // Needed to verify that nothing is broken for unsupported entities.
    'contact',

    // The base module.
    'metatag',

    // Some extra custom logic for testing Metatag.
    'metatag_test_tag',

    // Needed for testSecureTagOption().
    'metatag_open_graph',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
    'administer meta tags',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    // Add a metatag field to the entity type test_entity.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag',
      'new_storage_type' => 'metatag',
    ];
    $this->submitForm($edit, $this->t('Save and continue'));
    $this->submitForm([], $this->t('Save field settings'));
    $this->container->get('entity_field.manager')
      ->clearCachedFieldDefinitions();
  }

  /**
   * Tests whether HTML is correctly removed from metatags.
   *
   * Tests three values in meta tags -- one without any HTML; one with raw html;
   * and one with escaped HTML. To pass all HTML including escaped should be
   * removed.
   */
  public function testHtmlIsRemoved() {
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $values = [
      'abstract' => 'No HTML here',
      'description' => '<html><body><p class="test">Surrounded by raw HTML</p></body></html>',
      'keywords' => '&lt;html&gt;&lt;body&gt;&lt;p class="test"&gt;Surrounded by escaped HTML&lt;/p&gt;&lt;/body&gt;&lt;/html&gt;',
    ];

    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();
    $this->drupalGet('hit-a-404');
    $session->statusCodeEquals(404);

    $session->responseContains('<meta name="abstract" content="No HTML here" />');
    $session->responseContains('<meta name="description" content="Surrounded by raw HTML" />');
    $session->responseContains('<meta name="keywords" content="Surrounded by escaped HTML" />');
  }

  /**
   * Tests the 'secure' meta tag attribute.
   *
   * Tests insecure values in og:image:secure_url (a tag with secure attribute
   * set to TRUE) and in og:image (a tag with secure attribute set to FALSE). To
   * To pass og:image_secure should be changed to https:// and og:image
   * unchanged.
   */
  public function testSecureTagOption() {
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $values = [
      'og_image' => 'https://blahblahblah.com/insecure.jpg',
      'og_image_secure_url' => 'https://blahblahblah.com/secure.jpg',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();
    $this->drupalGet('');
    $session->statusCodeEquals(200);

    $session->responseContains('<meta property="og:image" content="https://blahblahblah.com/insecure.jpg" />');
    $session->responseContains('<meta property="og:image:secure_url" content="https://blahblahblah.com/secure.jpg" />');
  }

  /**
   * Check the contact form.
   *
   * @todo Move this somewhere else.
   */
  public function testContactForm() {
    // Test a route where the entity for that route does not implement
    // ContentEntityInterface.
    $controller = \Drupal::entityTypeManager()->getStorage('contact_form');
    $controller->create([
      'id' => 'test_contact_form',
    ])->save();
    $account = $this->drupalCreateUser(['access site-wide contact form']);
    $this->drupalLogin($account);
    $this->drupalGet('contact/test_contact_form');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Check URL handling.
   *
   * @todo Finish.
   */
  public function todoTestUrl() {
    // @code
    // $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? $this->t('Save and publish') : $this->t('Save');
    // // Tests meta tags with URLs work.
    // $this->drupalGet($this->entity_add_path);
    // $this->assertSession()->statusCodeEquals(200);
    // $edit = [
    //   'name[0][value]' => 'UrlTags',
    //   'user_id[0][target_id]' => 'foo (' . $this->adminUser->id() . ')',
    //   'field_metatag[0][advanced][original_source]' => 'https://example.com/foo.html',
    // ];
    // $this->submitForm($edit, $save_label);
    // $entities = entity_load_multiple_by_properties('entity_test', [
    //   'name' => 'UrlTags',
    // ]);
    // $this->assertEquals(count($entities), 1, 'Entity was saved');
    // $entity = reset($entities);
    // $this->drupalGet($this->entity_base_path . '/' . $entity->id());
    // $this->assertSession()->statusCodeEquals(200);
    // $elements = $this->cssSelect("meta[name='original-source']");
    // $this->assertTrue(count($elements) === 1, 'Found original source metatag from defaults');
    // $this->assertEquals($edit['field_metatag[0][advanced][original_source]'], (string) $elements[0]['content']);
    // @endcode
  }

}
