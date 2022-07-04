<?php

namespace Drupal\Tests\purge_purger_http\Functional;

use Drupal\purge_purger_http\Form\HttpBundledPurgerForm;

/**
 * Tests \Drupal\purge_purger_http\Form\HttpBundledPurgerForm.
 *
 * @group purge_purger_http
 */
class HttpBundledPurgerConfigFormTest extends HttpPurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $formClass = HttpBundledPurgerForm::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'httpbundled';

  /**
   * {@inheritdoc}
   */
  protected $tokenGroups = ['invalidations'];

}
