<?php

namespace Drupal\metatag;

use PHPUnit\Framework\Exception;

/**
 * MetatagTrimmer service class for trimming metatags.
 */
class MetatagTrimmer {

  /**
   * Trims a given string after the word on the given length.
   *
   * @param string $string
   *   The string to trim.
   * @param int $maxlength
   *   The maximum length where the string approximately gets trimmed.
   *
   * @return string
   *   The trimmed string.
   */
  public function trimAfterValue($string, $maxlength) {
    // If the string is shorter than the max length then skip the rest of the
    // logic.
    if ($maxlength > mb_strlen($string)) {
      return $string;
    }

    $spacePos = mb_strpos($string, ' ', $maxlength - 1);
    if (FALSE === $spacePos) {
      return $string;
    }
    $subString = mb_substr($string, 0, $spacePos);

    return trim($subString);
  }

  /**
   * Trims a given string before the word on the given length.
   *
   * @param string $string
   *   The string to trim.
   * @param int $maxlength
   *   The maximum length where the string approximately gets trimmed.
   *
   * @return string
   *   The trimmed string.
   */
  public function trimBeforeValue($string, $maxlength) {
    // If the string is shorter than the max length then skip the rest of the
    // logic.
    if ($maxlength > mb_strlen($string)) {
      return $string;
    }

    $subString = mb_substr($string, 0, $maxlength + 1);
    if (' ' === mb_substr($subString, -1)) {
      return trim($subString);
    }
    $spacePos = mb_strrpos($subString, ' ', 0);
    if (FALSE === $spacePos) {
      return $string;
    }
    $returnedString = mb_substr($string, 0, $spacePos);

    return trim($returnedString);
  }

  /**
   * Trims a value based on the given length and the given method.
   *
   * @param string $value
   *   The string to trim.
   * @param int $maxlength
   *   The maximum length where the string approximately gets trimmed.
   * @param string $method
   *   The trim method to use for the trimming.
   *   Allowed values: 'afterValue', 'onValue' and 'beforeValue'.
   */
  public function trimByMethod($value, $maxlength, $method) {
    if (empty($value) || empty($maxlength)) {
      return $value;
    }

    switch ($method) {
      case 'afterValue':
        return $this->trimAfterValue($value, $maxlength);

      case 'onValue':
        return trim(mb_substr($value, 0, $maxlength));

      case 'beforeValue':
        return $this->trimBeforeValue($value, $maxlength);

      default:
        throw new Exception('Unknown trimming method: ' . $method);
    }
  }

}
