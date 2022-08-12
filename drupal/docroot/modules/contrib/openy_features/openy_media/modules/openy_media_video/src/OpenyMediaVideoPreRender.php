<?php

namespace Drupal\openy_media_video;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Implements prerender callbacks for the openy_media_video module.
 *
 * @internal
 */
class OpenyMediaVideoPreRender implements RenderCallbackInterface {

  /**
   * Prerender callback for change default value of frameborder attribute.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the frameborder attribute.
   */
  public static function videoEmbedIframePrerender(array $element) {
    if (isset($element['#attributes']['frameborder'])) {
      $element['#attributes']['frameborder'] = 1;
    }
    return $element;
  }

}
