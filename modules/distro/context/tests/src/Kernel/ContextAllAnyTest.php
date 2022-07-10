<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Tests\Routing\MockAliasManager;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests the context all condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class ContextAllAnyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'context',
    'context_all_any_test',
  ];

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

    // Set a mock alias manager in the container.
    $this->aliasManager = new MockAliasManager();
    $this->container->set('path_alias.manager', $this->aliasManager);
    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);
    $this->currentPath = new CurrentPathStack($this->requestStack);
    $this->container->set('path.current', $this->currentPath);
    $this->container->set('current_route_match', new CurrentRouteMatch($this->requestStack));

    // Set test module configuration.
    $this->installConfig('context_all_any_test');

  }

  /**
   * Tests the context all condition against a path/route.
   */
  public function testContextAll() {

    $request = Request::create('/node/22');
    $this->requestStack->push($request);
    /** @var \Drupal\context\Plugin\Condition\RequestDomain $condition */
    $condition = $this->pluginManager->createInstance('context_all');

    $this->aliasManager->addAlias('/node/22', '/node/22');

    // Test if condition is true for one context.
    $condition->setConfig('values', 'another_context');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: another_context', 'Context applied.');

    $condition->setConfig('values', 'test');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: test', 'Context applied.');

    // Test if condition is true for two contexts.
    $condition->setConfig('values', "another_context\r\ntest");
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: another_context, test', 'Context applied.');

    // Test if condition is true for one context with an asterisks.
    $condition->setConfig('values', 'ano*');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: ano*', 'Context applied.');

    $condition->setConfig('values', 'te*');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: te*', 'Context applied.');

    // Test that condition is false on specific route for one context and true
    // for the other.
    $request = Request::create('/user/2');
    $request->attributes->set('_route', 'entity.user.canonical');
    $request->request->set('user', 2);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 2]));
    $request->attributes->set('_route_object', new Route('/user/{user}'));
    $this->requestStack->push($request);
    $condition->setConfig('values', 'another_context');
    $this->aliasManager->addAlias('/user/2', '/user/2');
    $this->assertFalse($condition->evaluate(), 'All contexts are not active');

    $condition->setConfig('values', 'another_co*');
    $this->assertFalse($condition->evaluate(), 'All contexts are not active');

    $condition->setConfig('values', "another_context\r\ntest");
    $this->assertFalse($condition->evaluate(), 'All contexts are not active');

    $condition->setConfig('values', 'test');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: test', 'Context applied.');

    $condition->setConfig('values', 'te*');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: te*', 'Context applied.');

    // Test that condition is true with the tilde.
    $condition->setConfig('values', "~another_context\r\ntest");
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: ~another_context, test', 'Context applied.');

  }

  /**
   * Tests the context any condition against a path/route.
   */
  public function testContextAny() {
    $request = Request::create('/node/2');
    $this->requestStack->push($request);
    /** @var \Drupal\context\Plugin\Condition\RequestDomain $condition */
    $condition = $this->pluginManager->createInstance('context');

    $this->aliasManager->addAlias('/node/2', '/node/2');

    // Test if condition is true for one context.
    $condition->setConfig('values', 'another_context');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: another_context', 'Context applied.');

    $condition->setConfig('values', 'test');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: test', 'Context applied.');

    // Test if condition is true for two contexts.
    $condition->setConfig('values', "another_context\r\ntest");
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: another_context, test', 'Context applied.');

    // Test if condition is true for one context with an asterisks.
    $condition->setConfig('values', 'anoth*');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: anoth*', 'Context applied.');

    $condition->setConfig('values', 'tes*');
    $this->assertTrue($condition->evaluate(), 'All contexts are active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: tes*', 'Context applied.');

    // Test that condition is false on specific route for one context and true
    // for the other.
    $request = Request::create('/user/3');
    $request->attributes->set('_route', 'entity.user.canonical');
    $request->request->set('user', 3);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 3]));
    $request->attributes->set('_route_object', new Route('/user/{user}'));
    $this->requestStack->push($request);
    $condition->setConfig('values', 'another_context');
    $this->aliasManager->addAlias('/user/3', '/user/3');
    $this->assertFalse($condition->evaluate(), 'Any context is not active');

    $condition->setConfig('values', 'another_co*');
    $this->assertFalse($condition->evaluate(), 'Any context is not active');

    $condition->setConfig('values', "another_context\r\ntest");
    $this->assertTrue($condition->evaluate(), 'Any context is active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: another_context, test', 'Context applied.');

    $condition->setConfig('values', 'test');
    $this->assertTrue($condition->evaluate(), 'Any context is active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: test', 'Context applied.');

    $condition->setConfig('values', 'tes*');
    $this->assertTrue($condition->evaluate(), 'Any context is active');
    $this->assertEquals($condition->summary(), 'Return true on the basis of other active contexts: tes*', 'Context applied.');

    // Test that condition is false with the tilde.
    $condition->setConfig('values', "another_context\r\n~test");
    $this->assertFalse($condition->evaluate(), 'Any context is not active');

  }

}
