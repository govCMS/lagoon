<?php

namespace Drupal\entity_hierarchy_microsite\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_hierarchy_microsite\EntityHooks;

/**
 * Defines a class for a microsite entity.
 *
 * @ContentEntityType(
 *   id = "entity_hierarchy_microsite",
 *   label = @Translation("Microsite"),
 *   label_collection = @Translation("Microsites"),
 *   label_singular = @Translation("microsite"),
 *   label_plural = @Translation("microsites"),
 *   label_count = @PluralTranslation(
 *     singular = "@count microsite",
 *     plural = "@count microsites",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_hierarchy_microsite\MicrositeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\entity_hierarchy_microsite\Form\MicrositeForm",
 *       "add" = "Drupal\entity_hierarchy_microsite\Form\MicrositeForm",
 *       "edit" = "Drupal\entity_hierarchy_microsite\Form\MicrositeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_hierarchy_microsite",
 *   admin_permission = "administer entity hierarchy microsites",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity-hierarchy-microsites/add",
 *     "edit-form" = "/admin/structure/entity-hierarchy-microsites/{entity_hierarchy_microsite}/edit",
 *     "delete-form" = "/admin/structure/entity-hierarchy-microsites/{entity_hierarchy_microsite}/delete",
 *     "collection" = "/admin/structure/entity-hierarchy-microsites",
 *   },
 *   field_ui_base_route = "entity.entity_hierarchy_microsite.collection"
 * )
 */
class Microsite extends ContentEntityBase implements MicrositeInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
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
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['home'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Home page')
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['logo'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Logo')
      ->setSetting('target_type', 'media')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => \Drupal::moduleHandler()->moduleExists('media_library') ? 'media_library_widget' : 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getHome() {
    return $this->get('home')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo() {
    return $this->get('logo')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityHooks::class)->onMicrositePostSave($this, $update);
  }

}
