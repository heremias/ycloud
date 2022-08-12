<?php

namespace Drupal\openy_node_alert\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;


/**
 * Class AlertManager.
 *
 * @package Drupal\openy_node_alert\Service
 */
class AlertManager {

  /**
   * The alert builders array.
   *
   * @var \Drupal\openy_node_alert\Service\AlertBuilderInterface[]
   */
  protected $alerts = [];

  /**
   * An array with sorted alerts builders by priority, NULL otherwise.
   *
   * @var null|array
   */
  protected $alertsSorted = NULL;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The Path Matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs the Alert manager.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    AliasManagerInterface $alias_manager,
    PathMatcherInterface $path_matcher
  ) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->currentUser = $current_user;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Adds alerts builders to internal service storage.
   *
   * @param \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert
   *   The alert service.
   * @param int $priority
   *   The service priority.
   */
  public function addBuilder(AlertBuilderInterface $alert, $priority = 0) {
    $this->alerts[$priority][] = $alert;
    // Reset sorted status to be resorted on next call.
    $this->alertsSorted = NULL;
  }

  /**
   * Sorts alerts services.
   *
   * @return \Drupal\openy_node_alert\Service\AlertBuilderInterface[]
   *   The sorted services.
   */
  protected function sortAlerts() {
    $sorted = [];
    krsort($this->alerts);

    foreach ($this->alerts as $alerts) {
      $sorted = array_merge($sorted, $alerts);
    }

    return $sorted;
  }

  /**
   * Gets all alerts from services.
   *
   * @param EntityInterface $node
   *  Node to retrieve referenced alerts.
   * @return array
   *   The array alert ids to display on page.
   */
  public function getServiceAlerts(EntityInterface $node) {
    if (!$this->alertsSorted) {
      $this->alertsSorted = $this->sortAlerts();
    }
    // Get alerts without location assigned.
    $query = $this->nodeStorage->getQuery()
      ->condition('type', 'alert')
      ->condition('status', 1)
      ->notExists('field_alert_location');
    $alerts = $query->execute();

    // Get alerts from services.
    /** @var \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert_service */
    foreach ($this->alertsSorted as $alert_service) {
      if (!$alert_service->applies($node)) {
        // The service does not apply, so we continue with the other services.
        continue;
      }
      $alerts = array_merge($alerts, $alert_service->build($node));
    }
    return $alerts;
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \HttpException
   */
  public function getAlerts(EntityInterface $node) {
    // Get data from draggableviews_structure table.
    $query = \Drupal::database()->select('draggableviews_structure', 'dvs');
    $query->fields('dvs', ['view_name', 'view_display', 'entity_id', 'weight']);
    $query->condition('dvs.view_name', 'alerts_rearrange');
    $query->condition('dvs.view_display', 'page_1');
    $query->orderBy('dvs.weight');
    $weights = $query->execute()->fetchAll();
    $loadByProperties = ['type' => 'alert', 'status' => 1];

    $alerts_entities = $this->nodeStorage
      ->loadByProperties($loadByProperties);

    $alerts = $alerts_entities;

    // Sort alert based on draggable_views data.
    if (!empty($weights)) {
      $alerts = [];
      foreach ($weights as $row) {
        if (!isset($alerts_entities[(int)$row->entity_id])) {
          continue;
        }
        $alerts[] = $alerts_entities[(int)$row->entity_id];
        unset($alerts_entities[(int)$row->entity_id]);
      }
      // Add items that was missed in draggableviews table.
      $alerts = array_merge($alerts, $alerts_entities);
    }

    $service_alert_ids = $this->getServiceAlerts($node);
    $sendAlerts = [];
    /** @var \Drupal\node\Entity\Node $alert */
    foreach ($alerts as $alert) {
      // Filter alerts to remove alerts not listed by alert service for this uri.
      if (!empty($service_alert_ids) && !in_array($alert->id(), $service_alert_ids)) {
        continue;
      }
      if (!$alert->hasField('field_alert_visibility_pages')) {
        if ($alert->hasField('field_alert_belongs') && !$alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
          $refid = $alert->field_alert_belongs->target_id;
          $alias = $this->aliasManager->getAliasByPath('/node/' . $refid);
          if ($_GET['uri'] != $alias) {
            // Do not show alerts for current page.
            continue;
          }
          $sendAlerts[$alert->field_alert_place->value]['local'][] = $this->formatAlert($alert);
        }
        elseif ($alert->hasField('field_alert_belongs') && $alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
          $sendAlerts[$alert->field_alert_place->value]['global'][] = $this->formatAlert($alert);
        }
        else {
          throw new \HttpException('Field configuration for alerts is wrong');
        }
      }
      elseif ($this->checkVisibility($alert, $node)) {
        $sendAlerts[$alert->field_alert_place->value]['local'][] = $this->formatAlert($alert);
      }
      else {
        if ($alert->hasField('field_alert_location') && !$alert->field_alert_location->isEmpty()) {
          $sendAlerts[$alert->field_alert_place->value]['local'][] = $this->formatAlert($alert);
        }
      }
    }

    return [$sendAlerts, $alerts];
  }

  /**
   * Helper function for alerts formatting.
   *
   * @param \Drupal\node\NodeInterface $alert
   *   Alert node.
   *
   * @return array
   *   Formatted alert.
   */
  public function formatAlert(NodeInterface $alert) {
    $url = $alert->field_alert_link->uri != NULL ? Url::fromUri($alert->field_alert_link->uri)
      ->setAbsolute()
      ->toString() : NULL;

    $iconColor = '';
    if ($alert->field_alert_icon_color && $alert->field_alert_icon_color->entity && $alert->field_alert_icon_color->entity->field_color && $alert->field_alert_icon_color->entity->field_color->value) {
      $iconColor = $alert->field_alert_icon_color->entity->field_color->value;
    }

    return [
      'title' => $alert->getTitle(),
      'textColor' => $alert->field_alert_text_color->entity->field_color->value,
      'bgColor' => $alert->field_alert_color->entity->field_color->value,
      'description' => $alert->field_alert_description->value,
      'iconColor' => $iconColor,
      'linkUrl' => $url,
      'linkText' => $alert->field_alert_link->title,
      'id' => $alert->id(),
    ];
  }

  /**
   * Check visibility of alert.
   *
   * @param \Drupal\node\NodeInterface $alert
   *   Alert node.
   *
   * @return bool
   *   Visibility status, TRUE if visible.
   */
  private function checkVisibility(NodeInterface $alert, NodeInterface $node) {
    $visibility_paths = '';
    if ($alert->hasField('field_alert_visibility_pages')) {
      $field = $alert->get('field_alert_visibility_pages');
      if (!$field->isEmpty()) {
        $visibility_paths = $field->getString();
      }
    }

    $state = 'include';
    if ($alert->hasField('field_alert_visibility_state')) {
      $field = $alert->get('field_alert_visibility_state');
      if (!$field->isEmpty()) {
        $state = $field->getString();
      }
    }

    if (empty($visibility_paths)) {
      // Global alert.
      return TRUE;
    }

    $visibility_paths = mb_strtolower($visibility_paths);
    $pages = preg_split("(\r\n?|\n)", $visibility_paths);

    // Convert path to lowercase. This allows comparison of the same path.
    // with different case. Ex: /Page, /page, /PAGE.
    // Compare the lowercase path alias (if any) and internal path.
    $current_path = $this->aliasManager->getAliasByPath('/node/'.$node->id());
    $current_path = $current_path ? mb_strtolower($current_path) : $current_path;
    // Check all values from the field "alert_visibility_pages".
    foreach ($pages as $page) {
      $page_path = ($page === '<front>' || $page === '/') ? '<front>' : '/' . ltrim($page, '/');
      $is_path_match = $this->pathMatcher->matchPath($current_path, $page_path);
      if ($is_path_match) {
        // If this path matches at least one of the visibility pages,
        // we return TRUE if we want to include the alert on this page.
        // Hide the alert on the given page otherwise.
        return $state === 'include';
      }
    }

    // If the code got to this point, then no path matches were found. This
    // means we have to hide the alert (return FALSE) if it has "include"
    // visibility state. And show the alert (return TRUE) with the "exclude"
    // state.
    return $state !== 'include';
  }
}
