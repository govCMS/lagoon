<?php

namespace Drupal\Tests\metatag_google_cse\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google CSE tags work correctly.
 *
 * @group metatag
 */
class MetatagGoogleCSETagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag_google_cse'];

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'audience',
    'department',
    'doc_status',
    'google_rating',
    'thumbnail',
  ];

  /**
   * Implements {tag_name}TestTagName() for 'google_rating'.
   */
  protected function googleRatingTestTagName() {
    return 'rating';
  }

}
