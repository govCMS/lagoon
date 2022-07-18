<?php

namespace Drupal\layout_builder_modal\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Provides an event subscriber that alters Ajax Responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Close modal dialog if Layout Builder is re-rendered.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  public function onResponse(\Symfony\Component\HttpKernel\Event\ResponseEvent $event) {
    $response = $event->getResponse();

    if ($response instanceof AjaxResponse) {
      $should_close_dialog = FALSE;

      $commands = &$response->getCommands();

      foreach ($commands as $command) {
        if (isset($command['selector']) && $command['selector'] === '#layout-builder') {
          $should_close_dialog = TRUE;
          break;
        }
      }

      if ($should_close_dialog) {
        $response->addCommand(new CloseDialogCommand('#layout-builder-modal'));
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }

}
