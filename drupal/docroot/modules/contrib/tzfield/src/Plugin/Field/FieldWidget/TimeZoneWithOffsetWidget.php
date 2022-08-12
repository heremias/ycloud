<?php

namespace Drupal\tzfield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the time zone with offset widget.
 *
 * @FieldWidget(
 *   id = "tzfield_offset",
 *   label = @Translation("Time zone with current offset"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 */
class TimeZoneWithOffsetWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => $this->getTimezonesList(!$element['#required']),
      '#default_value' => $items[$delta]->value ?? NULL,
    ];
    return $element;
  }

  /**
   * Get sorted list of timezones with offsets.
   *
   * @param bool $blank
   *   Whether to include empty timezone to the list.
   *
   * @return array
   *   Array of timezones with offsets sorted by offsets.
   */
  public function getTimezonesList(bool $blank): array {
    // Get offsets for timezones.
    $offsets = [];
    $now = new \DateTime();
    foreach (\DateTimeZone::listIdentifiers() as $timezone) {
      $tz = new \DateTimeZone($timezone);
      $offsets[$timezone] = $tz->getOffset($now);
    }

    // Sort timezones by offset.
    asort($offsets);

    $timezone_list = $blank ? ['' => $this->t('- None selected -')] : [];
    foreach ($offsets as $timezone => $offset) {
      $timezone_list[$timezone] = $this->t('(UTC@offset_prefix@offset_formatted) @zone', [
        '@offset_prefix' => $offset < 0 ? '-' : '+',
        '@offset_formatted' => gmdate('H:i', abs($offset)),
        '@zone' => str_replace('_', ' ', $timezone),
      ]);
    }

    return $timezone_list;
  }

}
