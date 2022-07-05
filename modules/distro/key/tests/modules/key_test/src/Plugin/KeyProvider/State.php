<?php

namespace Drupal\key_test\Plugin\KeyProvider;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a key provider that stores in memory.
 * @KeyProvider(
 *   id = "key_test_state",
 *   label = @Translation("State ☠️"),
 *   description = @Translation("Stores keys in state."),
 *   storage_method = "state",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = FALSE
 *   }
 * )
 */
class State extends KeyProviderBase implements KeyProviderSettableValueInterface, ContainerFactoryPluginInterface {

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['state_key' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    return $this->state->get('key_test:' . $this->configuration['state_key']);
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    return $this->state->set('key_test:' . $this->configuration['state_key'], $key_value);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    return $this->state->delete('key_test:' . $this->configuration['state_key']);
  }

}
