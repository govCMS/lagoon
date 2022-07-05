<?php

namespace Drupal\seckit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example page controller.
 */
class SeckitExportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an SeckitExportController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   LoggerInterface.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.seckit')
    );
  }

  /**
   * Reports CSP violations.
   */
  public function export() {
    // Only allow POST data with Content-Type application/csp-report
    // or application/json (the latter to support older user agents).
    // n.b. The CSP spec (1.0, 1.1) mandates this Content-Type header/value.
    // n.b. Content-Length is optional, so we don't check it.
    // @TODO replace with custom access checker?
    if (empty($_SERVER['CONTENT_TYPE']) || empty($_SERVER['REQUEST_METHOD'])) {
      throw new NotFoundHttpException();
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      throw new NotFoundHttpException();
    }
    $pattern = '~^application/(csp-report|json)\h*(;|$)~';
    if (!preg_match($pattern, $_SERVER['CONTENT_TYPE'])) {
      throw new NotFoundHttpException();
    }

    // Get and parse report.
    $reports = file_get_contents('php://input');
    $reports = json_decode($reports);
    if (!is_object($reports)) {
      throw new NotFoundHttpException();
    }

    // Log the report data.
    foreach ($reports as $report) {
      if (!isset($report->{'violated-directive'})) {
        continue;
      }
      $info = [
        '@directive' => $report->{'violated-directive'},
        '@blocked_uri' => $report->{'blocked-uri'},
        '@data' => print_r($report, TRUE),
      ];
      $this->logger->warning('CSP: Directive @directive violated.<br /> Blocked URI: @blocked_uri.<br /> <pre>Data: @data</pre>', $info);
    }

    return new Response();
  }

}
