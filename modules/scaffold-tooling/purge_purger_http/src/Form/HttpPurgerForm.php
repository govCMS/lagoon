<?php

namespace Drupal\purge_purger_http\Form;

/**
 * Configuration form for the HTTP Bundled Purger.
 */
class HttpPurgerForm extends HttpPurgerFormBase {

  /**
   * {@inheritdoc}
   */
  protected $tokenGroups = ['invalidation'];

}
