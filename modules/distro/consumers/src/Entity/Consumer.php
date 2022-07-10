<?php

namespace Drupal\consumers\Entity;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Consumer entity.
 *
 * @ContentEntityType(
 *   id = "consumer",
 *   label = @Translation("Consumer"),
 *   handlers = {
 *     "list_builder" = "Drupal\consumers\ConsumerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "add" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "edit" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "make-default" = "Drupal\consumers\Entity\Form\MakeDefaultForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\consumers\Entity\Routing\HtmlRouteProvider",
 *     },
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *     "access" = "Drupal\consumers\AccessControlHandler",
 *     "storage" = "Drupal\consumers\ConsumerStorage",
 *   },
 *   base_table = "consumer",
 *   data_table = "consumer_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer consumer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "owner" = "owner_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/consumer/{consumer}",
 *     "collection" = "/admin/config/services/consumer",
 *     "add-form" = "/admin/config/services/consumer/add",
 *     "edit-form" = "/admin/config/services/consumer/{consumer}/edit",
 *     "delete-form" = "/admin/config/services/consumer/{consumer}/delete",
 *     "make-default-form" = "/admin/config/services/consumer/{consumer}/make-default",
 *   }
 * )
 */
class Consumer extends ContentEntityBase implements EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $was_not_default = is_null($this->original)
      || !$this->original->get('is_default')->value;
    if ($this->get('is_default')->value && $was_not_default) {
      // If we are making this the new default consumer.
      try {
        $this->removeDefaultConsumerFlags();
      }
      catch (AccessException $exception) {
        watchdog_exception('consumers', $exception);
        \Drupal::messenger()->addError($exception->getMessage());
        $this->set('is_default', FALSE);
      }
    }

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->get('owner_id')->entity) {
        $translation->set('owner_id', 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'))
      ->setDescription(new TranslatableMarkup('The consumer label.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
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
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the consumer. This text will be shown to the users to authorize sharing their data to create an access token.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('Logo of the consumer.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -3,
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'progress_indicator' => 'throbber',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['third_party'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is this consumer 3rd party?'))
      ->setDescription(new TranslatableMarkup('Mark this if the organization behind this consumer is not the same as the one behind the Drupal API.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'weight' => 4,
      ])
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is this the default consumer?'))
      ->setDescription(new TranslatableMarkup('There can only be one default consumer. Mark this to use this consumer when none other applies.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'weight' => 4,
      ])
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * Removes the is_default flag from other consumers.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Access\AccessException
   */
  protected function removeDefaultConsumerFlags() {
    // Find the old defaults.
    $entity_storage = $this->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
    $entity_ids = $entity_storage
      ->getQuery()
      ->condition('is_default', TRUE)
      ->condition('id', $this->id(), '!=')
      ->execute();
    $entity_ids = $entity_ids ? array_values($entity_ids) : [];
    if (empty($entity_ids)) {
      $default_entities = [];
    }
    else {
      $default_entities = $entity_storage->loadMultiple($entity_ids);
      $default_entities = array_map(
        static::setDefaultTo(FALSE),
        $default_entities
      );
      $invalid_entities = array_filter($default_entities, function (Consumer $consumer) {
        return !$consumer->access('update', NULL, TRUE)->isAllowed();
      });
      if (count($invalid_entities)) {
        throw new AccessException('Unable to change the current default consumer. Permission denied.');
      }
    }
    array_map([$entity_storage, 'save'], $default_entities);
  }

  /**
   * Gets closure that will set is_default to the selected value for an entity.
   *
   * @param boolean $value
   *   The final value of the "is_default" field.
   *
   * @return \Closure
   */
  protected static function setDefaultTo($value) {
    return function (Consumer $consumer) use ($value) {
      $consumer->set('is_default', $value);
      return $consumer;
    };
  }

}
