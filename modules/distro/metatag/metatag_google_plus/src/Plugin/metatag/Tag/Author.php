<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The basic "Author" meta tag.
 *
 * @MetatagTag(
 *   id = "google_plus_author",
 *   label = @Translation("Author"),
 *   description = @Translation("DEPRECATED, use Advanced-Author instead."),
 *   name = "author",
 *   group = "google_plus",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 *
 * @deprecated in metatag:8.x-1.20 and is removed from metatag:2.0.0. No replacement is provided.
 *
 * @see https://www.drupal.org/project/metatag/issues/3284464
 */
class Author extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
