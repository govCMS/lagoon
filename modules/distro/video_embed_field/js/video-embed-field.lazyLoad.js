/**
 * @file
 * The video_embed_field lazy loading videos.
 */

(function($, once) {
  Drupal.behaviors.video_embed_field_lazyLoad = {
    attach: function (context, settings) {
      $(once('video-embed-field-lazy', '.video-embed-field-lazy', context)).click(function(e) {
        // Swap the lightweight image for the heavy JavaScript.
        e.preventDefault();
        var $el = $(this);
        $el.html($el.data('video-embed-field-lazy'));
      });
    }
  };
})(jQuery, once);
