<?php

namespace Drupal\diff;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Builds a diff layout.
 */
interface DiffLayoutInterface extends PluginFormInterface, ConfigurableInterface {

  /**
   * Builds a diff comparison between two revisions.
   *
   * This method is responsible for building the diff comparison between
   * revisions of the same entity. It can build a table, navigation links and
   * headers of a diff comparison.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $left_revision
   *   The left revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right_revision
   *   The right revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The modified build array that the plugin builds.
   */
  public function build(ContentEntityInterface $left_revision, ContentEntityInterface $right_revision, ContentEntityInterface $entity);

}
