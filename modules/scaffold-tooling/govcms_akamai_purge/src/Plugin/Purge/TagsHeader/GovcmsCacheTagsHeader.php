<?php

namespace Drupal\govcms_akamai_purge\Plugin\Purge\TagsHeader;

use Drupal\govcms_akamai_purge\Hash;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderBase;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets and formats the default response header with cache tags.
 *
 * @PurgeTagsHeader(
 *   id = "govcms_tagsheader",
 *   header_name = "Edge-Cache-Tag",
 * )
 */
class GovcmsCacheTagsHeader extends TagsHeaderBase implements TagsHeaderInterface {

  /**
   * Whether to send cacheability headers for debugging purposes.
   *
   * @var bool
   */
  protected $debugCacheabilityHeaders = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $http_response_debug_cacheability_headers = FALSE) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->debugCacheabilityHeaders = $http_response_debug_cacheability_headers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('http.response.debug_cacheability_headers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $tags) {
    $project = getenv('LAGOON_PROJECT') . '-' . getenv('LAGOON_GIT_SAFE_BRANCH') . ':';
    // Hash tags when not debugging cacheability headers.
    if (!$this->debugCacheabilityHeaders) {
      $tags = Hash::cacheTags($tags, $project);
    }
    else {
      foreach ($tags as &$tag) {
        $tag = $project . $tag;
      }
    }
    return implode(',', $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (getenv('AKAMAI_PURGE_TOKEN') && getenv('LAGOON_PROJECT') && getenv('LAGOON_GIT_SAFE_BRANCH'));
  }

}
