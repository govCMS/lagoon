<?php

namespace Drupal\Tests\google_analytics\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests add more behavior for a multiple value field.
 *
 * @group google_analytics
 */
class GoogleAnalyticsFormValidationTest extends WebDriverTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'token', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer nodes',
      'create article content',
    ];

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if Custom Dimensions token form validation works.
   */
  public function testGoogleAnalyticsCustomDimensionsTokenFormValidation() {
    $this->drupalGet('admin/config/services/google-analytics');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set the UA Code
    $user_name = $assert_session->waitForField('accounts[0][value]');
    $account_field = $page->findField('accounts[0][value]');
    $account_field->setValue('UA-123456-1');

    $dms = $assert_session->waitForLink('Dimensions and Metrics');
    $page->clickLink('Dimensions and Metrics');

    // First set a value on the first input field.
    $field_0_name = $page->findField('custom_parameters[0][name]');
    $field_0_name->setValue('current_user_name');
    $field_0_value = $page->findField('custom_parameters[0][value]');
    $field_0_value->setValue('[current-user:name]');

    // Validate the value of the first field exists.
    $this->assertEquals('current_user_name', $field_0_name->getValue(), 'Name for the first item has not changed.');
    $this->assertEquals('[current-user:name]', $field_0_value->getValue(), 'Value for the first item has not changed.');

    /** TODO: Fix tests in Issue #3243622
    $add_more_button = $page->findButton('Add another Parameter');
    // Add another item
    $add_more_button->click();
    $field_1 = $assert_session->waitForField('custom_parameters[1][name]');
    $this->assertNotEmpty($field_1, 'Successfully added another item.');

    // Validate the value of the first field has not changed.
    $this->assertEquals('current_user_name', $field_0_name->getValue(), 'Name for the first item has not changed.');
    $this->assertEquals('[current-user:name]', $field_0_value->getValue(), 'Value for the first item has not changed.');

    // Validate the value of the second item is empty.
    $this->assertEmpty($field_1->getValue(), 'Value for the second item is currently empty.');

    $field_1_name = $page->findField('custom_parameters[1][name]');
    $field_1_name->setValue('current_user_edit_url');
    $field_1_value = $page->findField('custom_parameters[1][value]');
    $field_1_value->setValue('[current-user:edit-url]');

    // Add third item
    $add_more_button->click();
    $field_2 = $assert_session->waitForField('custom_parameters[2][name]');
    $this->assertNotEmpty($field_2, 'Successfully added another item.');

    $field_2_name = $page->findField('custom_parameters[2][name]');
    $field_2_name->setValue('user_name');
    $field_2_value = $page->findField('custom_parameters[2][value]');
    $field_2_value->setValue('[user:name]');

    // Add forth item
    $add_more_button->click();
    $field_3 = $assert_session->waitForField('custom_parameters[3][name]');
    $this->assertNotEmpty($field_3, 'Successfully added another item.');

    $field_3_name = $page->findField('custom_parameters[3][name]');
    $field_3_name->setValue('term_name');
    $field_3_value = $page->findField('custom_parameters[3][value]');
    $field_3_value->setValue('[term:name]');

    // Add fifth item
    $add_more_button->click();
    $field_4 = $assert_session->waitForField('custom_parameters[4][name]');
    $this->assertNotEmpty($field_4, 'Successfully added another item.');

    $field_4_name = $page->findField('custom_parameters[4][name]');
    $field_4_name->setValue('term_tid');
    $field_4_value = $page->findField('custom_parameters[4][value]');
    $field_4_value->setValue('[term:tid]');

    $page->pressButton('op');

    // Check form validation.
    $this->assertSession()->responseContains($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 0]), '@invalid-tokens' => implode(', ', ['[current-user:name]'])]));
    $this->assertSession()->responseContains($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 1]), '@invalid-tokens' => implode(', ', ['[current-user:edit-url]'])]));
    $this->assertSession()->responseContains($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 2]), '@invalid-tokens' => implode(', ', ['[user:name]'])]));
    // BUG #2037595
    //$this->assertSession()->responseNotContains($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 4]), '@invalid-tokens' => implode(', ', ['[term:name]'])]));
    //$this->assertSession()->responseNotContains($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 5]), '@invalid-tokens' => implode(', ', ['[term:tid]'])]));
  */
  }

}
