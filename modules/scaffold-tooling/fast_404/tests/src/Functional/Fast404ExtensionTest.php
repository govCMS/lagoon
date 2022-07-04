<?php

namespace Drupal\Tests\fast404\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the extension checking functionality.
 *
 * @group fast404
 */
class Fast404ExtensionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['fast404'];

  /**
   * Tests the extension checking functionality.
   */
  public function testExtensionCheck() {
    // Ensure extension check for .doc isn't activated by default.
    $this->drupalGet('/unknowfile.doc');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('The requested page could not be found.');

    // Ensure robots.txt is not blocked by default settings.
    $this->drupalGet('/robots.txt');
    $this->assertSession()->statusCodeEquals(200);

    \Drupal::service('cache.page')->deleteAll();

    // Add .doc to the default extension list.
    $settings['settings']['fast404_exts'] = (object) [
      'value' => '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp|doc)$/i',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('/unknowfile.doc');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('Not Found');
    $this->assertSession()->responseContains('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "/unknowfile.doc" was not found on this server.</p></body></html>');
  }

}
