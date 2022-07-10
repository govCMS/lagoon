<?php

namespace Drupal\context\Theme;

use Drupal\context\ContextManager;
use Drupal\context\Plugin\ContextReaction\Theme;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Context Theme Switcher Negotiator.
 */
class ThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  /**
   * ContextManager.
   *
   * @var \Drupal\context\ContextManager
   */
  private $contextManager;

  /**
   * Theme machine name.
   *
   * @var string
   */
  protected $theme;

  /**
   * A boolean indicating if the applies method has already been evaluated.
   *
   * @var bool
   */
  protected $evaluated;

  /**
   * Service constructor.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   ContextManager.
   */
  public function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
    $this->evaluated = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // If there is no Theme reaction set or this method has already been
    // executed, do not try to get active reactions, since this causes infinite
    // loop.
    if ($this->evaluated) {
      $this->evaluated = FALSE;
      return FALSE;
    }
    $theme_reaction = FALSE;
    foreach ($this->contextManager->getContexts() as $context) {
      foreach ($context->getReactions() as $reaction) {
        if ($reaction instanceof Theme) {
          $theme_reaction = TRUE;
          break;
        }
      }
    }

    if ($theme_reaction) {
      $this->evaluated = TRUE;
      foreach ($this->contextManager->getActiveReactions('theme') as $theme_reaction) {
        $configuration = $theme_reaction->getConfiguration();
        // Be sure the theme key really exists.
        if (isset($configuration['theme'])) {
          switch ($configuration['theme']) {
            case '_admin':
              $this->theme = \Drupal::config('system.theme')->get('admin');
              return TRUE;

            case '_default':
              $this->theme = \Drupal::config('system.theme')->get('default');
              return TRUE;

            default:
              $this->theme = $configuration['theme'];
              return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

}
