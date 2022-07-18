<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test custom dimensions and metrics functionality of Google Analytics module.
 *
 * @group Google Analytics
 *
 * @dependencies token
 */
class GoogleAnalyticsCustomDimensionsAndMetricsTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'token', 'node'];

  /**
   * Default theme.
   *
   * @var string
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
   * Tests if custom dimensions are properly added to the page.
   */
  public function testGoogleAnalyticsCustomDimensions() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);

    // Basic test if the feature works.
    $google_analytics_custom_dimension = [
      'dimension1' => [
        'type' => 'dimension',
        'name' => 'bar1',
        'value' => 'Bar 1',
      ],
      'dimension2' => [
        'type' => 'dimension',
        'name' => 'bar2',
        'value' => 'Bar 2',
      ],
      'dimension3' => [
        'type' => 'dimension',
        'name' => 'bar2',
        'value' => 'Bar 3',
      ],
      'dimension4' => [
        'type' => 'dimension',
        'name' => 'bar4',
        'value' => 'Bar 4',
      ],
      'dimension5' => [
        'type' => 'dimension',
        'name' => 'bar5',
        'value' => 'Bar 5',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.parameters', $google_analytics_custom_dimension)->save();
    $this->drupalGet('');

    $custom_map = [];
    $custom_vars = [];
    foreach ($google_analytics_custom_dimension as $index => $dimension) {
      $custom_map['custom_map'][$index] = $dimension['name'];
      $custom_vars[$dimension['name']] = $dimension['value'];
    }
    // Verify the account ID exists in the config.
    $this->assertSession()->responseContains('gtag("config", ' . Json::encode($ua_code));
    // Check the dimensions.
    $this->assertSession()->responseContains('"custom_map":' . Json::encode($custom_map['custom_map']));
    $this->assertSession()->responseContains('gtag("event", "custom", ' . Json::encode($custom_vars) . ');');

    // Test whether tokens are replaced in custom dimension values.
    $site_slogan = $this->randomMachineName(16);
    $this->config('system.site')->set('slogan', $site_slogan)->save();

    $google_analytics_custom_dimension = [
      'dimension1' => [
        'type' => 'dimension',
        'name' => 'site_slogan',
        'value' => 'Value: [site:slogan]',
      ],
      'dimension2' => [
        'type' => 'dimension',
        'name' => 'machine_name',
        'value' => $this->randomMachineName(16),
      ],
      'dimension3' => [
        'type' => 'dimension',
        'name' => 'foo3',
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      'dimension4' => [
        'type' => 'dimension',
        'name' => 'bar4',
        'value' => '0',
      ],
      'dimension5' => [
        'type' => 'dimension',
        'name' => 'node_type',
        'value' => '[node:type]',
      ],
      // Test google_analytics_tokens().
      'dimension6' => [
        'type' => 'dimension',
        'name' => 'current_user_role_names',
        'value' => '[current-user:role-names]',
      ],
      'dimension7' => [
        'type' => 'dimension',
        'name' => 'current_user_role_ids',
        'value' => '[current-user:role-ids]',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.parameters', $google_analytics_custom_dimension)->save();
    dump('<pre>' . print_r($google_analytics_custom_dimension, TRUE) . '</pre>');

    // Test on frontpage.
    $this->drupalGet('');
    $this->assertSession()->responseContains(Json::encode('dimension1') . ':' . Json::encode($google_analytics_custom_dimension['dimension1']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension1']['name']) . ':' . Json::encode("Value: $site_slogan"));
    $this->assertSession()->responseContains(Json::encode('dimension2') . ':' . Json::encode($google_analytics_custom_dimension['dimension2']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension2']['name']) . ':' . Json::encode($google_analytics_custom_dimension['dimension2']['value']));
    $this->assertSession()->responseNotContains(Json::encode('dimension3') . ':' . Json::encode($google_analytics_custom_dimension['dimension3']['name']));
    $this->assertSession()->responseNotContains(Json::encode($google_analytics_custom_dimension['dimension3']['name']) . ':' . Json::encode(''));
    $this->assertSession()->responseContains(Json::encode('dimension4') . ':' . Json::encode($google_analytics_custom_dimension['dimension4']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension4']['name']) . ':' . Json::encode('0'));
    $this->assertSession()->responseNotContains(Json::encode('dimension5') . ':' . Json::encode($google_analytics_custom_dimension['dimension5']['name']));
    $this->assertSession()->responseNotContains(Json::encode($google_analytics_custom_dimension['dimension5']['name']) . ':' . Json::encode('article'));
    $this->assertSession()->responseContains(Json::encode('dimension6') . ':' . Json::encode($google_analytics_custom_dimension['dimension6']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension6']['name']) . ':' . Json::encode(implode(',', \Drupal::currentUser()->getRoles())));
    $this->assertSession()->responseContains(Json::encode('dimension7') . ':' . Json::encode($google_analytics_custom_dimension['dimension7']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension7']['name']) . ':' . Json::encode(implode(',', array_keys(\Drupal::currentUser()->getRoles()))));

    // Test on a node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->responseContains(Json::encode('dimension5') . ':' . Json::encode($google_analytics_custom_dimension['dimension5']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_dimension['dimension5']['name']) . ':' . Json::encode('article'));
  }

  /**
   * Tests if custom metrics are properly added to the page.
   */
  public function testGoogleAnalyticsCustomMetrics() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Basic test if the feature works.
    $google_analytics_custom_metric = [
      'metric1' => [
        'type' => 'metric',
        'name' => 'foo1',
        'value' => '6',
      ],
      'metric2' => [
        'type' => 'metric',
        'name' => 'foo2',
        'value' => '8000',
      ],
      'metric3' => [
        'type' => 'metric',
        'name' => 'foo3',
        'value' => '7.8654',
      ],
      'metric4' => [
        'type' => 'metric',
        'name' => 'foo4',
        'value' => '1123.4',
      ],
      'metric5' => [
        'type' => 'metric',
        'name' => 'foo5',
        'value' => '5,67',
      ],
    ];

    $this->config('google_analytics.settings')->set('custom.parameters', $google_analytics_custom_metric)->save();
    $this->drupalGet('');

    $custom_map = [];
    $custom_vars = [];
    foreach ($google_analytics_custom_metric as $index => $metric) {
      $custom_map['custom_map'][$index] = $metric['name'];
      $custom_vars[$metric['name']] = floatval($metric['value']);
    }

    // Verify the account ID exists in the config.
    $this->assertSession()->responseContains('gtag("config", ' . Json::encode($ua_code));
    // Check the dimensions.
    $this->assertSession()->responseContains('"custom_map":' . Json::encode($custom_map['custom_map']));
    $this->assertSession()->responseContains('gtag("event", "custom", ' . Json::encode($custom_vars) . ');');

    // Test whether tokens are replaced in custom metric values.
    $google_analytics_custom_metric = [
      'metric1' => [
        'type' => 'metric',
        'name' => 'bar1',
        'value' => '[current-user:roles:count]',
      ],
      'metric2' => [
        'type' => 'metric',
        'name' => 'bar2',
        'value' => mt_rand(),
      ],
      'metric3' => [
        'type' => 'metric',
        'name' => 'bar3',
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      'metric4' => [
        'type' => 'metric',
        'name' => 'bar4',
        'value' => '0',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.parameters', $google_analytics_custom_metric)->save();
    //dump(print_r($google_analytics_custom_metric, TRUE));

    $this->drupalGet('');
    $this->assertSession()->responseContains(Json::encode('metric1') . ':' . Json::encode($google_analytics_custom_metric['metric1']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_metric['metric1']['name']) . ':');
    $this->assertSession()->responseContains(Json::encode('metric2') . ':' . Json::encode($google_analytics_custom_metric['metric2']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_metric['metric2']['name']) . ':' . Json::encode($google_analytics_custom_metric['metric2']['value']));
    $this->assertSession()->responseNotContains(Json::encode('metric3') . ':' . Json::encode($google_analytics_custom_metric['metric3']['name']));
    $this->assertSession()->responseNotContains(Json::encode($google_analytics_custom_metric['metric3']['name']) . ':' . Json::encode(''));
    $this->assertSession()->responseContains(Json::encode('metric4') . ':' . Json::encode($google_analytics_custom_metric['metric4']['name']));
    $this->assertSession()->responseContains(Json::encode($google_analytics_custom_metric['metric4']['name']) . ':' . Json::encode(0));
  }

}
