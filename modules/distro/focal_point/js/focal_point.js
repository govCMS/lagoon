/**
 * @file
 * Javascript functionality for the focal point widget.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Focal Point indicator.
   */
  Drupal.behaviors.focalPointIndicator = {
    attach: function (context) {
      $(".focal-point", context).once('focal-point-hide-field').each(function () {
        var $wrapper = $(this).closest('.focal-point-wrapper');
        // Add the "visually-hidden" class unless the focal point offset field
        // has an error. This will show the field for everyone when there is an
        // error and for non-sighted users no matter what. We add it the
        // form item to make sure the field is focusable while
        // the entire form item is hidden for sighted users.
        if (!$(this).hasClass('error')) {
          $wrapper.addClass('visually-hidden');
          $(this).on('focus', function () {
            $wrapper.removeClass('visually-hidden');
          }).on('blur', function () {
            $wrapper.addClass('visually-hidden');
          });
        }
      });

      $(".focal-point-indicator", context).once('focal-point-indicator').each(function () {
        // Set some variables for the different pieces at play.
        var $indicator = $(this);
        var $img = $(this).siblings('img');
        var $previewLink = $(this).siblings('.focal-point-preview-link');
        var $field = $("." + $(this).attr('data-selector'));
        var fp = new Drupal.FocalPoint($indicator, $img, $field, $previewLink);

        // Set the position of the indicator on image load and any time the
        // field value changes. We use a bit of hackery to make certain that the
        // image is loaded before moving the crosshair. See http://goo.gl/B02vFO
        // The setTimeout was added to ensure the focal point is set properly on
        // modal windows. See http://goo.gl/s73ge.
        setTimeout(function () {
          $img.one('load', function () {
            fp.setIndicator();
          }).each(function () {
            if (this.complete) {
              $(this).trigger('load');
            }
          });
        }, 0);

      });
    }

  };

  /**
   * Object representing the focal point for a given image.
   *
   * @param $indicator object
   *   The indicator jQuery object whose position should be set.
   * @param $img object
   *   The image jQuery object to which the indicator is attached.
   * @param $field array
   *   The field jQuery object where the position can be found.
   * @param $previewLink object
   *   The previewLink jQuery object.
   */
  Drupal.FocalPoint = function ($indicator, $img, $field, $previewLink) {
    var self = this;

    this.$indicator = $indicator;
    this.$img = $img;
    this.$field = $field;
    this.$previewLink = $previewLink;

    // Make the focal point indicator draggable and tell it to update the
    // appropriate field when it is moved by the user.
    this.$indicator.draggable({
      containment: self.$img,
      stop: function () {
        var imgOffset = self.$img.offset();
        var focalPointOffset = self.$indicator.offset();

        var leftDelta = focalPointOffset.left - imgOffset.left;
        var topDelta = focalPointOffset.top - imgOffset.top;

        self.set(leftDelta, topDelta);
      }
    });

    // Allow users to double-click the indicator to reveal the focal point form
    // element.
    this.$indicator.on('dblclick', function () {
      self.$field.closest('.focal-point-wrapper').toggleClass('visually-hidden');
    });

    // Allow users to click on the image preview in order to set the focal_point
    // and set a cursor.
    this.$img.on('click', function (event) {
      self.set(event.offsetX, event.offsetY);
    });
    this.$img.css('cursor', 'crosshair');

    // Add a change event to the focal point field so it will properly update
    // the indicator position and preview link.
    this.$field.on('change', function () {
     $(document).trigger('drupalFocalPointSet', { $focalPoint: self });
    });

    // Wrap the focal point indicator and thumbnail image in a div so that
    // everything still works with RTL languages.
    this.$indicator.add(this.$img).add(this.$previewLink).wrapAll("<div class='focal-point-wrapper' />");
  };

  /**
   * Set the focal point.
   *
   * @param offsetX int
   *   Left offset in pixels.
   * @param offsetY int
   *   Top offset in pixels.
   */
  Drupal.FocalPoint.prototype.set = function (offsetX, offsetY) {
    var focalPoint = this.calculate(offsetX, offsetY);
    this.$field.val(focalPoint.x + ',' + focalPoint.y).trigger('change');

    $(document).trigger('drupalFocalPointSet', { $focalPoint: this });
  };

  /**
   * Change the position of the focal point indicator. This may not work in IE7.
   */
  Drupal.FocalPoint.prototype.setIndicator = function () {
    var coordinates = this.$field.val() !== '' && this.$field.val() !== undefined ? this.$field.val().split(',') : [50,50];

    var left = Math.min(this.$img.width(), (parseInt(coordinates[0], 10) / 100) * this.$img.width());
    var top = Math.min(this.$img.height(), (parseInt(coordinates[1], 10) / 100) * this.$img.height());

    this.$indicator.css('left', Math.max(0, left));
    this.$indicator.css('top', Math.max(0,top));
    this.$field.val(coordinates[0] + ',' + coordinates[1]);
  };

  /**
   * Calculate the focal point for the given image.
   *
   * @param offsetX int
   *   Left offset in pixels.
   * @param offsetY int
   *   Top offset in pixels.
   *
   * @returns object
   */
  Drupal.FocalPoint.prototype.calculate = function (offsetX, offsetY) {
    var focalPoint = {};
    focalPoint.x = this.round(100 * offsetX / this.$img.width(), 0, 100);
    focalPoint.y = this.round(100 * offsetY / this.$img.height(), 0, 100);

    return focalPoint;
  };

  /**
   * Rounds the given value to the nearest integer within the given bounds.
   *
   * @param value float
   *   The value to round.
   * @param min int
   *   The lower bound.
   * @param max int
   *   The upper bound.
   *
   * @returns int
   */
  Drupal.FocalPoint.prototype.round = function (value, min, max) {
    var roundedVal = Math.max(Math.round(value), min);
    roundedVal = Math.min(roundedVal, max);

    return roundedVal;
  };

  /**
   * Updates the preview link to include the correct focal point value.
   *
   * @param dataSelector string
   *   The data-selector value for the preview link.
   * @param value string
   *   The new focal point value in the form x,y where x and y are integers from
   *   0 to 100.
   */
  Drupal.FocalPoint.prototype.updatePreviewLink = function (dataSelector, value) {
    var $previewLink = $('a.focal-point-preview-link[data-selector=' + dataSelector + ']');
    if ($previewLink.length > 0) {
      var href = $previewLink.attr('href').split('/');
      href.pop();
      // The search property contains the query string which in some cases
      // includes the focal_point_token which is used to determine access.
      href.push(value.replace(',', 'x').concat($previewLink[0].search ? $previewLink[0].search : ''));
      $previewLink.attr('href', href.join('/'));
    }

    // Update the ajax binding to reflect the new preview link href value.
    Drupal.ajax.instances.forEach(function (instance, index) {
      if (instance && $(instance.element).data('selector') === dataSelector) {
        var href = $(instance.selector).attr('href');
        Drupal.ajax.instances[index].url = href;
        Drupal.ajax.instances[index].options.url = href;
      }
    });
  };

  /**
   * Update the Focal Point indicator and preview link when focal point changes.
   *
   * @param {jQuery.Event} event
   *   The `drupalFocalPointSet` event.
   * @param {object} data
   *   An object containing the data relevant to the event.
   *
   * @listens event:drupalFocalPointSet
   */
  $(document).on('drupalFocalPointSet', function (event, data) {
    data.$focalPoint.setIndicator();
    data.$focalPoint.updatePreviewLink(data.$focalPoint.$field.attr('data-selector'), data.$focalPoint.$field.val());
  });

})(jQuery, Drupal);
