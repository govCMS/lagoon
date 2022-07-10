<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests the view inclusion condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class ViewInclusionTest extends KernelTestBase {

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
   * Tests the view inclusion condition against a view path/route.
   */
  public function testViewInclusion() {
    $request = Request::create('');
    $request->attributes->set('_route', 'view-frontpage-page_1');
    $request->attributes->set('_route_object', new Route('/node/'));
    $this->requestStack->push($request);

    /** @var \Drupal\context\Plugin\Condition\ViewInclusion $condition */
    $condition = $this->pluginManager->createInstance('view_inclusion');
    $condition->setConfig('view_inclusion', ['view-frontpage-page_1' => 'view-frontpage-page_1']);
    $this->assertTrue($condition->execute(), 'The path does not match');

    $condition->setConfig('view_inclusion', ['view-user_admin_people-page_1' => 'view-user_admin_people-page_1']);
    $this->assertFalse($condition->execute(), 'The path does match');
  }

}
