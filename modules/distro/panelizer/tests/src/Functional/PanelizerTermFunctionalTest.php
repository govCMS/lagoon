<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Basic functional tests of using Panelizer with taxonomy terms.
 *
 * @group panelizer
 */
class PanelizerTermFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'field',
    'field_ui',
    'taxonomy',
    'user',

    // Core dependencies.
    'layout_discovery',

    // Contrib dependencies.
    'ctools',
    'panels',
    'panels_ipe',

    // This module.
    'panelizer',
    'panelizer_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    $user = $this->drupalCreateUser([
      'administer taxonomy',
      'administer taxonomy_term display',
      'edit terms in tags',
      'administer panelizer',
      'access panels in-place editing',
      'administer taxonomy_term fields',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/taxonomy/manage/tags/overview/display');
    $edit = [
      'panelizer[enable]' => TRUE,
      'panelizer[custom]' => TRUE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->rebuildAll();
  }

  /**
   * Tests rendering a taxonomy term with Panelizer default.
   */
  public function testPanelizerDefault() {
    /** @var \Drupal\panelizer\PanelizerInterface $panelizer */
    $panelizer = \Drupal::service('panelizer');
    $displays = $panelizer->getDefaultPanelsDisplays('taxonomy_term', 'tags', 'default');
    $display = $displays['default'];
    $display->addBlock([
      'id' => 'panelizer_test',
      'label' => 'Panelizer test',
      'provider' => 'block_content',
      'region' => 'content',
    ]);
    $panelizer->setDefaultPanelsDisplay('default', 'taxonomy_term', 'tags', 'default', $display);

    // Create a term, and check that the IPE is visible on it.
    $term = $this->createTerm();

    $out = $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertSession()->statusCodeEquals(200);
    dump($out);
    $elements = $this->xpath('//*[@id="panels-ipe-content"]');
    if (is_array($elements)) {
      $this->assertSame(count($elements), 1);
    }
    else {
      $this->fail('Could not parse page content.');
    }

    // Check that the block we added is visible.
    $this->assertSession()->pageTextContains('Panelizer test');
    $this->assertSession()->pageTextContains('Abracadabra');
  }

  /**
   * Create a term.
   *
   * @return Term;
   */
  protected function createTerm() {
    $settings = [
      'description' => [['value' => $this->randomMachineName(32)]],
      'name' => $this->randomMachineName(8),
      'vid' => 'tags',
      'uid' => \Drupal::currentUser()->id(),
    ];
    $term = Term::create($settings);
    $term->save();
    return $term;
  }

}
