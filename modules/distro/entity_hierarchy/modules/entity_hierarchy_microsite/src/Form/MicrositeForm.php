<?php

namespace Drupal\entity_hierarchy_microsite\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class for microsite form.
 */
class MicrositeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Microsite.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Microsite.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.entity_hierarchy_microsite.collection');
  }

}
