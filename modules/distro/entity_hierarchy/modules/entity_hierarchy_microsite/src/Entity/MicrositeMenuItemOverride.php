<?php

namespace Drupal\entity_hierarchy_microsite\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_hierarchy_microsite\EntityHooks;

/**
 * Defines a class for a content entity to store menu item overrides.
 *
 * This is similar to MenuLinkContent, but each override in this scenario is
 * tied directly to an item in the microsite hierarchy. Users are free to add
 * new items to the menu using MenuLinkContent, but we need to be able to have
 * a 1:1 relationship between the override and the auto-derived hierarchy item.
 *
 * Adding a new override requires the target id value be set, and this is
 * enforced via a required flag on the field and the add-form only being present
 * with a required {target} path slug. From that point, any edits to the
 * override entity are respected by the auto-derivation. Deleting the content
 * entity is equivalent to resetting it to the hierarchy derived state. This is
 * not dissimilar to how core uses the static overrides service, with the key
 * distinction being the storage for these overrides uses a content-entity,
 * meaning that changes aren't tracked in configuration and can be readily made
 * by site-admins without needing a CMI deployment process/workflow.
 *
 * @ContentEntityType(
 *   id = "eh_microsite_menu_override",
 *   label = @Translation("Microsite menu override"),
 *   label_collection = @Translation("Microsite menu overrides"),
 *   label_singular = @Translation("microsite menu override"),
 *   label_plural = @Translation("microsite menu overrides"),
 *   label_count = @PluralTranslation(
 *     singular = "@count microsite menu override",
 *     plural = "@count microsite menu overrides",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "storage_schema" = "Drupal\entity_hierarchy_microsite\MicrositeMenuItemOverrideStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\entity_hierarchy_microsite\Form\MicrositeMenuItemForm",
 *       "delete" = "Drupal\entity_hierarchy_microsite\Form\MicrositeMenuItemDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_hierarchy_microsite_menu_override",
 *   admin_permission = "administer entity hierarchy microsites",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/entity-hierarchy-microsites/menu-items/{eh_microsite_menu_override}/edit",
 *     "delete-form" = "/admin/structure/entity-hierarchy-microsites/menu-items/{eh_microsite_menu_override}/delete",
 *   },
 * )
 */
class MicrositeMenuItemOverride extends ContentEntityBase implements MicrositeMenuItemOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu link title'))
      ->setDescription(t('The text to be used for this link in the menu.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setRequired(TRUE)
      ->setLabel(t('Weight'))
      ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ]);

    $fields['expanded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show as expanded'))
      ->setDescription(t('If selected and this menu link has children, the menu will always appear expanded. This option may be overridden for the entire menu tree when placing a menu block.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 0,
      ]);

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Enabled'))
      ->setDefaultValue(TRUE)
      ->setDescription(t('A flag for whether the link should be enabled in menus or hidden.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => -1,
      ]);

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent plugin ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.'));

    $fields['target'] = BaseFieldDefinition::create('uuid')
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setLabel(new TranslatableMarkup('Target UUID of item to override'));
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityHooks::class)->onMenuOverridePostSave($this, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityHooks::class)->onMenuOverridePostDelete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return $this->get('target')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->get('parent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return (bool) $this->get('expanded')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

}
