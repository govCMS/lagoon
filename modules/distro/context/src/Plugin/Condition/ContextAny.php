<?php

namespace Drupal\context\Plugin\Condition;

use Drupal\context\ContextManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Context (any)' condition.
 *
 * @Condition(
 *   id = "context",
 *   label = @Translation("Context (any)"),
 * )
 */
class ContextAny extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Context Manager.
   *
   * @var \Drupal\context\ContextManager
   */
  private $contextManager;

  /**
   * Constructs a ContextAny condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\context\ContextManager $context_manager
   *   A context manager for checking the current active contexts.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ContextManager $context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contextManager = $context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['values' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $contexts = array_filter(array_map('trim', explode("\n", $this->configuration['values'])));

    foreach ($contexts as $id) {
      // Strip out `~` for negated contexts.
      $id = ltrim($id, '~');

      /** @var \Drupal\context\ContextInterface $context */
      $context = $this->contextManager->getContext($id);
      /** @var \Drupal\Core\Condition\ConditionInterface[] $context_conditions */
      $context_conditions = $context->getConditions();
      foreach ($context_conditions as $condition) {
        $cache_contexts = Cache::mergeContexts($cache_contexts, $condition->getCacheContexts());
      }
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['negate']);
    $form['values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Context (any)'),
      '#description' => $this->t('Set this context on the basis of other active contexts. Put each context on a separate line. The condition will pass if <em>any</em> of the contexts are active. You can use the <code>*</code> character (asterisk) as a wildcard and the <code>~</code> character (tilde) to prevent this context from activating if the listed context is active. Other contexts which use context conditions can not be used to exclude this context from activating.'),
      '#default_value' => $this->configuration['values'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['values'] = $form_state->getValue('values');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $contexts = array_map('trim', explode("\n", $this->configuration['values']));
    $contexts = implode(', ', $contexts);
    return $this->t('Return true on the basis of other active contexts: @contexts', ['@contexts' => $contexts]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $required_contexts = $negated_contexts = [];
    $asterisk_context = '';
    $values = array_filter(array_map('trim', explode("\n", $this->configuration['values'])));

    if (empty($values)) {
      return TRUE;
    }

    foreach ($values as $key) {
      if (substr($key, 0, 1) == "~") {
        $negated_contexts[] = substr($key, 1);
      }
      elseif (strpos($key, '*') !== FALSE) {
        $asterisk_context = $key;
      }
      elseif (!empty($key)) {
        $required_contexts[] = $key;
      }
    }

    // Handle negated contexts first.
    foreach ($negated_contexts as $name) {
      /** @var \Drupal\context\ContextInterface $negated_context */
      $negated_context = $this->contextManager->getContext($name);
      if ($this->contextManager->evaluateContextConditions($negated_context) && !$negated_context->disabled()) {
        return FALSE;
      }
    }

    // Now handle required contexts.
    foreach ($required_contexts as $name) {
      /** @var \Drupal\context\ContextInterface $required_context */
      if ($required_context = $this->contextManager->getContext($name)) {
        if ($this->contextManager->evaluateContextConditions($required_context) && !$required_context->disabled()) {
          return TRUE;
        }
      }
    }

    // Handle the asterisks/wildcard contexts.
    /** @var \Drupal\context\ContextInterface $asterisk_contexts */
    if ($asterisk_contexts = $this->contextManager->getContext($asterisk_context)) {
      foreach ($asterisk_contexts as $context) {
        if ($this->contextManager->evaluateContextConditions($context) && !$context->disabled()) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
