<?php

namespace Drupal\Tests\tzfield\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for time zone field module.
 *
 * @group tzfield
 */
class TimeZoneFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'node', 'tzfield'];

  /**
   * Functional tests for tzfield.
   */
  public function testTimeZoneField() {
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $adminUser = $this->drupalCreateUser([
      'administer node fields',
      'administer node form display',
      'create article content',
    ]);
    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'tzfield',
      'label' => 'Time zone',
      'field_name' => 'time_zone',
    ], 'Save and continue');
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->submitForm(['fields[field_time_zone][type]' => 'tzfield_offset'], 'Save');
    $this->drupalGet('node/add/article');
    $option = $this->assertSession()->selectExists('edit-field-time-zone-0-value')->find('css', 'option[value=UTC]');
    $this->assertSame('(UTC+00:00) UTC', $option->getText());
  }

}
