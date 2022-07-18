<?php

namespace Drupal\Tests\entity_hierarchy_microsite\Functional;

use Drupal\Core\Url;

/**
 * Defines a class for testing microsite admin.
 *
 * @group entity_hierarchy_microsite
 */
class MicrositeAdminTest extends MicrositeFunctionalTestBase {

  /**
   * Tests admin.
   */
  public function testAdmin() {
    $assert = $this->assertSession();

    // Access check for non-admins.
    $listing = Url::fromRoute('entity.entity_hierarchy_microsite.collection');
    $this->drupalGet($listing);
    $assert->statusCodeEquals(403);

    // Test admin can create a microsite.
    $this->drupalLogin($this->createUser(array_keys(\Drupal::service('user.permissions')->getPermissions())));
    $this->drupalGet('admin/structure');
    $assert->linkExists('Microsites');
    $this->drupalGet($listing);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('There are no microsites yet.');
    $assert->linkExists('Add Microsite');
    $this->clickLink('Add Microsite');
    $assert->fieldExists('Name');
    $assert->fieldExists('Home page');
    $assert->fieldExists('logo[0][target_id]');
    $assert->elementExists('css', 'legend:contains("Logo")');
    $root = $this->createTestEntity(NULL, 'Root');
    $children = $this->createChildEntities($root->id(), 2);
    $child = reset($children);
    $grandchildren = $this->createChildEntities($child->id(), 1);
    $grandchild = reset($grandchildren);
    $logo = $this->createImageMedia();
    $entity_reference_format = '%s (%s)';
    $label = $this->randomMachineName();
    $this->submitForm([
      'name[0][value]' => $label,
      'home[0][target_id]' => sprintf($entity_reference_format, $root->label(), $root->id()),
      'logo[0][target_id]' => sprintf($entity_reference_format, $logo->label(), $logo->id()),
    ], 'Save');
    $assert->pageTextContains(sprintf('Created the %s Microsite', $label));

    // Test that admin can edit the microsite.
    $this->assertStringContainsString($listing->toString(), $this->getSession()->getCurrentUrl());
    $this->clickLink('Edit');
    $assert->fieldValueEquals('Name', $label);
    $assert->fieldValueEquals('Home page', sprintf($entity_reference_format, $root->label(), $root->id()));
    $assert->fieldValueEquals('logo[0][target_id]', sprintf($entity_reference_format, $logo->label(), $logo->id()));
    $label = $this->randomMachineName();
    $this->submitForm([
      'name[0][value]' => $label,
    ], 'Save');
    $assert->pageTextContains(sprintf('Saved the %s Microsite', $label));
    $this->assertStringContainsString($listing->toString(), $this->getSession()->getCurrentUrl());

    // Test menu link admin.
    $menuAdmin = Url::fromRoute('entity.menu.edit_form', ['menu' => 'entity-hierarchy-microsite']);
    $this->drupalGet($menuAdmin);
    foreach (array_merge($grandchildren, $children, [$root]) as $entity) {
      $assert->linkByHrefExists($entity->toUrl()->toString());
      $assert->linkExists($entity->label());
    }

    // Test overriding a title.
    $overrideStorage = \Drupal::entityTypeManager()->getStorage('eh_microsite_menu_override');
    $this->drupalGet($menuAdmin);
    $other_child = end($children);
    $row = $assert->elementExists('css', 'tr:contains("' . $other_child->label() . '")');
    $edit = $row->find('named', ['link', 'Edit']);
    $edit->click();
    $newOverrideUrl = new Url('entity.eh_microsite_menu_override.add', ['target' => $other_child->uuid()]);
    $this->assertStringContainsString($newOverrideUrl->toString(), $this->getSession()->getCurrentUrl());
    $assert->fieldValueEquals('Parent link', 'entity-hierarchy-microsite:entity_hierarchy_microsite:' . $root->uuid());
    $newTitle = $this->randomMachineName();
    $this->submitForm([
      'title[0][value]' => $newTitle,
      'menu_parent' => 'entity-hierarchy-microsite:entity_hierarchy_microsite:' . $child->uuid(),
    ], 'Save');
    $overrides = $overrideStorage->loadByProperties([
      'target' => $other_child->uuid(),
    ]);
    $row = $assert->elementExists('css', 'tr:contains("' . $newTitle . '")');
    $this->assertCount(1, $overrides);
    $override = reset($overrides);
    $this->assertTrue($override->isEnabled());
    $this->assertTrue($override->isExpanded());
    $this->assertEquals($newTitle, $override->label());
    $this->assertEquals('entity_hierarchy_microsite:' . $child->uuid(), $override->getParent());
    $this->assertEquals($other_child->uuid(), $override->getTarget());

    // Test disabling via admin form.
    $this->submitForm([
      sprintf('links[menu_plugin_id:entity_hierarchy_microsite:%s][enabled]', $grandchild->uuid()) => FALSE,
    ], 'Save');
    $listOverrides = $overrideStorage->loadByProperties([
      'target' => $grandchild->uuid(),
    ]);
    $this->assertCount(1, $overrides);
    /** @var \Drupal\entity_hierarchy_microsite\Entity\MicrositeMenuItemOverrideInterface $override */
    $listOverride = reset($listOverrides);
    $this->assertFalse($listOverride->isEnabled());
    $this->assertTrue($listOverride->isExpanded());
    $this->assertEquals($grandchild->label(), $listOverride->label());
    $this->assertEquals('entity_hierarchy_microsite:' . $child->uuid(), $listOverride->getParent());
    $this->assertEquals($grandchild->uuid(), $listOverride->getTarget());

    // Test edit button now goes to edit page and editing is possible.
    $edit = $assert->elementExists('named', ['link', 'Edit'], $row);
    $edit->click();
    $this->assertStringContainsString($override->toUrl('edit-form')->toString(), $this->getSession()->getCurrentUrl());
    $assert->fieldValueEquals('Parent link', 'entity-hierarchy-microsite:entity_hierarchy_microsite:' . $child->uuid());
    $anotherTitle = $this->randomMachineName();
    $this->submitForm([
      'title[0][value]' => $anotherTitle,
      'menu_parent' => 'entity-hierarchy-microsite:entity_hierarchy_microsite:' . $child->uuid(),
    ], 'Save');
    $overrides = $overrideStorage->loadByProperties([
      'target' => $other_child->uuid(),
    ]);
    $this->assertCount(1, $overrides);
    $row = $assert->elementExists('css', 'tr:contains("' . $anotherTitle . '")');

    // Cannot create a duplicate.
    $this->drupalGet($newOverrideUrl);
    $assert->statusCodeEquals(404);

    // Go back to the menu edit page.
    $this->drupalGet($menuAdmin);
    $revert = $assert->elementExists('named', ['link', 'Remove override'], $row);
    $revert->click();
    $this->assertStringContainsString($override->toUrl('delete-form')->toString(), $this->getSession()->getCurrentUrl());
    $assert->pageTextContains(sprintf('Are you sure you want to delete the microsite menu override %s', $anotherTitle));
    $this->submitForm([], 'Delete');
    $overrides = $overrideStorage->loadByProperties([
      'target' => $other_child->uuid(),
    ]);
    $this->assertCount(0, $overrides);
    $row = $assert->elementExists('css', 'tr:contains("' . $other_child->label() . '")');
    $assert->elementNotExists('css', 'tr:contains("' . $anotherTitle . '")');
    $assert->elementExists('named', ['link', 'Edit'], $row);
    $assert->elementNotExists('named', ['link', 'Remove override'], $row);

    // Non admins cannot access the url to create new overrides.
    $this->drupalLogout();
    $newOverrideUrl = new Url('entity.eh_microsite_menu_override.add', ['target' => $child->uuid()]);
    $this->drupalGet($newOverrideUrl);
    $assert->statusCodeEquals(403);
  }

}
