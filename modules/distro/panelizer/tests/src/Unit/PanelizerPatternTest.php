<?php

namespace Drupal\Tests\panelizer\Unit;

use Drupal\ctools\ContextMapperInterface;
use Drupal\panelizer\Plugin\PanelsPattern\PanelizerPattern;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\panelizer\Plugin\PanelsPattern\PanelizerPattern
 * @group panelizer
 */
class PanelizerPatternTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructor() {
    new PanelizerPattern([], 'panelizer', [], $this->createMock(ContextMapperInterface::class));
    $this->assertTrue(TRUE, 'PanelizerPattern was successfully instantiated.');
  }

}
