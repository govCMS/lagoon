<?php

namespace Drupal\Tests\entity_embed\Kernel;

/**
 * Tests that entity embed disables certain integrations.
 *
 * @coversDefaultClass \Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter
 * @group entity_embed
 */
class EntityEmbedFilterDisabledIntegrationsTest extends EntityEmbedFilterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->container->get('current_user')
      ->addRole($this->drupalCreateRole([
        'access contextual links',
        'access in-place editing',
      ]));
  }

  /**
   * @covers \Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay\EntityReferenceFieldFormatter::disableContextualLinks
   * @covers \Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay\EntityReferenceFieldFormatter::disableQuickEdit
   * @dataProvider providerDisabledIntegrations
   */
  public function testDisabledIntegrations($integration_detection_selector) {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'node',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
      'data-view-mode' => 'teaser',
    ]);

    $this->applyFilter($text);
    $this->assertCount(0, $this->cssSelect($integration_detection_selector));
  }

  /**
   * Data provider for testDisabledIntegrations().
   */
  public function providerDisabledIntegrations() {
    return [
      'contextual' => [
        'div.embedded-entity > .contextual-region',
      ],
      'quickedit' => [
        'div.embedded-entity > [data-quickedit-entity-id]',
      ],
    ];
  }

}
