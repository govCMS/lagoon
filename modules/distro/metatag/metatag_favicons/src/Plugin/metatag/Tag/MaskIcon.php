<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "mask-icon" meta tag.
 *
 * @MetatagTag(
 *   id = "mask_icon",
 *   label = @Translation("Mask icon (SVG)"),
 *   description = @Translation("A grayscale scalable vector graphic (SVG) file."),
 *   name = "mask-icon",
 *   group = "favicons",
 *   weight = 2,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MaskIcon extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    $form['#container'] = TRUE;
    $form['#tree'] = TRUE;

    // Backwards compatibility.
    $defaults = $this->value();
    if (is_string($defaults)) {
      $defaults = [
        'href' => $defaults,
        'color' => '',
      ];
    }

    // The main icon value.
    $form['href'] = [
      '#type' => 'textfield',
      '#title' => $this->label(),
      '#default_value' => $defaults['href'] ?? '',
      '#maxlength' => 255,
      '#required' => $element['#required'] ?? FALSE,
      '#description' => $this->description(),
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    // New form element for color.
    $form['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mask icon color'),
      '#default_value' => $defaults['color'] ?? '',
      '#required' => FALSE,
      '#description' => $this->t("Color attribute for SVG (mask) icon in hexadecimal format, e.g. '#0000ff'. Setting it will break HTML validation. If not set macOS Safari ignores the Mask Icon entirely, making the Icon: SVG completely useless."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function output() {
    $values = $this->value;

    // Make sure the value is an array, if it is not then assume it was assigned
    // before the "color" attribute was added, so place the original string as
    // the 'href' element and leave the 'color' element blank.
    if (!is_array($values)) {
      $values = [
        'href' => $values,
        'color' => '',
      ];
    }

    // Build the output.
    $element['#tag'] = 'link';
    $element['#attributes'] = [
      'rel' => $this->name(),
      'href' => $this->tidy($values['href']),
    ];

    // Add the 'color' element.
    if (!empty($values['color'])) {
      $element['#attributes']['color'] = $this->tidy($values['color']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value) {
    // Do not store array with empty values.
    $this->value = is_array($value) && empty(array_filter($value)) ? NULL : $value;
  }

}
