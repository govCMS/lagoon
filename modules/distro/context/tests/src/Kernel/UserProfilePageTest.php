<?php

namespace Drupal\Tests\context\Kernel;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests the user profile page condition plugin.
 *
 * @package Drupal\Tests\context\Kernel
 *
 * @group context
 */
class UserProfilePageTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['context', 'system', 'user'];

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

    $this->setUpCurrentUser(['uid' => 2], ['access user profiles']);
    $this->installEntitySchema('user');
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
  public function testUserProfilePage() {
    $request = Request::create('');
    $request->attributes->set('_route_object', new Route('/node/'));
    $this->requestStack->push($request);

    /** @var \Drupal\context\Plugin\Condition\UserProfilePage $condition */
    $condition = $this->pluginManager->createInstance('user_status');

    // Checks if viewing_profile is set to not work on nodes.
    $condition->setConfig('user_status', ['viewing_profile' => 'viewing_profile']);
    $condition->setConfig('user_fields', '');
    $this->assertFalse($condition->evaluate(), 'The user status field is not viewing_profile');

    // Checks if no condition are set to work on nodes.
    $condition->setConfig('user_status', '');
    $this->assertTrue($condition->evaluate(), 'The user status field is empty');

    // Checks if viewing_profile is set to work on user/id page.
    $request = Request::create('/user/2');
    $request->attributes->set('_route', 'entity.user.canonical');
    $request->request->set('user', 2);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 2]));
    $request->attributes->set('_route_object', new Route('/user/{user}'));
    $this->requestStack->push($request);
    $condition->setConfig('user_status', ['viewing_profile' => 'viewing_profile']);
    $this->assertTrue($condition->evaluate(), 'The user status field is viewing_profile');

    // Checks if own_page_true is set to work only on
    // currently logged in user/id page.
    $condition->setConfig('user_status', ['own_page_true' => 'own_page_true']);
    $this->assertTrue($condition->evaluate(), 'The user status field is own_page_true');

    // Checks if logged_viewing_profile is set to work on any user/id page.
    $this->setUpCurrentUser(['uid' => 3], ['access user profiles']);
    $condition->setConfig('user_status', ['logged_viewing_profile' => 'logged_viewing_profile']);
    $this->assertTrue($condition->evaluate(), 'The user status field is logged_viewing_profile');

    // Checks if field_value is set to work if uid is present/not empty.
    $condition->setConfig('user_status', ['field_value' => 'field_value']);
    $condition->setConfig('user_fields', 'uid');
    $this->assertTrue($condition->evaluate(), 'The user status field is uid');

    // Checks if field_value is set to work if roles are present/not empty.
    $request = Request::create('/user/3/edit');
    $request->attributes->set('_route', 'entity.user.edit_form');
    $request->request->set('user', 3);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 3]));
    $request->attributes->set('_route_object', new Route('/user/{user}/edit'));
    $this->requestStack->push($request);
    $condition->setConfig('user_fields', 'roles');
    $this->assertTrue($condition->evaluate(), 'The user status is field value and user field is roles');

  }

  /**
   * Tests the view inclusion condition against a view path/route.
   */
  public function testUserProfilePageNegate() {
    $request = Request::create('');
    $request->attributes->set('_route_object', new Route('/node/'));
    $this->requestStack->push($request);

    /** @var \Drupal\context\Plugin\Condition\UserProfilePage $condition */
    $condition = $this->pluginManager->createInstance('user_status');
    $condition->setConfig('negate', 1);

    // Checks if viewing_profile is set to work on nodes.
    $condition->setConfig('user_status', ['viewing_profile' => 'viewing_profile']);
    $condition->setConfig('user_fields', '');
    $this->assertTrue($condition->execute(), 'The user status field is viewing_profile');

    // Checks if no condition is set to work on nodes.
    $condition->setConfig('user_status', '');
    $this->assertFalse($condition->execute(), 'The user status field is empty');

    // Checks if viewing_profile is set to work on user/id page.
    $request = Request::create('/user/2');
    $request->attributes->set('_route', 'entity.user.canonical');
    $request->request->set('user', 2);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 2]));
    $request->attributes->set('_route_object', new Route('/user/{user}'));
    $this->requestStack->push($request);
    $condition->setConfig('user_status', ['viewing_profile' => 'viewing_profile']);
    $this->assertFalse($condition->execute(), 'The user status field is not viewing_profile');

    // Checks if own_page_true is set to work only on
    // currently logged in user/id page.
    $condition->setConfig('user_status', ['own_page_true' => 'own_page_true']);
    $this->assertFalse($condition->execute(), 'The user status field is not own_page_true');

    // Checks if logged_viewing_profile is set to work on any user/id page.
    $this->setUpCurrentUser(['uid' => 3], ['access user profiles']);
    $condition->setConfig('user_status', ['logged_viewing_profile' => 'logged_viewing_profile']);
    $this->assertFalse($condition->execute(), 'The user status field is not logged_viewing_profile');

    // Checks if field_value is set to work if no uid is present/not empty.
    $condition->setConfig('user_status', ['field_value' => 'field_value']);
    $condition->setConfig('user_fields', 'uid');
    $this->assertFalse($condition->execute(), 'The user status field is not uid');

    // Checks if field_value is set to work if no roles are present/not empty.
    $request = Request::create('/user/3/edit');
    $request->attributes->set('_route', 'entity.user.edit_form');
    $request->request->set('user', 3);
    $request->attributes->set('_raw_variables', new ParameterBag(['user' => 3]));
    $request->attributes->set('_route_object', new Route('/user/{user}/edit'));
    $this->requestStack->push($request);
    $condition->setConfig('user_status', ['field_value' => 'field_value']);
    $condition->setConfig('user_fields', 'roles');
    $this->assertFalse($condition->execute(), 'The user status is not field value and user field is not roles');

  }

}
