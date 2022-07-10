<?php

namespace Drupal\contact_storage\Controller;

use Drupal\contact\ContactFormInterface;
use Drupal\contact\Controller\ContactController;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for contact storage routes.
 */
class ContactStorageController extends ContactController {

  /**
   * {@inheritdoc}
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL) {
    // This is an override of ContactController::contactSitePage() that uses
    // the entity view builder. This is necessary to show the close message for
    // disabled forms.
    $config = $this->config('contact.settings');

    // Use the default form if no form has been passed.
    $manager = $this->entityTypeManager();
    if (empty($contact_form)) {
      $contact_form = $manager
        ->getStorage('contact_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($contact_form)) {
        if ($this->currentUser()->hasPermission('administer contact forms')) {
          $this->messenger()->addError($this->t('The contact form has not been configured. <a href=":add">Add one or more forms</a> .', [
            ':add' => Url::fromRoute('contact.form_add')->toString(),
          ]));
          return [];
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $view_builder = $manager->getViewBuilder('contact_form');
    return $view_builder->view($contact_form, 'full', $contact_form->language());
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form.
   *
   * @return string
   *   The title of the contact form.
   */
  public function contactFormTitle(ContactFormInterface $contact_form) {
    return $contact_form->label();
  }

  /**
   * Edit route title callback.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form.
   *
   * @return string
   *   The title of the contact form.
   */
  public function contactEditFormTitle(ContactFormInterface $contact_form) {
    return $this->t('Edit @label', ['@label' => $contact_form->label()]);
  }

}
