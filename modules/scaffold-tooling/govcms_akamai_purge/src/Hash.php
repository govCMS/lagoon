<?php

namespace Drupal\govcms_akamai_purge;

/**
 * Helper class that centralizes string hashing for security and maintenance.
 *
 * Copied from https://github.com/section/section_purger.
 *
 * @see src/Entity/Hash.php
 */
class Hash {

  /**
   * Create a hash with the given input and length.
   *
   * @param string $input
   *   The input string to be hashed.
   * @param int $length
   *   The length of the hash.
   *
   * @return string
   *   Cryptographic hash with the given length.
   */
  protected static function hashInput($input, $length) {
    // MD5 is the fastest algorithm beyond CRC32 (which is 30% faster, but high
    // collision risk), so this is the best bet for now. If collisions are going
    // to be a major problem in the future, we might have to consider a hash DB.
    $hex = md5($input);
    // The produced HEX can be converted to BASE32 number to take less space.
    // For example 5 characters HEX can be stored in 4 characters BASE32.
    $hash = base_convert(substr($hex, 0, ceil($length * 1.25)), 16, 32);
    // Return a hash with consistent length, padding zeroes if needed.
    return strtolower(str_pad(substr($hash, 0, $length), $length, '0', STR_PAD_LEFT));
  }

  /**
   * Create a unique hash/ID for a cache tag string.
   *
   * @param string $tag
   *   A cache tag.
   * @param string $prefix
   *   An optional prefix for the tag.
   *
   * @return string
   *   Hashed copy of the given cache tag.
   */
  public static function cacheTag($tag, $prefix = '') {
    $tag = $prefix . $tag;
    if (strlen($tag) > 4) {
      $tag = self::hashInput($tag, 4);
    }
    return $tag;
  }

  /**
   * Create unique hashes/IDs for a list of cache tag strings.
   *
   * @param string[] $tags
   *   Non-associative array cache tags.
   * @param string $prefix
   *   An optional prefix for each tag.
   *
   * @return string[]
   *   Non-associative array with hashed copies of the given cache tags.
   */
  public static function cacheTags(array $tags, $prefix = '') {
    $hashes = [];
    foreach ($tags as $tag) {
      $hashes[] = self::cacheTag($tag, $prefix);
    }
    return $hashes;
  }

  /**
   * Create a unique hash that identifies this site.
   *
   * @param string $site_name
   *   The identifier of the site on Acquia Cloud.
   * @param string $site_path
   *   The path of the site, e.g. 'site/default' or 'site/database_a'.
   *
   * @return string
   *   Cryptographic hash that's long enough to be unique.
   */
  public static function siteIdentifier($site_name, $site_path) {
    return self::hashInput($site_name . $site_path, 16);
  }

}
