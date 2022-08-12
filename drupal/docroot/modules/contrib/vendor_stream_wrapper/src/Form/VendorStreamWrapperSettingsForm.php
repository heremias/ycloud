<?php

namespace Drupal\vendor_stream_wrapper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the form to configure the Vendor Stream Wrapper module.
 */
class VendorStreamWrapperSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vendor_stream_wrapper_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vendor_stream_wrapper.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vendor_stream_wrapper.settings');

    $form['allowed_file_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed file patterns'),
      '#description' => $this->t('Use patterns to indicate which files are allowed to be downloaded. One pattern per line. You can use wildcards. For example, the pattern <em>foo/bar/css/*.css</em> allows all .css files to be retrieved/downloaded from the foo/bar vendor package.'),
      '#default_value' => implode("\n", $config->get('allowed_file_patterns') ?? []),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vendor_stream_wrapper.settings');

    // Get the file patterns entered by the user.
    $allowed_file_patterns = $form_state->getValue('allowed_file_patterns');

    // Transform the text to an array of patterns.
    $allowed_file_patterns = explode("\n", $allowed_file_patterns);

    // Filter out illegal characters.
    $allowed_file_patterns = array_filter(preg_replace('/[^a-zA-Z0-9_\/\.\-\*]+/', '', $allowed_file_patterns));

    // Store the file patterns in the configuration.
    $config->set('allowed_file_patterns', $allowed_file_patterns)->save();

    parent::submitForm($form, $form_state);
  }

}
