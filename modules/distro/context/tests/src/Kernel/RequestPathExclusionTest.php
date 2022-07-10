<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Tests\Routing\MockAliasManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the request path exclusion condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class RequestPathExclusionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'path', 'field', 'context'];

  /**
   * The condition plugin manager used for testing.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The path alias manager used for testing.
   *
   * @var \Drupal\system\Tests\Routing\MockAliasManager
   */
  protected $aliasManager;

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

    $this->installSchema('system', ['sequences']);

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
   * Tests the condition against different patterns and requests.
   */
  public function testRequestPathExclusion() {
    $pages = "/my/exclude/page\r\n/my/exclude/page2\r\n/excludefoo";
    $request = Request::create('/my/exclude/page2');
    $this->requestStack->push($request);

    // Test a standard path.
    /** @var \Drupal\context\Plugin\Condition\RequestPathExclusion $condition */
    $condition = $this->pluginManager->createInstance('request_path_exclusion');
    $condition->setConfig('pages', $pages);
    $this->aliasManager->addAlias('/my/exclude/page2', '/my/exclude/page2');
    $this->assertFalse($condition->execute(), 'The request path matches a standard path');
    $this->assertEquals('Do not return true on the following pages: /my/exclude/page, /my/exclude/page2, /excludefoo', $condition->summary(), 'The condition summary matches for a standard path');

    // Test an aliased path.
    $this->currentPath->setPath('/my/aliased/page', $request);
    $this->requestStack->pop();
    $this->requestStack->push($request);
    $this->aliasManager->addAlias('/my/aliased/page', '/my/exclude/page');
    $this->assertFalse($condition->execute(), 'The request path matches an aliased path');
    $this->assertEquals('Do not return true on the following pages: /my/exclude/page, /my/exclude/page2, /excludefoo', $condition->summary(), 'The condition summary matches for an aliased path');

    // Test a wildcard path.
    $this->aliasManager->addAlias('/my/exclude/page3', '/my/exclude/page3');
    $this->currentPath->setPath('/my/exclude/page3', $request);
    $this->requestStack->pop();
    $this->requestStack->push($request);
    $condition->setConfig('pages', '/my/exclude/*');
    $this->assertTrue($condition->evaluate(), 'The exclude_path my/exclude/page3 passes for wildcard paths.');
    $this->assertEquals('Do not return true on the following pages: /my/exclude/*', $condition->summary(), 'The condition summary matches for a wildcard path');

    // Test a missing path.
    $this->requestStack->pop();
    $this->requestStack->push($request);
    $this->currentPath->setPath('/my/fail/page4', $request);
    $condition->setConfig('pages', '/my/exclude/*');
    $this->aliasManager->addAlias('/my/fail/page4', '/my/fail/page4');
    $this->assertFalse($condition->evaluate(), 'The request_path /my/pass/page4 fails for a missing path.');

    // Test a path of '/'.
    $this->aliasManager->addAlias('/', '/my/exclude/page3');
    $this->currentPath->setPath('/', $request);
    $this->requestStack->pop();
    $this->requestStack->push($request);
    $this->assertTrue($condition->evaluate(), 'The request_path my/exclude/page3 passes for wildcard paths.');
    $this->assertEquals($condition->summary(), 'Do not return true on the following pages: /my/exclude/*', 'The condition summary matches for a wildcard path');
  }

}
