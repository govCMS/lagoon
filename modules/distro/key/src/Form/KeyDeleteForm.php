<?php

namespace Drupal\key\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\key\Plugin\KeyPluginDeleteFormInterface;

/**
 * Builds the form to delete a Key.
 */
class KeyDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the key %key?', ['%key' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.key.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Allow the plugins to modify the form.
    foreach ($this->entity->getPlugins() as $plugin) {
      if ($plugin instanceof KeyPluginDeleteFormInterface) {
        $plugin->buildDeleteForm($form, $form_state);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Allow the plugins to perform additional validation.
    foreach ($this->entity->getPlugins() as $plugin) {
      if ($plugin instanceof KeyPluginDeleteFormInterface) {
        $plugin->validateDeleteForm($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDeletionMessage() {
    return $this->t('The key %label has been deleted.', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Allow the plugins to perform additional actions.
    foreach ($this->entity->getPlugins() as $plugin) {
      if ($plugin instanceof KeyPluginDeleteFormInterface) {
        $plugin->submitDeleteForm($form, $form_state);
      }
    }
  }

}
