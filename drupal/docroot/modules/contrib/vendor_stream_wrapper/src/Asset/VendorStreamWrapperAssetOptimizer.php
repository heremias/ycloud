<?php

namespace Drupal\vendor_stream_wrapper\Asset;

use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\vendor_stream_wrapper\StreamWrapper\VendorStreamWrapper;

/**
 * Decorates the CSS and JS optimization services.
 *
 * The optimization services loads the CSS and JS files based on the provided
 * path, however, the path that is generated is virtual/non-existing (which
 * prevents the whole vendor folder being publicly available). Therefore, we
 * need to decorate the optimization service, used when aggregating CSS and JS
 * files, to set the correct file path for the CSS and JS assets in the vendor
 * folder.
 */
class VendorStreamWrapperAssetOptimizer implements AssetOptimizerInterface {

  /**
   * The original asset optimization service instance that is being decorated.
   *
   * @var \Drupal\Core\Asset\AssetOptimizerInterface
   */
  protected $innerService;

  /**
   * Creates a new VendorStreamWrapperCssOptimizer instance.
   *
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $inner_service
   *   The original asset optimization service instance that is being decorated.
   */
  public function __construct(AssetOptimizerInterface $inner_service) {
    $this->innerService = $inner_service;
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(array $asset) {
    // Translate the virtual 'vendor_files' paths into the correct path for the
    // vendor directory, so that the actual file can loaded by the optimization
    // service.
    if (!empty($asset['data']) && strpos($asset['data'], 'vendor_files/') === 0) {
      $asset['data'] = str_replace('vendor_files', VendorStreamWrapper::basePath(), $asset['data']);
    }

    return $this->innerService->optimize($asset);
  }

  /**
   * {@inheritdoc}
   */
  public function clean($content) {
    return $this->innerService->clean($content);
  }

}
