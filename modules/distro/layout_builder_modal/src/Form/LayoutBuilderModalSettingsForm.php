<?php

namespace Drupal\layout_builder_modal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the layout builder modal configuration form.
 */
class LayoutBuilderModalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_modal_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['layout_builder_modal.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('layout_builder_modal.settings');

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];
    $form['options']['modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('modal_width'),
      '#description' => $this->t(
        'Width in pixels with no units (e.g. "<code>768</code>"), or use percentage (e.g. "<code>80%</code>"). See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-width']
      ),
      '#size' => 20,
      '#required' => TRUE,
    ];
    $form['options']['modal_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('modal_height'),
      '#description' => $this->t(
        'Height in pixels with no units (e.g. "<code>768</code>") or "auto" for automatic height. See <a href=":link">the jQuery Dialog documentation</a> for more details.',
        [':link' => 'https://api.jqueryui.com/dialog/#option-height']
      ),
      '#size' => 20,
      '#required' => TRUE,
    ];

    $form['options']['modal_autoresize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto resize'),
      '#default_value' => $config->get('modal_autoresize'),
      '#description' => $this->t('Allow modal to automatically resize and enable scrolling for dialog content. If enabled, the ability to drag and resize the dialog will be disabled.'),
    ];
    $theme_config = \Drupal::config('system.theme');
    $theme_handler = \Drupal::service('theme_handler');
    $theme_options = [
      'default_theme' => $this->t('Default (%default_theme)', ['%default_theme' => $theme_handler->getName($theme_config->get('default'))]),
      'seven' => $this->t("Administrative (Seven)"),
    ];
    $form['options']['theme_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $theme_options,
      '#default_value' => $config->get('theme_display') ?? 'default_theme',
      '#description' => $this->t('Choose whether the default theme should display on its own, or whether to add administrative form CSS.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $width = $form_state->getValue('modal_width');
    if (substr_count($width, '%') == 1 && $width[strlen($width) - 1] == '%') {
      $percentage_value = substr($width,0,-1);
      if (!is_numeric($percentage_value) || $percentage_value < 1 || $percentage_value > 100) {
        $form_state->setErrorByName('modal_width', $this->t('Width must be a positive number or a percentage.'));
      }
    }
    elseif ((!is_numeric($width) || $width < 1)) {
      $form_state->setErrorByName('modal_width', $this->t('Width must be a positive number or a percentage.'));
    }
    $height = $form_state->getValue('modal_height');
    if ((!is_numeric($height) || $height < 1) && $height !== 'auto') {
      $form_state->setErrorByName('modal_height', $this->t('Height must be a positive number or "auto".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('layout_builder_modal.settings')
      ->set('modal_width', $form_state->getValue('modal_width'))
      ->set('modal_height', $form_state->getValue('modal_height'))
      ->set('modal_autoresize', $form_state->getValue('modal_autoresize'))
      ->set('theme_display', $form_state->getValue('theme_display'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
