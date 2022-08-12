<?php

namespace Drupal\openy_daxko2;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\Client;
use Drupal\openy_activity_finder\OpenyActivityFinderBackend;
use Psr\Log\LoggerInterface;
use Drupal\Core\Url;

/**
 * Daxko API back-end for Open Y Activity Finder.
 */
class OpenyActivityFinderDaxkoBackend extends OpenyActivityFinderBackend {

  /**
   * Number of results per page.
   */
  const RESULTS_PER_PAGE = 25;

  /**
   * Default number of available spots for unlimited programs.
   */
  const DEFAULT_NUMBER_OF_SPOTS = 100;

  /**
   * Daxko configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $daxkoConfig;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * OpenyActivityFinderDaxkoBackend constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param CacheBackendInterface $cache
   *   Cache default.
   * @param \GuzzleHttp\Client $http
   *   The http client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, CacheBackendInterface $cache, Client $http, ModuleHandlerInterface $module_handler, TimeInterface $time, LoggerInterface $logger) {
    parent::__construct($config_factory);
    $this->daxkoConfig = $config_factory->get('openy_daxko2.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->http = $http;
    $this->moduleHandler = $module_handler;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function runProgramSearch($parameters, $log_id): array {
    $parameters += [
      'locations' => '',
      'keywords' => '',
      'next' => '',
      'days' => '',
      'ages' => '',
      'sort' => '',
    ];

    $access_token = $this->getDaxkoToken();

    $get = [];
    $locationsConfig = $this->getDaxkoLocations();
    $locationArgument = $parameters['locations'];
    $locations = explode(',', $locationArgument);
    $locations = array_unique($locations);
    $locations = array_filter($locations);
    if (empty($locationArgument)) {
      $locations = array_keys($locationsConfig);
    } elseif (count($locations) == count($locationsConfig)) {
      // If all locations were selected we do not need to use
      // location_ids argument.
      $locationArgument = [];
    }

    $locations = array_filter($locations);

    if (!empty($locations) && !empty($locationArgument)) {
      $get['location_ids'] = implode(',', $locations);
    }

    $keywords = $parameters['keywords'];
    if (!empty($keywords)) {
      $get['keywords'] = $keywords;
    }

    if ($next = $parameters['next']) {
      $get['after'] = $next;
    }

    if ($days = $parameters['days']) {
      $get['days_offered'] = $days;
    }

    $ages = $parameters['ages'];
    if (!empty($ages)) {
      $ageGet = [];
      foreach (explode(',', $ages) as $age_value) {
        $ageGet[] = date('Y-m-d', strtotime('-' . $age_value . ' months'));
      }
      $get['birth_dates'] = implode(',', $ageGet);
    }

    if (isset($parameters['categories']) && !empty($parameters['categories'])) {
      $get['category_ids'] = $parameters['categories'];
    }

    $get['sort'] = 'DESC__score';
    if (!empty($parameters['sort'])) {
      $get['sort'] = $parameters['sort'];
    }
    $sort = $get['sort'];
    $get['sort'] = str_replace('ASC__', '+', $get['sort']);
    $get['sort'] = str_replace('DESC__', '-', $get['sort']);

    // Include facets. We need locations for Activity Finder.
    $get['include_facets'] = TRUE;

    $get['registration_type'] = 'online';

    $get['limit'] = self::RESULTS_PER_PAGE;

    $this->moduleHandler->alter('openy_daxko2_activity_finder_daxko_params', $get);

    $time_start = microtime(true);
    $client = new Client(['base_uri' => $this->daxkoConfig->get('base_uri')]);
    $response = $client->request('GET', 'programs/offerings/search',
      [
        'query' => $get,
        'headers' => [
          'Authorization' => "Bearer " . $access_token
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    $this->logger->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'programs/offerings/search',
    ]);

    $programsResponse = json_decode((string) $response->getBody(), TRUE);

    $locationNodesData = $this->getLocationsInfoFromNodes();
    $locations_config = $this->daxkoConfig->get('locations');
    $locations_rows = explode("\n", $locations_config);
    foreach ($locations_rows as $locations_row) {
      if (substr($locations_row, 0, 1) == '*') {
        continue;
      }
      $locations_row_fields = explode(',', $locations_row);
      // Remove id.
      array_shift($locations_row_fields);
      // Location name.
      $location_name = trim(array_shift($locations_row_fields));
      if (isset($locationNodesData[$location_name])) {
        continue;
      }
      $location_address = implode(',', $locations_row_fields);
      $locationNodesData[$location_name] = [
        'title' => $location_name,
        'address' => $location_address,
        'email' => '',
        'phone' => '',
        'days' => [],
      ];
    }

    $result = [];
    foreach ($programsResponse['offerings'] as $row) {
      $start_date = new \DateTime($row['start_date']);
      $end_date = new \DateTime($row['end_date']);
      $start_date_formatted = $start_date->format('M d');
      $end_date_formatted = $end_date->format('M d');
      if ($start_date->format('Y') != $end_date->format('Y')) {
        $start_date_formatted = $start_date->format('M d');
      }
      $times = '';
      if (isset($row['times'][0])) {
        $start = strtotime($row['times'][0]['start']);
        $end = strtotime($row['times'][0]['end']);
        if (date('a', $start) == date('a', $end)) {
          $times = date('g:i', $start) . '-' . date('g:ia', $end);
        }
        else {
          $times = date('g:ia', $start) . '-' . date('g:ia', $end);
        }
      }
      /**
       * added +1 day because DateTime::diff counts difference between
       * 2020-08-01 00:00:00.000000 and 2020-08-22 00:00:00.000000
       * as 21 day, not 22
       */
      $end_date->modify('+1 day');
      $weeks = $start_date->diff($end_date)->days/7;
      $weeks = ceil($weeks);
      $weeks = $weeks != 0 ? $weeks : '';
      $days = [];
      if (isset($row['days_offered'][0])) {
        foreach ($row['days_offered'] as $day) {
          $days[] = substr($day['name'], 0, 3);
        }
      }

