<?php

namespace Drupal\openy_daxko_gxp_syncer;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Daxko Gxp Api Client.
 */
class DaxkoGxpClient {

  const CACHE_PREFIX = 'openy_daxko_gxp_syncer:';
  const MAX_DELAY = 60;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The http client.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * Construct class.
   */
  public function __construct(Client $client, ConfigFactoryInterface $configFactory, CacheBackendInterface $cache, LoggerChannelInterface $logger) {
    $this->client = $client;
    $this->configFactory = $configFactory;
    $this->config = $this->configFactory->get('openy_daxko_gxp_syncer.settings');
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * Get access token to API.
   *
   * @see https://docs.partners.daxko.com/tutorials/authentication/
   */
  public function getAccessToken($force = FALSE) {
    if (!$force &&$cache = $this->cache->get(self::CACHE_PREFIX . 'access_token')) {
      $access_token = $cache->data;
      return $access_token;
    }
    $json = [
      'client_id' => $this->config->get('client_id'),
      'client_secret' => $this->config->get('client_secret'),
      'scope' => $this->config->get('scope'),
      'grant_type' => $this->config->get('grant_type'),
    ];
    $url = $this->config->get('auth_url');

    try {
      $response = $this->client->post($url, ['json' => $json]);
      $body = $response->getBody();
      $content = $body->getContents();
      $json = json_decode($content, TRUE, JSON_THROW_ON_ERROR);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      throw new SyncException($e->getMessage(), $e->getCode());
    }

    $access_token = $json["access_token"];
    $expires_in = $json["expires_in"];

    $currentTime = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
    $access_token_expire = clone $currentTime;
    $access_token_expire->modify('+' . $expires_in . ' seconds');
    $access_token_expire = $access_token_expire->getTimestamp();

    $this->cache->set(self::CACHE_PREFIX . 'access_token', $access_token, $access_token_expire);

    return $access_token;
  }

  /**
   * Get schedules from daxko api.
   *
   * @param string $startDate
   *   Start date period range.
   * @param string $endDate
   *   End date period range.
   * @param int $locationId
   *   GXP location id.
   *
   * @see https://docs.partners.daxko.com/openapi/gxp/#operation/get-class-details
   */
  public function getSchedules($startDate, $endDate, $locationId, $capacity = FALSE, $retry = 0) {
    if ($retry >= $this->config->get('retry')) {
      $msg = 'Retry limit. We can`t get data from daxko api.';
      $this->logger->error($msg);
      throw new SyncException($msg, 403);
    }
    $queryParams = [
      'locationId' => $locationId,
      'startDate' => $startDate,
      'endDate' => $endDate,
      'capacity' => $capacity ? 1 : 0,
    ];
    $options = [
      'headers' => [
        'Authorization' => "Bearer " . $this->getAccessToken(),
      ],
      'timeout' => 60,
    ];
    $queryStr = http_build_query($queryParams);
    $url = $this->config->get('api_url') . 'classes?' . $queryStr;
    $json = [];
    try {
      $response = $this->client->get($url, $options);
      $body = $response->getBody();
      $content = $body->getContents();
      if (empty(trim($content))) {
        return [];
      }
      $json = json_decode($content, TRUE, JSON_THROW_ON_ERROR);
    }
    catch (\Exception $e) {
      $this->logger->warning('%try-th Retry to get data from daxko gxp api for location %id. %code - %msg', [
        '%msg' => $e->getMessage(),
        '%code' => $e->getCode(),
        '%id' => $locationId,
        '%try' => $retry + 1,
      ]);
      $delay = ((int) $this->config->get('delay')) * ($retry + 1);
      if ($delay > self::MAX_DELAY) {
        $delay = self::MAX_DELAY;
      }
      if ($delay > 0) {
        sleep($delay);
      }
      $this->getAccessToken($force = TRUE);
      $retry += 1;
      $json = $this->getSchedules($startDate, $endDate, $locationId, $capacity, $retry);
      return $json;
    }

    return $json;
  }

  /**
   * Get schedule datails.
   *
   * @param string $daxkoId
   *   The ID of the schedule.
   *
   * @see https://docs.partners.daxko.com/openapi/gxp/#operation/get-class-details-by-id
   */
  public function getScheduleDetails($daxkoId) {
    $options = [
      'headers' => [
        'Authorization' => "Bearer " . $this->getAccessToken(),
      ],
    ];
    $url = $this->config->get('api_url') . 'classes/' . $daxkoId;
    $json = [];
    try {
      $response = $this->client->get($url, $options);
      $body = $response->getBody();
      $content = $body->getContents();
      $json = json_decode($content, TRUE, JSON_THROW_ON_ERROR);
      if (!$json) {
        $json = [];
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e);
      return [];
    }

    return $json;
  }

}
