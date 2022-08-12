<?php

namespace Drupal\vendor_stream_wrapper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Vendor Stream Wrapper file controller.
 *
 * Sets up serving of files from the vendor directory, using the vendor://
 * stream wrapper.
 */
class VendorFileDownloadController extends ControllerBase implements VendorFileDownloadControllerInterface {

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The log service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Creates a new VendorFileDownloadController instance.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    MimeTypeGuesserInterface $mimeTypeGuesser,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    $this->mimeTypeGuesser = $mimeTypeGuesser;
    $this->logger = $loggerFactory->get('Vendor File Download');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.mime_type.guesser'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function download(Request $request) {
    $filepath = str_replace(':', '/', $request->get('filepath'));

    // Check if the file path matches a whitelist pattern, this to ensure only
    // explicitly allowed vendor files can be accessed/downloaded.
    if (!$this->allowedVendorFilePattern($filepath)) {
      throw new AccessDeniedHttpException();
    }

    $scheme = 'vendor';
    $uri = $scheme . '://' . $filepath;

    $mime_type = '';
    try {
      $mime_type = $this->mimeTypeGuesser->guess($uri);
    }
    catch (\Exception $e) {
      $this->logger->error('Vendor file download error: %message', ['%message' => $e->getMessage()]);
    }

    if (!empty($mime_type)) {
      $headers = [
        'Content-Type' => $mime_type,
      ];

      try {
        return new BinaryFileResponse($uri, 200, $headers, TRUE);
      }
      catch (FileNotFoundException $e) {
        throw new NotFoundHttpException();
      }
    }

    throw new NotFoundHttpException();
  }

  /**
   * Checks if the provided vendor file (path) is allowed to be downloaded.
   *
   * @param string $file_path
   *   The path of the vendor file.
   *
   * @return bool
   *   TRUE when the file (path) matches a pattern of the whitelist, FALSE
   *   otherwise.
   */
  private function allowedVendorFilePattern(string $file_path) {
    // Get from configuration the patterns of vendor files that are allowed to
    // be downloaded/accessed.
    $allowed_file_patterns = $this->config('vendor_stream_wrapper.settings')->get('allowed_file_patterns') ?? [];

    foreach ($allowed_file_patterns as $pattern) {
      // Prepare the pattern to be used as a regular expression.
      $pattern = str_replace('\*', '.*', preg_quote($pattern, '/'));

      // In case a valid pattern matches, indicate that the particular file is
      // allowed to be accessed.
      if (preg_match('/^' . $pattern . '$/', $file_path)) {
        return TRUE;
      }
    }

    // No valid pattern matches the provided file path.
    return FALSE;
  }

}
