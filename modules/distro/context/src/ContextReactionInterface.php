<?php

namespace Drupal\context;

use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for ContextReaction.
 */
interface ContextReactionInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface, ExecutableInterface {

  /**
   * Get the unique ID of this context reaction.
   *
   * @return string|null
   *   The Reaction id or null if reaction was not found.
   */
  public function getId();

  /**
   * Provides a human readable summary of the condition's configuration.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   */
  public function summary();

}
