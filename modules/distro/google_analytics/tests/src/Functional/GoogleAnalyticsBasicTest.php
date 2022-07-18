<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsBasicTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * User without permissions to use snippets.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noSnippetUser;

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'google_analytics',
    'help',
  ];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up google_analytics.
    $this->noSnippetUser = $this->drupalCreateUser($permissions);
    $permissions[] = 'add JS snippets for google analytics';
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    // Place the block or the help is not shown.
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testGoogleAnalyticsConfiguration() {
    // Check if Configure link is available on 'Extend' page.
    // Requires 'administer modules' permission.
    $this->drupalGet('admin/modules');
    $this->assertSession()->responseContains('admin/config/services/google-analytics');

    // Check if Configure link is available on 'Status Reports' page.
    // NOTE: Link is only shown without UA code configured.
    // Requires 'administer site configuration' permission.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->responseContains('admin/config/services/google-analytics');

    // Check for setting page's presence.
    $this->drupalGet('admin/config/services/google-analytics');
    $this->assertSession()->responseContains($this->t('Web Property ID(s)'));

    // Check for account code validation.
    $edit['accounts[0][value]'] = $this->randomMachineName(2);
    $this->drupalGet('admin/config/services/google-analytics');
    $this->submitForm($edit, $this->t('Save configuration'));
    $this->assertSession()->responseContains($this->t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxx-yy, G-xxxxxxxx, AW-xxxxxxxxx, or DC-xxxxxxxx.'));

    // User should have access to code snippets.
    $this->assertSession()->fieldExists('google_analytics_codesnippet_create');
    $this->assertSession()->fieldExists('google_analytics_codesnippet_before');
    $this->assertSession()->fieldExists('google_analytics_codesnippet_after');
    $this->assertEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_create' and @disabled='disabled']"), '"Parameters" field is enabled.');
    $this->assertEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_before' and @disabled='disabled']"), '"Code snippet (before)" is enabled.');
    $this->assertEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_after' and @disabled='disabled']"), '"Code snippet (after)" is enabled.');

    // Login as user without JS permissions.
    $this->drupalLogin($this->noSnippetUser);
    $this->drupalGet('admin/config/services/google-analytics');

    // User should *not* have access to snippets, but parameters field.
    $this->assertSession()->fieldExists('google_analytics_codesnippet_create');
    $this->assertSession()->fieldExists('google_analytics_codesnippet_before');
    $this->assertSession()->fieldExists('google_analytics_codesnippet_after');
    $this->assertEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_create' and @disabled='disabled']"), '"Parameters" field is enabled.');
    $this->assertNotEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_before' and @disabled='disabled']"), '"Code snippet (before)" is disabled.');
    $this->assertNotEmpty($this->xpath("//textarea[@name='google_analytics_codesnippet_after' and @disabled='disabled']"), '"Code snippet (after)" is disabled.');
  }

  /**
   * Tests if help sections are shown.
   */
  public function testGoogleAnalyticsHelp() {
    // Requires help and block module and help block placement.
    $this->drupalGet('admin/config/services/google-analytics');
    $this->assertSession()->pageTextContains('Google Analytics is a free (registration required) website traffic and marketing effectiveness service.');

    // Requires help.module.
    $this->drupalGet('admin/help/google_analytics');
    $this->assertSession()->pageTextContains('Google Analytics adds a web statistics tracking system to your website.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testGoogleAnalyticsPageVisibility() {
    // Verify that no tracking code is embedded into the webpage; if there is
    // only the module installed, but UA code not configured. See #2246991.
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('https://www.googletagmanager.com/gtag/js?id=');

    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Show tracking on "every page except the listed pages".
    $this->config('google_analytics.settings')->set('visibility.request_path_mode', 0)->save();
    // Disable tracking on "admin*" pages only.
    $this->config('google_analytics.settings')->set('visibility.request_path_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains('gtag("config", "' . $ua_code . '"');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertSession()->responseNotContains($ua_code);
    $this->drupalGet('admin/config/services/google-analytics');
    // Checking for tracking URI here, as $ua_code is displayed in the form.
    $this->assertSession()->responseNotContains('https://www.googletagmanager.com/gtag/js?id=');

    // Test whether tracking code display is properly flipped.
    $this->config('google_analytics.settings')->set('visibility.request_path_mode', 1)->save();
    $this->drupalGet('admin');
    $this->assertSession()->responseContains($ua_code);
    $this->drupalGet('admin/config/services/google-analytics');
    // Checking for tracking URI here, as $ua_code is displayed in the form.
    $this->assertSession()->responseContains('https://www.googletagmanager.com/gtag/js?id=');
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($ua_code);

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($ua_code);

    // Switch back to every page except the listed pages.
    $this->config('google_analytics.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [])->save();
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testGoogleAnalyticsTrackingCode() {
    $ua_code = 'UA-123456-2';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Show tracking code on every page except the listed pages.
    $this->config('google_analytics.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('google_analytics.settings')->set('visibility.user_role_roles', [])->save();
    // Disable Anonymous Tracking since its enabled by default.
    $this->config('google_analytics.settings')->set('privacy.anonymizeip', 0)->save();

    /* Sample JS code as added to page:
    <script type="text/javascript" src="/sites/all/modules/google_analytics/google_analytics.js?w"></script>
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-123456-7"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments)};
    gtag('js', new Date());
    gtag('config', 'UA-123456-7');
    </script>
     */

    // Test whether tracking code uses latest JS.
    $this->config('google_analytics.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('<script async src="https://www.googletagmanager.com/gtag/js?id=' . $ua_code . '"></script>');
    $this->assertSession()->responseContains('window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments)};gtag("js", new Date());');
    $this->assertSession()->responseContains('gtag("config", ' . Json::encode($ua_code));

    // Enable anonymizing of IP addresses.
    $this->config('google_analytics.settings')->set('privacy.anonymizeip', 1)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"anonymize_ip":true');

    // Test whether anonymize visitors IP address feature has been enabled.
    $this->config('google_analytics.settings')->set('privacy.anonymizeip', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('"anonymize_ip":true');

    // Test if track Enhanced Link Attribution is enabled.
    $this->config('google_analytics.settings')->set('track.linkid', 1)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"link_attribution":true');

    // Test if track Enhanced Link Attribution is disabled.
    $this->config('google_analytics.settings')->set('track.linkid', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('"link_attribution":true');

    // Test if track display features is disabled.
    $this->config('google_analytics.settings')->set('track.displayfeatures', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"allow_ad_personalization_signals":false');

    // Test if track display features is enabled.
    $this->config('google_analytics.settings')->set('track.displayfeatures', 1)->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('"allow_ad_personalization_signals":false');

    // Test if tracking of url fragments is enabled.
    $this->config('google_analytics.settings')->set('track.urlfragments', 1)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"page_path":location.pathname + location.search + location.hash');

    // Test if tracking of url fragments is disabled.
    $this->config('google_analytics.settings')->set('track.urlfragments', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('"page_path":location.pathname + location.search + location.hash');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertSession()->responseContains('"groups":"default"');

    // Enable "One domain with multiple subdomains".
    $this->config('google_analytics.settings')->set('domain_mode', 1)->save();
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    // TODO: Workaround to run tests successfully. This feature cannot tested
    // reliable.
    global $cookie_domain;
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $this->assertSession()->responseContains('"cookie_domain":"' . $cookie_domain . '"');
    }
    else {
      // Special cases, Localhost and IP addresses don't show 'cookieDomain'.
      $this->assertSession()->responseNotContains('"cookie_domain":"' . $cookie_domain . '"');
    }

    // Enable "Multiple top-level domains" tracking.
    $this->config('google_analytics.settings')
      ->set('domain_mode', 2)
      ->set('cross_domains', "www.example.com\nwww.example.net")
      ->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"groups":"default","linker":');
    $this->assertSession()->responseContains('"groups":"default","linker":{"domains":["www.example.com","www.example.net"]}');
    $this->assertSession()->responseContains('"trackDomainMode":2,');
    $this->assertSession()->responseContains('"trackCrossDomains":["www.example.com","www.example.net"]');
    $this->config('google_analytics.settings')->set('domain_mode', 0)->save();

    // Test whether debugging script has been enabled.
    $this->config('google_analytics.settings')->set('debug', 1)->save();
    $this->drupalGet('');
    // @FIXME
    //$this->assertSession()->responseContains('https://www.google-analytics.com/analytics_debug.js');

    // Check if text and link is shown on 'Status Reports' page.
    // Requires 'administer site configuration' permission.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->responseContains($this->t('Google Analytics module has debugging enabled. Please disable debugging setting in production sites from the <a href=":url">Google Analytics settings page</a>.', [':url' => Url::fromRoute('google_analytics.admin_settings_form')->toString()]));

    // Test whether debugging script has been disabled.
    $this->config('google_analytics.settings')->set('debug', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('https://www.googletagmanager.com/gtag/js?id=');

    // Test whether the CREATE and BEFORE and AFTER code is added to the
    // tracking code.
    $codesnippet_parameters = [
      'cookie_domain' => 'foo.example.com',
      'cookie_name' => 'myNewName',
      'cookie_expires' => "20000",
      'sample_rate' => "4.3",
    ];
    $this->config('google_analytics.settings')
      ->set('codesnippet.create', $codesnippet_parameters)
      ->set('codesnippet.before', 'gtag("set", {"currency":"USD"});')
      ->set('codesnippet.after', 'gtag("config", "UA-123456-3", {"groups":"default"});if(1 == 1 && 2 < 3 && 2 > 1){console.log("Google Analytics: Custom condition works.");}')
      ->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('"groups":"default","cookie_domain":"foo.example.com","cookie_name":"myNewName","cookie_expires":"20000","sample_rate":"4.3"');
    $this->assertSession()->responseContains('gtag("set", {"currency":"USD"});');
    $this->assertSession()->responseContains('gtag("config", "UA-123456-3", {"groups":"default"});');
    $this->assertSession()->responseContains('if(1 == 1 && 2 < 3 && 2 > 1){console.log("Google Analytics: Custom condition works.");}');
  }

}
