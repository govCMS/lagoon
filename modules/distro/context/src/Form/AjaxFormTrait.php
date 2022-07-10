<?php

namespace Drupal\context\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides helper methods for using an AJAX modal.
 *
 * This is a copy of the ctools AjaxFormTrait.
 */
trait AjaxFormTrait {

  /**
   * Gets attributes for use with an AJAX modal.
   *
   * @return array
   *   Array with the ajax modal attributes.
   */
  public static function getAjaxAttributes() {
    return [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 1000,
      ]),
    ];
  }

  /**
   * Gets attributes for use with an add button AJAX modal.
   *
   * @return array
   *   Array with the add button AJAX modal attributes.
   */
  public static function getAjaxButtonAttributes() {
    return NestedArray::mergeDeep(AjaxFormTrait::getAjaxAttributes(), [
      'class' => [
        'button',
        'button--small',
        'button-action',
      ],
    ]);
  }

}
