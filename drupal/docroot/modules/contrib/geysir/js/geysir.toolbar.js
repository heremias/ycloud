/**
 * @file
 * Attaches behaviors for the Geysir toolbar.
 */

(function ($, Drupal) {
  var body = $('body');
  var geysir_toolbar = $('.toolbar-icon-geysir');

  // Check if Geysir UI is enabled or not.
  if (localStorage.getItem('Drupal.geysir.toggle') !== null) {
    geysir_toolbar.addClass('is-active');
  }
  else {
    $('div[data-geysir-paragraph-id]').removeClass('geysir-field-paragraph-wrapper');
    body.addClass('geysir-off');
  }

  geysir_toolbar.on('click', function () {
    body.toggleClass('geysir-off');

    // If Geysir UI is disabled, remove corresponding classes and localStorage.
    if (body.hasClass('geysir-off')) {
      $('div[data-geysir-paragraph-id]').removeClass('geysir-field-paragraph-wrapper');
      geysir_toolbar.removeClass('is-active');
      localStorage.removeItem('Drupal.geysir.toggle');
    }
    // If Geysir UI is disabled, add corresponding classes and set localStorage.
    else {
      $('div[data-geysir-paragraph-id]').addClass('geysir-field-paragraph-wrapper');
      geysir_toolbar.addClass('is-active');
      localStorage.setItem('Drupal.geysir.toggle', 'on');
    }
  });
})(jQuery, Drupal);
