/**
 * @file
 * Javascript for the Panelizer defaults page.
 */
(function ($, once) {
  Drupal.behaviors.panelizer_default_form = {
    attach: function (context, settings) {
      var $panelizer_checkbox = $(once('panelizer-default-form', 'input[name="panelizer[enable]"]', context));

      if (!$panelizer_checkbox.length) {
        return;
      }
      function update_form() {
        var $core_form = $('#field-display-overview-wrapper');
        if ($panelizer_checkbox.is(':checked')) {
          $core_form.fadeOut();
        }
        else {
          $core_form.fadeIn();
        }
      }

      $panelizer_checkbox.click(update_form);
      update_form();
    }
  };
})(jQuery, once);
