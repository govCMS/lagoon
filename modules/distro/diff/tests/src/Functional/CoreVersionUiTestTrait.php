<?php

namespace Drupal\Tests\diff\Functional;

/**
 * Maintains differences between 8.3.x and 8.4.x for tests.
 */
trait CoreVersionUiTestTrait {

  /**
   * Posts the node form depending on core version.
   *
   * @param string|\Drupal\Core\Url $path
   *   The path to post the form.
   * @param array $edit
   *   An array of values to post.
   * @param string $submit
   *   The label of the submit button to post.
   */
  protected function drupalPostNodeForm($path, array $edit, $submit) {
    if (!version_compare(\Drupal::VERSION, '8.4', '<')) {
      // Check for translations.
      if (strpos($submit, 'translation') !== FALSE) {
        $submit = t('Save (this translation)');
      }
      else {
        // Form button is back to simply 'Save'.
        $submit = t('Save');
      }

      // Check the publish checkbox.
      if (strpos($submit, 'publish') !== FALSE) {
        $edit['status[value]'] = 1;
      }
    }
    $this->drupalPostForm($path, $edit, $submit);
  }

}