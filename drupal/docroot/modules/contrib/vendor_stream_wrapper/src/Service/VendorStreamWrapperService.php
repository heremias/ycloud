<?php

namespace Drupal\vendor_stream_wrapper\Service;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides services for the Vendor Stream Wrapper module.
 */
class VendorStreamWrapperService implements VendorStreamWrapperServiceInterface {

  /**
   * The Stream Wrapper Service.
   *
   * @var Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperService;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(StreamWrapperManagerInterface $streamWrapperManager, RequestStack $request_stack) {
    $this->streamWrapperService = $streamWrapperManager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function creatUrlFromUri($uri, $include_base_url = TRUE) {
    if (strpos($uri, 'vendor://') === 0) {
      if ($wrapper = $this->streamWrapperService->getViaUri($uri)) {
        $url = $wrapper->getExternalUrl();
        if ($include_base_url) {
          return $url;
        }
        $base_url = $this->requestStack->getCurrentRequest()->getBaseUrl();
        return substr($url, strlen($base_url !== '/' ? $base_url : ''));
      }
    }
    else {
      return $uri;
    }
  }

}
