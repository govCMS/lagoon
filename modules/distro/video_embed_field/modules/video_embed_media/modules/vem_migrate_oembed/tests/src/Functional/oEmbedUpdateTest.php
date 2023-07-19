<?php

namespace Drupal\Tests\vem_embed_media\Functional;

use Drupal\media\Entity\MediaType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the VEM to OEmbed migration.
 *
 * @group vem_embed_media
 */
class oEmbedUpdateTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['vem_migrate_oembed'];

  /**
   * Tests the VEM to OEmbed migration.
   */
  public function testOEmbedUpdate() {

    $mediaType = $this->createMediaType('video_embed_field');
    $this->assertEquals($mediaType->getSource()->getPluginId(), 'video_embed_field');

    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $this->assertEquals($sourceField->getType(), 'video_embed_field');

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = $this->container->get('entity_display.repository');

    $formDisplay = $display_repository->getFormDisplay('media', $mediaType->id());
    $formField = $formDisplay->getComponent($sourceField->getName());

    $this->assertEquals($formField['type'], 'video_embed_field_textfield');

    /** @var \Drupal\vem_migrate_oembed\VemMigrate $vemService */
    $vemService = \Drupal::service('vem_migrate_oembed.migrate');
    $vemService->migrate();

    /** @var \Drupal\media\Entity\MediaType $mediaType */
    $mediaType = MediaType::load($mediaType->id());
    $this->assertEquals($mediaType->getSource()->getPluginId(), 'oembed:video');

    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $this->assertEquals($sourceField->getType(), 'string');

    $formDisplay = $display_repository->getFormDisplay('media', $mediaType->id());
    $formField = $formDisplay->getComponent($sourceField->getName());

    $this->assertEquals($formField['type'], 'oembed_textfield');
  }

}
