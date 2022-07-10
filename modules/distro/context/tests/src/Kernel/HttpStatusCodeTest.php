<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Tests\Routing\MockAliasManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests the http status code condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class HttpStatusCodeTest extends KernelTestBase {

  /**
   * The condition plugin manager used for testing.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The path alias manager used for testing.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['context'];

  /**
   * Current path stack.
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

    // Set a mock alias manager in the container.
    $this->aliasManager = new MockAliasManager();
    $this->container->set('path_alias.manager', $this->aliasManager);

    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);

    $this->currentPath = new CurrentPathStack($this->requestStack);
    $this->container->set('path.current', $this->currentPath);
  }

  /**
   * Tests the http status code condition.
   */
  public function testHttpStatusCodes() {
    // Get the http status code condition and configure it to check against
    // different code statuses.
    $statusCodes = [200 => '200', 403 => '403'];
    $request = Request::create('/my/status/page');
    $request->attributes->set('exception', new HttpException(403));
    $request->attributes->set('exception', new HttpException(200));
    $this->requestStack->push($request);

    /** @var \Drupal\system\Plugin\Condition\RequestPath $condition * */
    $condition = $this->pluginManager->createInstance('http_status_code');
    $condition->setConfig('status_codes', $statusCodes);

    $this->aliasManager->addAlias('/my/status/page', '/my/status/page');

    $this->assertTrue($condition->execute(), 'Status codes are not matching');
    $this->assertEquals('The http status code is 200 or 403', $condition->summary(), 'The http status codes do not match');

    $request->attributes->set('exception', new HttpException(403));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [403 => '403']);
    $this->assertTrue($condition->execute(), 'Status code is not 403');
    $this->assertEquals('The http status code is 403', $condition->summary(), 'The http status code does not match');

    $request->attributes->set('exception', new HttpException(200));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [200 => '200']);
    $this->assertTrue($condition->execute(), 'Status code is not 200');
    $this->assertEquals('The http status code is 200', $condition->summary(), 'The http status code does not match');

    // Exception is empty, so default status code should be 200.
    $this->requestStack->push($request);
    $this->assertTrue($condition->execute(), 'Status code is not 200');

    // Exception is not a HttpException, so default status code should be 200.
    $request->attributes->set('exception', new \Exception(403));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [403 => '403']);
    $this->assertFalse($condition->execute(), 'Status code is not 200');
  }

  /**
   * Tests the http status code condition that is negated.
   */
  public function testHttpStatusCodeNegate() {
    // Get the http status code condition and configure it to check against
    // different code statuses with the negate option enabled.
    $statusCodes = [200 => '200', 403 => '403'];
    $request = Request::create('/my/status/page');
    $request->attributes->set('exception', new HttpException(403));
    $request->attributes->set('exception', new HttpException(200));
    $this->requestStack->push($request);

    /** @var \Drupal\system\Plugin\Condition\RequestPath $condition * */
    $condition = $this->pluginManager->createInstance('http_status_code');
    $condition->setConfig('status_codes', $statusCodes);
    $condition->setConfig('negate', 1);

    $this->aliasManager->addAlias('/my/status/page', '/my/status/page');

    $this->assertFalse($condition->execute(), 'Status codes are not matching');
    $this->assertEquals('The http status code is not 200 or 403', $condition->summary(), 'The http status codes do not match');

    $request->attributes->set('exception', new HttpException(403));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [403 => '403']);
    $this->assertFalse($condition->execute(), 'Status code is not 403');
    $this->assertEquals('The http status code is not 403', $condition->summary(), 'The http status code does not match');

    $request->attributes->set('exception', new HttpException(200));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [200 => '200']);
    $this->assertFalse($condition->execute(), 'Status code is not 200');
    $this->assertEquals('The http status code is not 200', $condition->summary(), 'The http status code does not match');

    // Exception is empty, so default status code should be 200.
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Status code is not 200');

    // Exception is not a HttpException, so default status code should be 200.
    $request->attributes->set('exception', new \Exception(403));
    $this->requestStack->push($request);
    $condition->setConfig('status_codes', [403 => '403']);
    $this->assertTrue($condition->execute(), 'Status code is 403');
  }

}
