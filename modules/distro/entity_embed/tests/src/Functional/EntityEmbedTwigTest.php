<?php

namespace Drupal\Tests\entity_embed\Functional;

use Drupal\entity_embed\Twig\EntityEmbedTwigExtension;

/**
 * Tests Twig extension provided by entity_embed.
 *
 * @group entity_embed
 */
class EntityEmbedTwigTest extends EntityEmbedTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme']);
  }

  /**
   * Tests that the provided Twig extension loads the service appropriately.
   */
  public function testTwigExtensionLoaded() {
    $ext = $this->container->get('twig')->getExtension(EntityEmbedTwigExtension::class);
    $this->assertNotEmpty($ext);
    $this->assertInstanceOf(EntityEmbedTwigExtension::class, $ext, 'Extension loaded successfully.');
  }

  /**
   * Tests that the Twig extension's filter produces expected output.
   */
  public function testEntityEmbedTwigFunction() {
    // Test embedding a node using entity ID.
    $this->drupalGet('entity_embed_twig_test/id');
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextContains() for HTML responses, responseContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains($this->node->body->value);

    // Test 'Label' Entity Embed Display plugin.
    $this->drupalGet('entity_embed_twig_test/label_plugin');
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextContains() for HTML responses, responseContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains($this->node->title->value);
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextNotContains() for HTML responses, responseNotContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextNotContains($this->node->body->value);
    $this->assertSession()->linkByHrefExists('node/' . $this->node->id(), 0, 'Link to the embedded node exists when "Label" plugin is used.');

    // Test 'Label' Entity Embed Display plugin without linking to the node.
    $this->drupalGet('entity_embed_twig_test/label_plugin_no_link');
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextContains() for HTML responses, responseContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains($this->node->title->value);
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextNotContains() for HTML responses, responseNotContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextNotContains($this->node->body->value);
    $this->assertSession()->linkByHrefNotExists('node/' . $this->node->id(), 0, 'Link to the embedded node does not exists.');
  }

}
