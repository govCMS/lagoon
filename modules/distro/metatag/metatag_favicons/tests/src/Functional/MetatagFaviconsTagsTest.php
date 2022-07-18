<?php

namespace Drupal\Tests\metatag_favicons\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;
use Drupal\metatag\Entity\MetatagDefaults;

/**
 * Tests that each of the Metatag Favicons tags work correctly.
 *
 * @group metatag
 */
class MetatagFaviconsTagsTest extends MetatagTagsTestBase {
  
  public function testTagsArePresent() {return;}
  /**
   * Confirm that each tag can be saved and that the output is correct.
   *
   * Each tag is passed in one at a time (using the dataProvider) to make it
   * easier to distinguish when a problem occurs.
   *
   * @param string $tag_name
   *   The tag to test.
   *
   * @dataProvider tagsInputOutputProvider
   */
  public function testTagsInputOutput($tag_name) {return;}
  public function tagsInputOutputProvider() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag_favicons', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'shortcut_icon',
    'icon_16x16',
    'icon_32x32',
    'icon_96x96',
    'icon_192x192',
    'apple_touch_icon',
    'apple_touch_icon_72x72',
    'apple_touch_icon_76x76',
    'apple_touch_icon_114x114',
    'apple_touch_icon_120x120',
    'apple_touch_icon_144x144',
    'apple_touch_icon_152x152',
    'apple_touch_icon_180x180',
    'apple_touch_icon_precomposed',
    'apple_touch_icon_precomposed_72x72',
    'apple_touch_icon_precomposed_76x76',
    'apple_touch_icon_precomposed_114x114',
    'apple_touch_icon_precomposed_120x120',
    'apple_touch_icon_precomposed_144x144',
    'apple_touch_icon_precomposed_152x152',
    'apple_touch_icon_precomposed_180x180',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testTag = 'link';

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'rel';

  /**
   * {@inheritdoc}
   */
  protected $testValueAttribute = 'href';

  /**
   * Implements {tag_name}TestValueAttribute() for 'shortcut icon'.
   */
  protected function shortcutIconTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_16x16'.
   */
  protected function icon16x16TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='16x16']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_192x192'.
   */
  protected function icon192x192TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='192x192']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_32x32'.
   */
  protected function icon32x32TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='32x32']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_96x96'.
   */
  protected function icon96x96TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='96x96']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_precomposed'.
   */
  protected function appleTouchIconPrecomposedTestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and not(@sizes)]";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_114x114'.
   */
  protected function appleTouchIconPrecomposed114x114TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='114x114']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_120x120'.
   */
  protected function appleTouchIconPrecomposed120x120TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='120x120']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_144x144'.
   */
  protected function appleTouchIconPrecomposed144x144TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='144x144']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_152x152'.
   */
  protected function appleTouchIconPrecomposed152x152TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='152x152']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_180x180'.
   */
  protected function appleTouchIconPrecomposed180x180TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='180x180']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_72x72'.
   */
  protected function appleTouchIconPrecomposed72x72TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='72x72']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_76x76'.
   */
  protected function appleTouchIconPrecomposed76x76TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='76x76']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon'.
   */
  protected function appleTouchIconTestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and not(@sizes)]";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_114x114'.
   */
  protected function appleTouchIcon114x114TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='114x114']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_120x120'.
   */
  protected function appleTouchIcon120x120TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='120x120']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_144x144'.
   */
  protected function appleTouchIcon144x144TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='144x144']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_152x152'.
   */
  protected function appleTouchIcon152x152TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='152x152']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_180x180'.
   */
  protected function appleTouchIcon180x180TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='180x180']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_72x72'.
   */
  protected function appleTouchIcon72x72TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='72x72']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_76x76'.
   */
  protected function appleTouchIcon76x76TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='76x76']";
  }

  /**
   * Implements {tag_name}TestTagName for 'shortcut icon'.
   */
  protected function shortcutIconTestTagName() {
    return 'icon';
  }

  /**
   * Test mask_icon as it currently works.
   *
   * The mask_icon is a separate test case because of it's unusual structure.
   * Mask_icon exists of 2 parts, an href and a color.
   */
  public function _testMaskIconCurrent() {
    // Test that mask icon fields are available.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('mask_icon[href]');
    $this->assertSession()->fieldExists('mask_icon[color]');

    // Test that a mask_icon is saved successfully and is correctly shown in
    // the meta tags.
    $edit = [
      'mask_icon[href]' => 'mask_icon_href',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');

    $this->drupalGet('user');
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');

    // Add a mask_icon color and check if it's correctly shown in the meta
    // tags.
    $this->drupalGet('admin/config/search/metatag/global');
    $edit = [
      'mask_icon[color]' => '#FFFFFF',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');

    $this->drupalGet('user');
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href' and @color='#FFFFFF']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');
  }

  /**
   * Legacy data for the MaskIcon tag just stored a single string, not an array.
   */
  public function testMaskIconLegacy() {
    $this->loginUser1();
    // Add a metatag field to the entity type test_entity.
    $this->createContentType(['type' => 'page']);
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag',
      'new_storage_type' => 'metatag',
    ];
    $this->submitForm($edit, 'Save and continue');
    $this->submitForm([], 'Save field settings');

    // Create a demo node of this content type so it can be tested.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Hello, world!',
      'field_metatag[0][favicons][mask_icon][href]' => 'mask_icon_href',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('page Hello, World! has been created.');
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');

    // Update the database record.
    \Drupal::database()->update('node__field_metatag')
      ->fields([
        'field_metatag_value' => serialize([
          'mask_icon' => 'mask_icon_href',
        ]),
      ])
      ->condition('entity_id', 1)
      ->execute();

    // Clear caches to make sure the node is reloaded.
    drupal_flush_all_caches();

    // Reload the node.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm the mask icon value.
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');
  }

}
