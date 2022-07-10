<?php

namespace Drupal\consumers\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Consumer edit forms.
 */
class ConsumerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['is_default']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    $label = $this->entity->label();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Consumer.', [
          '%label' => $label,
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Consumer.', [
          '%label' => $label,
        ]));
    }
    $form_state->setRedirect('entity.consumer.collection');
  }

}
