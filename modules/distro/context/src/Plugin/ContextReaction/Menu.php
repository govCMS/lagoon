<?php

namespace Drupal\context\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content reaction that adds a css 'active' class to menu item.
 *
 * @ContextReaction(
 *   id = "menu",
 *   label = @Translation("Menu")
 * )
 */
class Menu extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The menu parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelector
   */
  protected $menuParentFormSelector;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuParentFormSelectorInterface $menu_parent_form_selector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuParentFormSelector = $menu_parent_form_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.parent_form_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Set active menu item based on conditions.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$vars = []) {
    $config = $this->getConfiguration();
    return $config['menu'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $parent_element = $this->menuParentFormSelector->parentSelectElement('main:');
    $config = $this->getConfiguration();
    $form['menu_items'] = [
      '#title' => $this->t('Menu'),
      '#type' => 'select',
      '#options' => $parent_element['#options'],
      '#multiple' => TRUE,
      '#default_value' => isset($config['menu']) ? $config['menu'] : '',
      '#size' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = array_keys($form_state->getValue('menu_items'));

    $this->setConfiguration([
      'menu' => $values,
    ]);
  }

}
