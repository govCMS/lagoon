<?php

namespace Drupal\Tests\google_analytics\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\google_analytics\Form\GoogleAnalyticsAdminSettingsForm;

/**
 * Tests the google_analytics settings form.
 *
 * @group google_analytics
 */
class GoogleAnalyticsAdminSettingsFormTest extends KernelTestBase {

  /**
   * The google_analytics form object under test.
   *
   * @var \Drupal\google_analytics\Form\GoogleAnalyticsAdminSettingsForm
   */
  protected $googleAnalyticsSettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'path_alias',
    'user',
    'google_analytics',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->googleAnalyticsSettingsForm = new GoogleAnalyticsAdminSettingsForm(
      $this->container->get('config.factory'),
      $this->container->get('current_user'),
      $this->container->get('module_handler'),
      $this->container->get('google_analytics.accounts'),
      $this->container->get('google_analytics.javascript_cache')
    );
  }

  /**
   * Tests for \Drupal\google_analytics\Form\GoogleAnalyticsAdminSettingsForm.
   */
  public function testGoogleAnalyticsAdminSettingsForm() {
    $this->assertInstanceOf(FormInterface::class, $this->googleAnalyticsSettingsForm);

    $this->assertEquals('google_analytics_admin_settings', $this->googleAnalyticsSettingsForm->getFormId());

    $method = new \ReflectionMethod(GoogleAnalyticsAdminSettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->googleAnalyticsSettingsForm);
    $this->assertEquals(['google_analytics.settings'], $name);
  }

}
