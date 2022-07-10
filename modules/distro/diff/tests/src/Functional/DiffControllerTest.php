<?php

namespace Drupal\Tests\diff\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the diff controller.
 *
 * @group diff
 */
class DiffControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'diff',
    'entity_test',
    'diff_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config('diff.settings')
      ->set('entity.entity_test_rev.name', TRUE)
      ->save();
  }

  /**
   * Tests the Controller.
   */
  public function testController() {
    $assert_session = $this->assertSession();

    $entity = EntityTestRev::create([
      'name' => 'test entity 1',
      'type' => 'entity_test_rev',
    ]);
    $entity->save();
    $vid1 = $entity->getRevisionId();

    $entity->name->value = 'test entity 2';
    $entity->setNewRevision(TRUE);
    $entity->save();
    $vi2 = $entity->getRevisionId();

    $url = Url::fromRoute('entity.entity_test_rev.revisions_diff', [
      'entity_test_rev' => $entity->id(),
      'left_revision' => $vid1,
      'right_revision' => $vi2,
    ]);
    $this->drupalGet($url);
    $assert_session->statusCodeEquals(403);

    $account = $this->drupalCreateUser([
      'view test entity',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet($url);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('<td class="diff-context diff-deletedline">test entity <span class="diffchange">1</span></td>');
    $assert_session->responseContains('<td class="diff-context diff-addedline">test entity <span class="diffchange">2</span></td>');
  }

}
