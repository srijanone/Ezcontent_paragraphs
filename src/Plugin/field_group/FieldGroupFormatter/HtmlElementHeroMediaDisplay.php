<?php

namespace Drupal\ezcontent_paragraphs\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\Element\HtmlElement as HtmlElementFormatter;
use Drupal\field_group\Plugin\field_group\FieldGroupFormatter\HtmlElement;

/**
 * Plugin implementation of the 'html_element_hero_media_display' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "html_element_hero_media_display",
 *   label = @Translation("HTML element hero media display"),
 *   description = @Translation("This fieldgroup renders the inner content in a
 *   HTML element with classes and attributes."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class HtmlElementHeroMediaDisplay extends HtmlElement {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $paragraph = $rendering_object['#paragraph'];
    $element_attributes = new Attribute();

    // Handle attributes settings and safely add them.
    $this->setElementAttributes($element_attributes);

    // Add the ID and class attributes.
    $this->addIdToAttributes($element_attributes);
    $this->addClassesToAttributes($element_attributes, $paragraph);

    // Apply background color.
    $this->applyBackgroundColor($element_attributes, $paragraph);

    // Handle other settings.
    $element['#effect'] = $this->getSetting('effect');
    $element['#speed'] = $this->getSetting('speed');
    $element['#type'] = 'field_group_html_element';
    $element['#wrapper_element'] = $this->getSetting('element');
    $element['#attributes'] = $element_attributes;
    if ($this->getSetting('show_label')) {
      $element['#title_element'] = $this->getSetting('label_element');
      $element['#title'] = Html::escape($this->getLabel());
    }

    // Initialize form state and process the HTML element.
    $form_state = new FormState();
    HtmlElementFormatter::processHtmlElement($element, $form_state);

    if ($this->getSetting('required_fields')) {
      $element['#attributes']['class'][] = 'field-group-html-element';
      $element['#attached']['library'][] = 'field_group/formatter.html_element';
      $element['#attached']['library'][] = 'field_group/core';
    }
  }

  /**
   * Safely set attributes using a regex pattern.
   */
  private function setElementAttributes(Attribute &$element_attributes) {
    $attributes = $this->getSetting('attributes');
    if (!empty($attributes)) {
      // Safely parse attributes using regex.
      if (@preg_match_all('/([^\s=]+)="([^"]+)"/', $attributes, $matches)) {
        foreach ($matches[1] as $key => $attribute) {
          $element_attributes[$attribute] = $matches[2][$key];
        }
      }
      else {
        \Drupal::logger('ezcontent_paragraphs')->warning('Invalid attribute string: @attributes', ['@attributes' => $attributes]);
      }
    }
  }

  /**
   * Add ID to attributes array.
   */
  private function addIdToAttributes(Attribute &$element_attributes) {
    if ($id = $this->getSetting('id')) {
      $element_attributes['id'] = Html::getId($id);
    }
  }

  /**
   * Add classes to attributes array and merge with other classes.
   */
  private function addClassesToAttributes(Attribute &$element_attributes, $paragraph) {
    $classes = $this->getClasses();
    $classes[] = $paragraph->field_text_position->value;
    if (!empty($classes)) {
      if (!isset($element_attributes['class'])) {
        $element_attributes['class'] = [];
      }
      else {
        // Ensure that classes are merged properly.
        if (is_string($element_attributes['class'])) {
          $element_attributes['class'] = [$element_attributes['class']];
        }
      }
      $element_attributes['class'] = array_merge($element_attributes['class']->value(), $classes);
    }
  }

  /**
   * Apply background color if available, using default if necessary.
   */
  private function applyBackgroundColor(Attribute &$element_attributes, $paragraph) {
    $bg_color_field_config = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->load('paragraph.card.field_text_background_color');

    if ($bg_color_field_config && $paragraph->hasField('field_text_background_color')) {
      $default_value = 'default_value';
      $bg_defaults = $bg_color_field_config->get($default_value);
      $bg = !empty($bg_defaults[0]) ? $bg_defaults[0] : ['color' => '#ffffff', 'opacity' => 1];
      if (!empty($paragraph->get('field_text_background_color')->first())) {
        $bg = $paragraph->get('field_text_background_color')->first()->getValue();
      }

      $hex2rgba = \Drupal::service('ezcontent_paragraphs.hex2rgba');
      $bg_color = $hex2rgba->hex2rgba($bg['color'], $bg['opacity']);
      $element_attributes['style'] = "background-color: " . $bg_color . ";";
    }
    else {
      \Drupal::logger('ezcontent_paragraphs')->warning('Background color field configuration or value missing.');
    }
  }

}
