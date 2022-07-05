<?php

namespace Drupal\encrypt\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for testing encryption / decryption on a given profile.
 */
class EncryptionProfileTestForm extends EntityForm {

  /**
   * The encrypt service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptService;

  /**
   * Constructs the test form.
   *
   * @param \Drupal\encrypt\EncryptService $encrypt_service
   *   The encryption service.
   */
  public function __construct(EncryptService $encrypt_service) {
    $this->encryptService = $encrypt_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['encrypt'] = [
      '#type' => 'details',
      '#title' => $this->t('Encryption test'),
      '#open' => TRUE,
    ];

    $form['encrypt']['to_encrypt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to encrypt'),
      '#description' => $this->t('Specify the text you want to encrypt with this encryption profile. The result of the encryption will be shown below.'),
    ];

    $form['encrypt']['encrypt_base64'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Base64-encode the encrypted text.'),
    ];

    $form['encrypt']['encrypt_text'] = [
      '#type' => 'submit',
      '#value' => $this->t('Encrypt'),
      '#name' => 'encrypt',
    ];

    $form['encrypt']['encrypted'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Encrypted text'),
      '#value' => $form_state->getValue('encrypted'),
      '#disabled' => TRUE,
    ];

    $form['decrypt'] = [
      '#type' => 'details',
      '#title' => $this->t('Decryption test'),
      '#open' => TRUE,
    ];

    $form['decrypt']['to_decrypt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to decrypt'),
      '#description' => $this->t('Specify the text you want to decrypt with this encryption profile. The result of the decryption will be shown below.'),
    ];

    $form['decrypt']['decrypt_base64'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Base64-decode the encrypted text before decrypting.'),
    ];

    $form['decrypt']['decrypt_text'] = [
      '#type' => 'submit',
      '#value' => $this->t('Decrypt'),
      '#name' => 'decrypt',
    ];

    $form['decrypt']['decrypted'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Decrypted text'),
      '#value' => $form_state->getValue('decrypted'),
      '#disabled' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    switch ($trigger['#name']) {
      case 'encrypt':
        if ($to_encrypt = $form_state->getValue('to_encrypt')) {
          $encrypted_text = $this->encryptService->encrypt($to_encrypt, $this->entity);
          if ($form_state->getValue('encrypt_base64')) {
            $encrypted_text = base64_encode($encrypted_text);
          }
          $form_state->setValue('encrypted', $encrypted_text);
        }
        break;

      case 'decrypt':
        if ($to_decrypt = $form_state->getValue('to_decrypt')) {
          if ($form_state->getValue('decrypt_base64')) {
            $to_decrypt = base64_decode($to_decrypt);
          }
          $decrypted_text = $this->encryptService->decrypt($to_decrypt, $this->entity);
          $form_state->setValue('decrypted', $decrypted_text);
        }
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['submit']);
    unset($actions['delete']);
    return $actions;
  }

}
