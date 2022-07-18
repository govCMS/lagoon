<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The advanced "Content Language" meta tag.
 *
 * @MetatagTag(
 *   id = "content_language",
 *   label = @Translation("Content Language"),
 *   description = @Translation("DEPRECATED. Used to define this page's language code. May be the two letter language code, e.g. ""de"" for German, or the two letter code with a dash and the two letter ISO country code, e.g. ""de-AT"" for German in Austria. Still used by Bing."),
 *   name = "content-language",
 *   group = "advanced",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 *
 * @deprecated in metatag:8.x-1.20 and is removed from metatag:2.0.0. No replacement is provided.
 *
 * @see https://www.drupal.org/project/metatag/issues/3217263
 */
class ContentLanguage extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
