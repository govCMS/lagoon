<?php

namespace Drupal\entity_hierarchy_microsite\Plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a trait for microsite plugin functionality.
 */
trait MicrositePluginTrait {

  /**
   * Child of microsite lookup.
   *
   * @var \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface
   */
  protected $childOfMicrositeLookup;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Sets value of NestedSetStorageFactory.
   *
   * @param \Drupal\entity_hierarchy_microsite\ChildOfMicrositeLookupInterface $childOfMicrositeLookup
   *   Lookup.
   *
   * @return $this
   */
  public function setChildOfMicrositeLookup(ChildOfMicrositeLookupInterface $childOfMicrositeLookup) {
    $this->childOfMicrositeLookup = $childOfMicrositeLookup;
    return $this;
  }

  /**
   * Entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   *
   * @return $this
   */
  protected function setEntityFieldManager(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    return $instance->setChildOfMicrositeLookup($container->get('entity_hierarchy_microsite.microsite_lookup'))
      ->setEntityFieldManager($container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['field' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity hierarchy field'),
      '#options' => $this->getFieldOptions(),
      '#empty_option' => 'None',
      '#empty_value' => '',
      '#default_value' => $this->configuration['field'],
      '#description' => $this->t('Select the field to use to identify if the current node is the child of a microsite'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['field'] = $form_state->getValue('field');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Gets field options.
   *
   * @return array
   *   Field names keyed by label.
   */
  protected function getFieldOptions() {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference_hierarchy');
    $options = [];
    if (isset($fields['node'])) {
      foreach ($fields['node'] as $field_name => $detail) {
        foreach ($detail['bundles'] as $bundle) {
          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
          $field = $this->entityFieldManager->getFieldDefinitions('node', $bundle)[$field_name];
          $options[$field_name] = $field->getLabel();
        }
      }
    }
    return $options;
  }

}
