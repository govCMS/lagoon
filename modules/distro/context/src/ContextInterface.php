<?php

namespace Drupal\context;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for Context.
 */
interface ContextInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * The default value for a context that is not assigned to a group.
   */
  const CONTEXT_GROUP_NONE = NULL;

  /**
   * Get the ID of the context.
   *
   * @return string
   *   The the ID of the context.
   */
  public function id();

  /**
   * Get the machine name of the context.
   *
   * @return string
   *   The machine name of the context.
   */
  public function getName();

  /**
   * Set the machine name of the context.
   *
   * @param string $name
   *   The new name to set.
   *
   * @return $this
   *   This Context object.
   */
  public function setName($name);

  /**
   * Get the context label.
   *
   * @return string
   *   The context label.
   */
  public function getLabel();

  /**
   * Set the context label.
   *
   * @param string $label
   *   The new context label to set.
   *
   * @return $this
   *   This Context object.
   */
  public function setLabel($label);

  /**
   * Get the context description.
   *
   * @return string
   *   The context description.
   */
  public function getDescription();

  /**
   * Set the context description.
   *
   * @param string $description
   *   The new description to set.
   *
   * @return $this
   *   This Context object.
   */
  public function setDescription($description);

  /**
   * Get the group this context belongs to.
   *
   * @return null|string
   *   The name of the group.
   */
  public function getGroup();

  /**
   * Set the group this context should belong to.
   *
   * @param null|string $group
   *   The name of the group to set.
   *
   * @return $this
   *   This Context object.
   */
  public function setGroup($group);

  /**
   * Get the weight for this context.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Set the weight for this context.
   *
   * @param int $weight
   *   The weight to set for this context.
   *
   * @return $this
   *   This Context object.
   */
  public function setWeight($weight);

  /**
   * If the context requires all conditions to validate.
   *
   * @return bool
   *   TRUE if all conditions are required, FALSE if not.
   */
  public function requiresAllConditions();

  /**
   * Set if all conditions should be required for this context to validate.
   *
   * @param bool $require
   *   If a condition is required or not.
   *
   * @return $this
   *   This Context object.
   */
  public function setRequireAllConditions($require);

  /**
   * Get a list of all conditions.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|ConditionPluginCollection
   *   The plugin collection.
   */
  public function getConditions();

  /**
   * Get a condition with the specified ID.
   *
   * @param string $condition_id
   *   The condition to get.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The specific Condition.
   */
  public function getCondition($condition_id);

  /**
   * Set the conditions.
   *
   * @param array $configuration
   *   The configuration for the condition plugin.
   *
   * @return string
   *   The inserted condition ID.
   */
  public function addCondition(array $configuration);

  /**
   * Remove the specified condition.
   *
   * @param string $condition_id
   *   The id of the condition to remove.
   *
   * @return $this
   *   This Context object.
   */
  public function removeCondition($condition_id);

  /**
   * Check to see if the context has the specified condition.
   *
   * @param string $condition_id
   *   The ID of the condition to check for.
   *
   * @return bool
   *   TRUE if the context has the specified condition, FALSE if not.
   */
  public function hasCondition($condition_id);

  /**
   * Get a list of all the reactions.
   *
   * @return ContextReactionInterface[]|ContextReactionPluginCollection
   *   A reaction list.
   */
  public function getReactions();

  /**
   * Get a reaction with the specified ID.
   *
   * @param string $reaction_id
   *   The ID of the reaction to get.
   *
   * @return ContextReactionInterface
   *   A specific reaction.
   */
  public function getReaction($reaction_id);

  /**
   * Add a context reaction.
   *
   * @param array $configuration
   *   The reaction configuration array.
   *
   * @return string
   *   The inserted reaction ID.
   */
  public function addReaction(array $configuration);

  /**
   * Remove the specified reaction.
   *
   * @param string $reaction_id
   *   The id of the reaction to remove.
   *
   * @return $this
   *   This context object.
   */
  public function removeReaction($reaction_id);

  /**
   * Check to see if the context has the specified reaction.
   *
   * @param string $reaction_id
   *   The ID of the reaction to check for.
   *
   * @return bool
   *   TRUE if the context has the specified reaction, FALSE if not.
   */
  public function hasReaction($reaction_id);

}
