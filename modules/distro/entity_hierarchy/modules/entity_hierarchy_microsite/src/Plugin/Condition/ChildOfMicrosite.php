<?php

namespace Drupal\entity_hierarchy_microsite\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_hierarchy_microsite\Plugin\MicrositePluginTrait;
use Drupal\node\NodeInterface;

/**
 * Defines a condition plugin to test if current page is child of microsite.
 *
 * @Condition(
 *   id = "entity_hierarchy_microsite_child",
 *   label = @Translation("Child of microsite"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Current node"), required = FALSE)
 *   }
 * )
 */
class ChildOfMicrosite extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use MicrositePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['field'])) {
      return TRUE;
    }
    return ($node = $this->getContextValue('node')) &&
      $node instanceof NodeInterface &&
      $this->childOfMicrositeLookup->findMicrositesForNodeAndField($node, $this->configuration['field']);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $options = $this->getFieldOptions();
    if (empty($this->configuration['field'])) {
      return '';
    }
    return $this->t('@state true if current page is a child of a microsite for the @field field.', [
      '@field' => $options[$this->configuration['field']],
      '@state' => empty($this->configuration['negate']) ? $this->t('Return') : $this->t('Do not return'),
    ]);
  }

}
