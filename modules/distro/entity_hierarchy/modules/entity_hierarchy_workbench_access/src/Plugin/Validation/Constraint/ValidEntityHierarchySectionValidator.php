<?php

namespace Drupal\entity_hierarchy_workbench_access\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Defines a class for validating entity hierarchy selection with access.
 */
class ValidEntityHierarchySectionValidator extends ConstraintValidator implements ConstraintValidatorInterface, ContainerInjectionInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Plugin manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $workbenchManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ValidEntityHierarchySection.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $workbenchManager
   *   Workbench manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountInterface $currentUser, WorkbenchAccessManagerInterface $workbenchManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->workbenchManager = $workbenchManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    if ($this->currentUser->hasPermission('bypass workbench access')) {
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $items->entity;
    if (!$parent) {
      if ($this->configFactory->get('workbench_access.settings')->get('deny_on_empty')) {
        $this->context->addViolation($constraint->message);
      }
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $saved */
    if ($items->getEntity()->id() && $saved = $this->entityTypeManager->getStorage($parent->getEntityTypeId())->loadUnchanged($items->getEntity()->id())) {
      $field_name = $items->getFieldDefinition()->getFieldStorageDefinition()->getName();
      if ($saved->hasField($field_name) && !$saved->get($field_name)->isEmpty() && $saved->{$field_name}->entity && $saved->{$field_name}->entity->id() === $parent->id()) {
        // The user is not changing the field value.
        return;
      }
    }

    $result = array_reduce($this->entityTypeManager->getStorage('access_scheme')->loadMultiple(), function (AccessResult $carry, AccessSchemeInterface $scheme) use ($parent) {
      $carry->addCacheableDependency($scheme)->cachePerPermissions()->addCacheableDependency($parent);
      return $carry->orIf($scheme->getAccessScheme()->checkEntityAccess($scheme, $parent, 'update', $this->currentUser));
    }, AccessResult::neutral());
    if ($result->isForbidden()) {
      $this->context->addViolation($constraint->message);
    }
  }

}
