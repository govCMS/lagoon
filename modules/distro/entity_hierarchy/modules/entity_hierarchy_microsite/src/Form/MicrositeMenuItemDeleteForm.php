<?php

namespace Drupal\entity_hierarchy_microsite\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a delete form for override menu links.
 *
 * @internal
 */
class MicrositeMenuItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.menu.edit_form', ['menu' => 'entity-hierarchy-microsite']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The menu link override %title has been deleted.', ['%title' => $this->entity->label()]);
  }

}
