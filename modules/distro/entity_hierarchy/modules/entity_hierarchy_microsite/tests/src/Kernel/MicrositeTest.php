<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Kernel;

use Drupal\entity_hierarchy_microsite\Entity\Microsite;

/**
 * Defines a class for testing the microsite entity.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeTest extends EntityHierarchyMicrositeKernelTestBase {

  /**
   * Tests the microsite entity.
   */
  public function testMicrositeEntity() {
    $media = $this->createImageMedia();
    $microsite = Microsite::create([
      'name' => 'Subsite',
      'home' => $this->parent,
      'logo' => $media,
    ]);
    $microsite->save();
    $this->assertEquals('Subsite', $microsite->label());
    $this->assertEquals($this->parent->id(), $microsite->getHome()->id());
    $this->assertEquals($media->id(), $microsite->getLogo()->id());
  }

}
