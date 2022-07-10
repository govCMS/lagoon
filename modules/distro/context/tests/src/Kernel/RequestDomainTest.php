<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the request domain condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class RequestDomainTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['context'];

  /**
   * The condition plugin manager used for testing.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.condition');
    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);
    $this->currentPath = new CurrentPathStack($this->requestStack);
    $this->container->set('path.current', $this->currentPath);
    $this->container->set('current_route_match', new CurrentRouteMatch($this->requestStack));
  }

  /**
   * Tests the request domain condition against a path/route.
   */
  public function testRequestDomain() {
    $request = Request::create('/');
    $this->requestStack->push($request);
    $domain = $request->getHost();

    /** @var \Drupal\context\Plugin\Condition\RequestDomain $condition */
    $condition = $this->pluginManager->createInstance('request_domain');
    $condition->setConfig('domains', $domain);
    $this->assertTrue($condition->execute(), 'Domains match');
    $this->assertEquals($condition->summary(), 'Return true on the following domains: ' . $domain, 'Domains match.');

    $condition->setConfig('domains', 'example.com');
    $this->assertFalse($condition->execute(), 'Domains do not match');

  }

  /**
   * Tests the negated request domain condition against a path/route.
   */
  public function testRequestDomainNegate() {
    $request = Request::create('/');
    $this->requestStack->push($request);
    $domain = $request->getHost();

    /** @var \Drupal\context\Plugin\Condition\RequestDomain $condition */
    $condition = $this->pluginManager->createInstance('request_domain');
    $condition->setConfig('negate', 1);

    $condition->setConfig('domains', $domain);
    $this->assertFalse($condition->execute(), 'Domains do not match');
    $this->assertEquals($condition->summary(), 'Do not return true on the following domains: ' . $domain, 'Domains match.');

    $condition->setConfig('domains', 'example.com');
    $this->assertTrue($condition->execute(), 'Domains match');

  }

}
