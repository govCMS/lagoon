<?php

namespace Drupal\context\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a reaction that changes page title.
 *
 * @ContextReaction(
 *   id = "page_title",
 *   label = @Translation("Page title")
 * )
 */
class PageTitle extends ContextReactionPluginBase {

  /**
   * {@inheritDoc}
   */
  public function summary() {
    return $this->t('Lets you override the page title');
  }

  /**
   * {@inheritDoc}
   */
  public function execute() {
    $config = $this->getConfiguration();
    return $config['page_title'];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => isset($config['page_title']) ? $config['page_title'] : '',
      '#description' => $this->t('Enter the title you wish to display.'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config['page_title'] = $form_state->getValue('page_title');
    $this->setConfiguration($config);
  }

}
