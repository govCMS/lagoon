<?php

namespace Drupal\Tests\metatag_open_graph_products\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Open Graph Product tags work correctly.
 *
 * @group metatag
 */
class MetatagOpenGraphProductsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag_open_graph_products'];

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'product_availability',
    'product_condition',
    'product_price_amount',
    'product_price_currency',
    'product_retailer_item_id',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testTag = 'meta';

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'property';

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
    // Replace the first underline with a colon.
    $tag_name = str_replace('product_', 'product:', $tag_name);

    // Additional meta tags.
    $tag_name = str_replace('price_', 'price:', $tag_name);

    return $tag_name;
  }

}
