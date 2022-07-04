<?php

namespace Drupal\purge_purger_http\Form;

/**
 * Configuration form for the HTTP Bundled Purger.
 */
class HttpBundledPurgerForm extends HttpPurgerFormBase {

  /**
   * {@inheritdoc}
   */
  protected $tokenGroups = ['invalidations'];

}
