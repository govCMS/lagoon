<?php

namespace Drupal\Tests\entity_hierarchy_breadcrumb\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\Tests\entity_hierarchy\Kernel\EntityHierarchyKernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests for breadcrumbs built of entity hierarchy fields.
 *
 * @group entity_hierarchy_breadcrumb
 */
class BreadcrumbTest extends EntityHierarchyKernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_hierarchy_breadcrumb',
    'entity_hierarchy',
    'entity_test',
    'system',
    'user',
    'dbal',
    'field',
  ];

  /**
   * A route to test with.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $testRoute;

  /**
   * The breadcrumb manager service.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $user = $this->createUser([], ['view test entity']);
    $this->container->get('account_switcher')->switchTo($user);
    $this->breadcrumbBuilder = $this->container->get('entity_hierarchy.breadcrumb');

    $this->testRoute = new Route('/entity_test/{entity_test}', [], [], ['parameters' => [static::ENTITY_TYPE => ['type' => 'entity:' . static::ENTITY_TYPE]]]);
  }

  /**
   * Tests the applies when the entity has a hierarchy field.
   */
  public function testAppliesWhenOnEntityWithParentField() {
    $route_match = new RouteMatch('test', $this->testRoute, [static::ENTITY_TYPE => $this->parent], [static::ENTITY_TYPE => $this->parent]);
    $this->assertTrue($this->breadcrumbBuilder->applies($route_match));
  }

  /**
   * Tests applies on bundle without hierarchy field.
   */
  public function testAppliesOnEntityTypeWithoutParentField() {
    $bundle = EntityTestBundle::create(['id' => 'a_different_bundle']);
    $bundle->save();
    $entity = $this->doCreateTestEntity(['type' => $bundle->id()]);
    $route_match = new RouteMatch('test', $this->testRoute, [static::ENTITY_TYPE => $entity], [static::ENTITY_TYPE => $entity]);
    $this->assertFalse($this->breadcrumbBuilder->applies($route_match));
  }

  /**
   * Tests applies on admin route.
   */
  public function testAppliesOnAdminRoute() {
    $this->testRoute->setOption('_admin_route', TRUE);
    $route_match = new RouteMatch('test', $this->testRoute, [static::ENTITY_TYPE => $this->parent], [static::ENTITY_TYPE => $this->parent]);
    $this->assertFalse($this->breadcrumbBuilder->applies($route_match));
  }

  /**
   * Tests applies on non entity route.
   */
  public function testAppliesWithNoEntityInRouteMatch() {
    $route_match = new RouteMatch('test', $this->testRoute);
    $this->assertFalse($this->breadcrumbBuilder->applies($route_match));
  }

  /**
   * Tests breadcrumb builder returns the hierarchy.
   */
  public function testBreadcrumbsFollowHierarchy() {
    $entity_type = static::ENTITY_TYPE;

    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $grandchildren = $this->createChildEntities($child->id(), 3);
    $grandchild = reset($grandchildren);

    $route_match = new RouteMatch('test', $this->testRoute, [$entity_type => $grandchild], [$entity_type => $grandchild->id()]);
    $actual = $this->breadcrumbBuilder->build($route_match);

    $this->assertNotEquals(0, $actual->getCacheMaxAge());

    $actual_links = $actual->getLinks();

    $expected_cache_tags = [
      sprintf("%s:%s", $entity_type, $this->parent->id()),
      sprintf("%s:%s", $entity_type, $child->id()),
      sprintf("%s:%s", $entity_type, $grandchild->id()),
    ];
    $this->assertEquals($expected_cache_tags, $actual->getCacheTags());
    $this->assertEquals('Home', $actual_links[0]->getText());
    $this->assertEquals('/', $actual_links[0]->getUrl()->toString());
    $this->assertEquals($this->parent->label(), $actual_links[1]->getText());
    $this->assertEquals($this->parent->toUrl()->toString(), $actual_links[1]->getUrl()->toString());
    $this->assertEquals($child->label(), $actual_links[2]->getText());
    $this->assertEquals($child->toUrl()->toString(), $actual_links[2]->getUrl()->toString());
    $this->assertEquals($grandchild->label(), $actual_links[3]->getText());
    $this->assertEquals('', $actual_links[3]->getUrl()->toString());
  }

}