      foreach ($row['locations'] as $location_row) {
        $location_id = $location_row['id'];
        if (!in_array($location_id, $locations)) {
          continue;
        }

        // Cache the Price for one day.
        $cache_key = 'daxko-price-' . md5($row['id'] . $row['program']['id'] . $location_id);
        $ttl = $this->time->getRequestTime() + 24 * 60 * 60;
        if ($cache = $this->cache->get($cache_key)) {
          $prices = $cache->data;
        }
        else {
          $prices = [];
          if (isset($row['groups'])) {
            foreach ($row['groups'] as $group) {
              $prices[] = $group['rate']['description'] . ' (' . strtolower($group['name']) . ')';
            }
          }
          $prices = implode(', ', $prices);
          $this->cache->set($cache_key, $prices, $ttl);
        }

        $location_name = $location_row['name'];
        $location_info = [
          'title' => $location_row['name'],
          'address' => '',
          'email' => '',
          'phone' => '',
          'days' => [],
        ];
        if (isset($locationNodesData[$location_name])) {
          $location_info = $locationNodesData[$location_name];
        }

        $availability_status = '';
        $availability_note = '';
        $cache_key = 'daxko-availability-' . md5(
            $row['id'] . $row['program']['id'] . $location_id
          );
        if ($cache = $this->cache->get($cache_key)) {
          $availability_status = $cache->data['status'];
          $availability_note = $cache->data['note'];
        }

        // Build register link.
        $program_id = $row['program']['id'];
        $offering_id = $row['id'];
        $register_link = 'https://ops1.operations.daxko.com/Online/' . $this->daxkoConfig->get(
            'client_id'
          ) . '/ProgramsV2/OfferingDetails.mvc?program_id=' . $program_id . '&offering_id=' . $offering_id . '&location_id=' . $location_id;

        $register_link_with_tracking = Url::fromRoute(
          'openy_activity_finder.register_redirect',
          ['log' => $log_id],
          [
            'query' => [
              'url' => $register_link,
              'details' => $row['name'] . ' - ' . $row['program']['name'],
            ]
          ]
        )->toString();

        $name = $row['name'] . ' - ' . $row['program']['name'];
        similar_text($row['name'], $row['program']['name'] , $percent);
        $percentage = !empty($this->daxkoConfig->get('percentage')) ? $this->daxkoConfig->get('percentage') : 100;
        if ($row['name'] == $row['program']['name'] || $percent >= $percentage) {
          $name = $row['name'];
        }

        if (isset($row['restrictions']['genders']) && count($row['restrictions']['genders']) == 1) {
          $gender = reset($row['restrictions']['genders']);
          $gender = $gender['name'];
        }

        if (isset($row['restrictions']['age'])) {
          $age = $row['restrictions']['age']['start'] . '-' . $row['restrictions']['age']['end'] . 'yrs';
        }

        $result[] = [
          'nid' => '',
          'location' => $location_name,
          'name' => $name,
          'dates' => $start_date_formatted . '-' . $end_date_formatted,
          'schedule' => [
            0 => [
              'time' => $times,
              'days' => implode(', ', $days),
            ]
          ],
          'weeks' => $weeks,
          'offering_id' => $offering_id,
          'program_id' => $program_id,
          'location_id' => $location_id,
          'info' => var_export($row, TRUE),
          'price' => $prices,
          'location_info' => $location_info,
          'availability_status' => $availability_status,
          'availability_note' => $availability_note,
          'activity_type' => '',
          'atc_info' => '',
          'link' => $register_link_with_tracking,
          'log_id' => $log_id,
          'spots_available' => '',
          'learn_more' => '',
          'more_results' => '',
          'more_results_type' => 'keyword',
          'program_name' => '',
          'gender' => $gender ?? '',
          'ages' => $age ?? '',
        ];
      }
    }

    $pager = '';
    if (isset($programsResponse['after'])) {
      $pager = $programsResponse['after'];
    }

    $facets = $programsResponse['facets'];
    foreach ($facets as &$facet_data) {
      foreach ($facet_data as &$facet) {
        $facet = [
          'filter' => $facet['name'],
          'id' => $facet['id'],
          'count' => $facet['offering_count'],
        ];
      }
    }

    // Rename facet to match Solr backend.
    if (isset($facets['categories'])) {
      $facets['field_activity_category'] = $facets['categories'];
      unset($facets['categories']);
    }

    $locationsWithResults = [];
    $locations = $this->getLocations();
    foreach ($locations as $key => $group) {
      $locations[$key]['count'] = 0;
      foreach ($group['value'] as $location) {
        foreach ($facets['locations'] as $fl) {
          if ($fl['id'] == $location['value']) {
            $locations[$key]['count'] += $fl['count'];
            $locationsWithResults[] = $location['value'];
          }
        }
      }
    }

    foreach ($locations as $group) {
      foreach ($group['value'] as $location) {
        if (!in_array($location['value'], $locationsWithResults)) {
          $facets['locations'][] = [
            'id' => $location['value'],
            'filter' => $location['label'],
            'count' => 0
          ];
        }
      }
    }

    return [
      'count' => $programsResponse['total'],
      'table' => $result,
      'pager' => $pager,
      'facets' => $facets,
      'groupedLocations' => $locations,
      'sort' => $sort,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    // Need locations grouped to display in filter.
    return $this->getDaxkoLocations(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Need locations grouped to display in filter.
    return $this->getDaxkoCategories(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesType() {
    return 'single';
  }

  /**
   * The list of top level categories.
   */
  public function getCategoriesTopLevel() {
    $categories_config = $this->daxkoConfig->get('categories');
    $categories = $this->parseGrouppedSetting($categories_config, TRUE);

    return array_keys($categories);
  }

  /**
   * Loads the list of Daxko categories from the configuration.
   *
   * @param false $group
   *   Groping flag.
   *
   * @return array
   *   The array of categories,
   */
  protected function getDaxkoCategories(bool $group = FALSE): array {
    $categories_config = $this->daxkoConfig->get('categories');
    $categories = $this->parseGrouppedSetting($categories_config, $group);
    if (!$group) {
      return $categories;
    }

    $numericalKeyedCategories = [];
    foreach ($categories as $label => $value) {
      $numericalKeyedCategories[] = ['value' => $value, 'label' => $label];
    }

    return $numericalKeyedCategories;
  }

  /**
   * Run query to Daxko to get the token.
   */
  protected function getDaxkoToken() {
    $time_start = microtime(true);
    $response = $this->http->request('POST', $this->daxkoConfig->get('base_uri') . 'partners/oauth2/token',
      [
        'form_params' => [
          'client_id' => $this->daxkoConfig->get('user'),
          'client_secret' => $this->daxkoConfig->get('pass'),
          'grant_type' => 'client_credentials',
          'scope' => 'client:' . $this->daxkoConfig->get('client_id'),
        ],
        'headers' => [
          'Authorization' => "Bearer " . $this->daxkoConfig->get('referesh_token')
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    $this->logger->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'partners/oauth2/token',
    ]);

    return json_decode((string) $response->getBody())->access_token;
  }

  /**
   * Load locations from the configuration.
   *
   * @param false $group
   *   The grouping flag.
   *
   * @return array
   *   The array of locations.
   */
  protected function getDaxkoLocations($group = FALSE): array {
    $locations_config = $this->daxkoConfig->get('locations');
    $locations = $this->parseGrouppedSetting($locations_config, $group);

    if (!$group) {
      return $locations;
    }

    $numericalKeyedLocations = [];
    foreach ($locations as $label => $value) {
      $numericalKeyedLocations[] = ['value' => $value, 'label' => $label];
    }

    return $numericalKeyedLocations;
  }

  /**
   * Parses the complex text configuration.
   *
   * @param string $config
   *   Configuration string.
   * @param bool $group
   *   Groping flag.
   *
   * @return array
   *   The array with the parsed data.
   */
  protected function parseGrouppedSetting(string $config, bool $group): array {
    $config_rows = explode("\n", $config);

    $result = [];
    $group_name = '';
    foreach ($config_rows as $row) {
      $row = trim($row);
      if (empty($row)) {
        continue;
      }
      if (!$group && substr($row, 0, 1) == '*') {
        continue;
      }
      if ($group && substr($row, 0, 1) == '*') {
        $row = trim($row);
        $group_name = trim($row, '*');
        continue;
      }
      [$id, $name, ] = explode(',', $row);
      $id = trim($id);
      $name = trim($name);
      if (!empty($id) && !empty($name)) {
        if ($group && $group_name) {
          $result[$group_name][] = ['value' => $id, 'label' => $name];
        }
        else {
          $result[$id] = $name;
        }
      }
    }

    return $result;
  }

  /**
   * Get detailed info about Location (aka branch).
   */
  protected function getLocationsInfoFromNodes() {
    $cid = 'openy_repeat:locations_info';
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $data = [];
    $tags = ['node_list'];

    $storage = $this->entityTypeManager->getStorage('node');

    $nids = $storage->getQuery()
      ->condition('type','branch')
      ->execute();
    $nids_chunked = array_chunk($nids, 20, TRUE);
    foreach ($nids_chunked as $chunk) {
      $branches = $storage->loadMultiple($chunk);
      if (!empty($branches)) {
        foreach ($branches as $node) {
          $days = $node->get('field_branch_hours')->getValue();
          $locAddress = $node->get('field_location_address')->getValue();
          if (!empty($locAddress[0])) {
            $address = [];
            if (!empty($locAddress[0]['address_line1'])) {
              $address[] = $locAddress[0]['address_line1'];
            }
            if (!empty($locAddress[0]['locality'])) {
              $address[] = $locAddress[0]['locality'];
            }
            if (!empty($locAddress[0]['administrative_area'])) {
              $address[] = $locAddress[0]['administrative_area'];
            }
            if (!empty($locAddress[0]['postal_code'])) {
              $address[] = $locAddress[0]['postal_code'];
            }
            $address = implode(', ', $address);
          }
          $data[$node->getTitle()] = [
            'nid' => $node->id(),
            'title' => $node->getTitle(),
            'email' => $node->get('field_location_email')->value,
            'phone' => $node->get('field_location_phone')->value,
            'address' => $address,
            'days' => !empty($days[0]) ? $this->getFormattedHours($days[0]) : [],
          ];
          $tags[] = 'node:' . $node->id();
        }
      }
    }
    $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);

    return $data;
  }

  /**
   * Formats hours data.
   *
   * @param array $data
   *   The array of hours data.
   *
   * @return array
   *   The array with the formatted data.
   */
  protected function getFormattedHours(array $data): array {
    $lazy_hours = $groups = $rows = [];
    foreach ($data as $day => $value) {
      // Do not process label. Store it name for later usage.
      if ($day == 'hours_label') {
        continue;
      }

      $day = str_replace('hours_', '', $day);
      $value = $value ? $value : 'closed';
      $lazy_hours[$day] = $value;
      if ($groups && end($groups)['value'] == $value) {
        $array_keys = array_keys($groups);
        $group = &$groups[end($array_keys)];
        $group['days'][] = $day;
      }
      else {
        $groups[] = [
          'value' => $value,
          'days' => [$day],
        ];
      }
    }

    foreach ($groups as $group_item) {
      $title = sprintf('%s - %s', ucfirst(reset($group_item['days'])), ucfirst(end($group_item['days'])));
      if (count($group_item['days']) == 1) {
        $title = ucfirst(reset($group_item['days']));
      }
      $hours = $group_item['value'];
      $rows[] = [$title . ':', $hours];
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgramsMoreInfo($request): array {
    $time_start = microtime(true);
    $response = $this->http->request('POST', $this->daxkoConfig->get('base_uri') . 'partners/oauth2/token',
      [
        'form_params' => [
          'client_id' => $this->daxkoConfig->get('user'),
          'client_secret' => $this->daxkoConfig->get('pass'),
          'grant_type' => 'client_credentials',
          'scope' => 'client:' . $this->daxkoConfig->get('client_id'),
        ],
        'headers' => [
          'Authorization' => "Bearer " . $this->daxkoConfig->get('referesh_token')
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    $this->logger->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'partners/oauth2/token',
    ]);

    $access_token = json_decode((string) $response->getBody())->access_token;

    $client = new Client(['base_uri' => $this->daxkoConfig->get('base_uri')]);

    $offering_id = $request->get('offering');
    $program_id = $request->get('program');
    $location_id = $request->get('location');
    $log_id = $request->get('log');

    $time_start = microtime(true);
    $response = $client->request('GET', 'programs/' . $program_id . '/offerings/' . $offering_id,
      [
        'query' => ['location_id' => $location_id],
        'headers' => [
          'Authorization' => "Bearer " . $access_token
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    $this->logger->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'programs/' . $program_id . '/offerings/' . $offering_id,
    ]);

    $offeringResponse = json_decode((string) $response->getBody(), TRUE);

    $availability_status = 'closed';
    $availability_note = '';
    $spots_available = '';
    if (isset($offeringResponse['details'][0]['registration_summaries'][0]['description'])) {
      $online_open = $offeringResponse['details'][0]['registration_summaries'][0]['can_register'];
      if ($online_open) {
        $availability_status = 'open';
      }
      $availability_note = $offeringResponse['details'][0]['registration_summaries'][0]['description'];
      $spots_available = $offeringResponse['details'][0]['availability']['available'];
      $limited = $offeringResponse['details'][0]['availability']['limited'];

      // Daxko doesn't provide a number of available spots for unlimited offers.
      // But they should be open for users.
      if ($availability_status === 'open' && !$spots_available && !$limited) {
        $spots_available = static::DEFAULT_NUMBER_OF_SPOTS;
      }

      // If online is closed but offline is open.
      if (!$online_open && isset($offeringResponse['details'][0]['registration_summaries'][1]) && $offeringResponse['details'][0]['registration_summaries'][1]['can_register']) {
        $availability_note .= '. But you can register at the branch. ' . $offeringResponse['details'][0]['registration_summaries'][1]['description'];
      }
    }

    $prices = [];
    if (isset($offeringResponse['details'][0]['groups'])) {
      foreach ($offeringResponse['details'][0]['groups'] as $group) {
        $prices[] = $group['rate']['description'] . ' (' . strtolower($group['name']) . ')';
      }
    }

    // Cache the Price for one day.
    $cache_key = 'daxko-price-' . md5($offering_id . $program_id . $location_id);
    $ttl = $this->time->getRequestTime() + 24 * 60 * 60;
    $this->cache->set($cache_key, implode(', ', $prices), $ttl);

    // Cache the Availability for five minutes.
    $cache_key = 'daxko-availability-' . md5($offering_id . $program_id . $location_id);
    $ttl = $this->time->getRequestTime() + 5 * 60 * 60;
    $this->cache->set($cache_key, ['status' => $availability_status, 'note' => $availability_note, 'spots_available' => $spots_available], $ttl);

    // We show gender restrictions if there are any. So if value is both
    // male and female we do not need to show it as restriction.
    if (isset($offeringResponse['restrictions']['genders']) && count($offeringResponse['restrictions']['genders']) == 1) {
      $gender = reset($offeringResponse['restrictions']['genders']);
      $gender = $gender['name'];
    }

    if (isset($offeringResponse['restrictions']['age'])) {
      $age = $offeringResponse['restrictions']['age']['start'] . '-' . $offeringResponse['restrictions']['age']['end'] . 'yrs';
    }

    // Build register link.
    $register_link = 'https://ops1.operations.daxko.com/Online/' . $this->daxkoConfig->get(
        'client_id'
      ) . '/ProgramsV2/OfferingDetails.mvc?program_id=' . $program_id . '&offering_id=' . $offering_id . '&location_id=' . $location_id;

    $register_link_with_tracking = Url::fromRoute(
      'openy_activity_finder.register_redirect',
      ['log' => $log_id],
      [
        'query' => [
          'url' => $register_link,
          'details' => $offeringResponse['name'] . ' ' . $offeringResponse['program']['name'],
        ]
      ]
    )->toString();

    return [
      'name' => $offeringResponse['name'] . ' ' . $offeringResponse['program']['name'],
      'program_name' => $offeringResponse['program']['name'],
      'description' =>  $offeringResponse['description'] . ' ' . $offeringResponse['program']['description'],
      'price' =>  implode(', ', $prices),
      'availability_status' => $availability_status,
      'availability_note' => $availability_note,
      'activity_type' => '',
      'spots_available' => $spots_available,
      'gender' => $gender ?? '',
      'ages' => $age ?? '',
      'link' =>  $register_link_with_tracking,
    ];
  }

  /**
   * The list of Daxko sorting options.
   *
   * @return array
   *   The array of sorting options.
   */
  public function getSortOptions(): array {
    return [
      'DESC__score' => $this->t('Sort by Relevance'),
      'ASC__name' => $this->t('Sort by Title (A-Z)'),
      'DESC__name' => $this->t('Sort by Title (Z-A)'),
      'ASC__start_date' => $this->t('Sort by Date (Soonest - Latest)'),
      'DESC__start_date' => $this->t('Sort by Date (Latest - Soonest)'),
    ];
  }

}
