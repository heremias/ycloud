<?php

namespace Drupal\vendor_stream_wrapper\StreamWrapper;

/**
 * Inteface for VenderStreamWrapper (vendor://).
 */
interface VendorStreamWrapperInterface {

  /**
   * Returns the path to the site vendor directory.
   *
   * This is first searched for one level above the webroot, then the webroot,
   * and if not found in either of those locations, a custom path can be set in
   * settings.php.
   */
  public function getDirectoryPath();

  /**
   * Returns the base path for vendor://.
   *
   * @return string
   *   The base path for vendor://.
   */
  public static function basePath();

}
