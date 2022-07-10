/**
 * @file
 * Context admin behaviors.
 */
(function ($, Drupal) {

  'use strict';

  /**
   * This is a generic table filter that works with any table formatted in the
   * correct way with classes and data attributes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.contextTableFilter = {
    attach: function () {
      var $input = $('input.context-table-filter').once('.context-table-filter');
      var $table = $($input.attr('data-element'));
      var $filter_rows;

      // Only attach the filter listener if there is a table to filter.
      if ($table.length) {
        $filter_rows = $table.find('.context-table-filter-text-source');
        $input.on('keyup', filterTableRows);
      }

      /**
       * Filters the table rows.
       *
       * @param {jQuery.Event} e
       *   The jQuery event for the keyup event that triggered the filter.
       */
      function filterTableRows(e) {
        var query = $(e.target).val().toLowerCase();

        /**
         * Shows or hides the Table rows based on the query.
         *
         * @param {number} index
         *   The index in the loop, as provided by `jQuery.each`
         *
         * @param {HTMLElement} label
         *   The label of the block.
         */
        function toggleTableRow(index, label) {
          var $label = $(label);
          var $row = $label.parent().parent();
          var textMatch = $label.text().toLowerCase().indexOf(query) !== -1;

          $row.toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $filter_rows.each(toggleTableRow);
        }
        else {
          $filter_rows.each(function () {
            $(this).parent().parent().show();
          });
        }
      }
    }
  };

}(jQuery, Drupal));
