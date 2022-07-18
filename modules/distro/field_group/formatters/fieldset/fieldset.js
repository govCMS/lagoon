/**
 * @file
 * Provides the processing logic for fieldsets.
 */

(function ($) {

  'use strict';

  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * This script adds the required and error classes to the fieldset wrapper.
   */
  Drupal.behaviors.fieldGroupFieldset = {
    attach: function (context) {

      $(context).find('.field-group-fieldset').once('field-group-fieldset').each(function () {
        var $this = $(this);

        if ($this.is('.required-fields') && ($this.find('[required]').length > 0 || $this.find('.form-required').length > 0)) {
          $('legend', $this).first().addClass('form-required');
        }
      });
    }
  };

})(jQuery);
