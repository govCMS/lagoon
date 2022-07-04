<?php

namespace Drupal\Tests\purge_purger_http\Functional;

use Drupal\Tests\purge_ui\Functional\Form\Config\PurgerConfigFormTestBase;

/**
 * Testbase for testing \Drupal\purge_purger_http\Form\HttpPurgerFormBase.
 */
abstract class HttpPurgerConfigFormTestBase extends PurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['purge_purger_http'];

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_purger_http.configuration_form';

  /**
   * The token group names this purger supports replacing tokens for.
   *
   * @var string[]
   *
   * @see purge_tokens_token_info()
   */
  protected $tokenGroups = [];

  /**
   * Verify that the form contains all fields we require.
   */
  public function testFieldExistence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $fields = [
      'edit-name' => '',
      'edit-invalidationtype' => 'tag',
      'edit-hostname' => 'localhost',
      'edit-port' => 80,
      'edit-path' => '/',
      'edit-request-method' => 0,
      'edit-scheme' => 0,
      'edit-verify' => TRUE,
      'edit-headers-0-field' => '',
      'edit-headers-0-value' => '',
      'edit-show-body-form' => '',
      'edit-body-content-type' => 'text/plain',
      'edit-body' => '',
      'edit-runtime-measurement' => '1',
      'edit-timeout' => 1.0,
      'edit-connect-timeout' => 1.0,
      'edit-cooldown-time' => 0.0,
      'edit-http-errors' => '1',
      'edit-max-requests' => 100,
    ];
    foreach ($fields as $field => $default_value) {
      $this->assertSession()->fieldValueEquals($field, $default_value);
    }
  }

  /**
   * Tests ::buildFormTokensHelp().
   *
   * @see \Drupal\purge_purger_http\Form\HttpPurgerFormBase::buildFormTokensHelp
   */
  public function testTokensHelp(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextContains('Tokens');
    foreach ($this->tokenGroups as $token_group) {
      $this->assertRaw('<code>[' . $token_group . ':');
    }
  }

  /**
   * Test validating the data.
   */
  public function testFormValidation(): void {
    // Assert that valid timeout values don't cause validation errors.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValues(
      [
        'connect_timeout' => 0.3,
        'timeout' => 0.1,
        'name' => 'foobar',
      ]
    );
    $form = $this->getFormInstance();
    $this->formBuilder()->submitForm($form, $form_state);
    $this->assertEquals(0, count($form_state->getErrors()));
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValues(
      [
        'connect_timeout' => 2.3,
        'timeout' => 7.7,
        'name' => 'foobar',
      ]
    );
    $form = $this->getFormInstance();
    $this->formBuilder()->submitForm($form, $form_state);
    $this->assertEquals(0, count($form_state->getErrors()));
    // Submit timeout values that are too low and confirm the validation error.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValues(
      [
        'connect_timeout' => 0.0,
        'timeout' => 0.0,
        'name' => 'foobar',
      ]
    );
    $form = $this->getFormInstance();
    $this->formBuilder()->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEquals(2, count($errors));
    $this->assertTrue(isset($errors['timeout']));
    $this->assertTrue(isset($errors['connect_timeout']));
    // Submit timeout values that are too high and confirm the validation error.
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValues(
      [
        'connect_timeout' => 2.4,
        'timeout' => 7.7,
        'name' => 'foobar',
      ]
    );
    $form = $this->getFormInstance();
    $this->formBuilder()->submitForm($form, $form_state);
    $errors = $form_state->getErrors();
    $this->assertEquals(2, count($errors));
    $this->assertTrue(isset($errors['timeout']));
    $this->assertTrue(isset($errors['connect_timeout']));
  }

  /**
   * Test posting data to the HTTP Purger settings form.
   */
  public function testSaveConfigurationSubmit(): void {
    // Assert that all (simple) fields submit as intended.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'name' => 'foobar',
      'invalidationtype' => 'wildcardurl',
      'hostname' => 'example.com',
      'port' => 8080,
      'path' => 'node/1',
      'request_method' => 1,
      'scheme' => 0,
      'verify' => TRUE,
      'show_body_form' => 1,
      'body_content_type' => 'foo/bar',
      'body' => 'baz',
      'timeout' => 6,
      'runtime_measurement' => 1,
      'connect_timeout' => 0.5,
      'cooldown_time' => 0.8,
      'max_requests' => 25,
      'http_errors' => 1,
    ];
    $this->drupalPostForm($this->getPath(), $edit, 'Save configuration');
    $this->drupalGet($this->getPath());
    foreach ($edit as $field => $value) {
      $this->assertSession()->fieldValueEquals('edit-' . str_replace('_', '-', $field), $value);
    }
    // Assert headers behavior.
    $form = $this->getFormInstance();
    $form_state = $this->getFormStateInstance();
    $form_state->addBuildInfo('args', $this->formArgs);
    $form_state->setValue('headers', [['field' => 'foo', 'value' => 'bar']]);
    $this->formBuilder()->submitForm($form, $form_state);
    $this->assertEquals(0, count($form_state->getErrors()));
    $this->drupalGet($this->getPath());
    $this->assertSession()->fieldValueEquals('edit-headers-0-field', 'foo');
    $this->assertSession()->fieldValueEquals('edit-headers-0-value', 'bar');
  }

}
