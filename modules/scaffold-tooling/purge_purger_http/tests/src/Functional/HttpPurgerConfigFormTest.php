<?php

namespace Drupal\Tests\purge_purger_http\Functional;

use Drupal\purge_purger_http\Form\HttpPurgerForm;

/**
 * Tests \Drupal\purge_purger_http\Form\HttpPurgerForm.
 *
 * @group purge_purger_http
 */
class HttpPurgerConfigFormTest extends HttpPurgerConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $formClass = HttpPurgerForm::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'http';

  /**
   * {@inheritdoc}
   */
  protected $tokenGroups = ['invalidation'];

}
