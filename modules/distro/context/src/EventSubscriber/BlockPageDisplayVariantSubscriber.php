<?php

namespace Drupal\context\EventSubscriber;

use Drupal\context\ContextManager;
use Drupal\Core\Render\RenderEvents;
use Drupal\context\Plugin\ContextReaction\Blocks;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the block page display variant.
 *
 * @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 */
class BlockPageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * The Context module context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  private $contextManager;

  /**
   * Construct a block page display variant.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context manager.
   */
  public function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
  }

  /**
   * Selects the context block page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    // Activate the context block page display variant if any of the reactions
    // is a blocks reaction.
    foreach ($this->contextManager->getActiveReactions() as $reaction) {
      if ($reaction instanceof Blocks) {
        $event->setPluginId('context_block_page');
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
