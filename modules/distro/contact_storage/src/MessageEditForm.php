<?php

namespace Drupal\contact_storage;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for contact message edit forms.
 */
class MessageEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\contact\MessageInterface $message */
    $message = $this->entity;
    $form = parent::form($form, $form_state);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author name'),
      '#maxlength' => 255,
      '#default_value' => $message->getSenderName(),
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Sender email address'),
      '#default_value' => $message->getSenderMail(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->logger('contact')->notice('The contact message %subject has been updated.', [
      '%subject' => $this->entity->getSubject(),
      'link' => $this->getEntity()->toLink($this->t('Edit'), 'edit-form')->toString(),
    ]);
  }

}
