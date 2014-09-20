<?php

/**
 * Papi Property functions.
 *
 * @package Papi
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Check if it's ends with '_property'.
 *
 * @param string $str
 * @since 1.0
 *
 * @return integer
 */

function _papi_is_property_type_key ($str = '') {
  $pattern = PAPI_PROPERTY_TYPE_KEY;
  $pattern = str_replace('_', '\_', $pattern);
  $pattern = str_replace('-', '\-', $pattern);
  $pattern = '/' . $pattern . '$/';
  return preg_match($pattern, $str);
}

/**
 * Get the right key for a property type.
 *
 * @param string $str
 * @since 1.0
 *
 * @return string
 */

function _papi_property_type_key ($str = '') {
  return $str . PAPI_PROPERTY_TYPE_KEY;
}

/**
 * Get property key.
 *
 * @param string $str
 * @since 1.0.0
 *
 * @return string
 */

function _papi_property_key ($str) {
  return _papi_f(_papify($str));
}

/**
 * Returns only values in the array and removes `{x}_property` key and value.
 *
 * @param array $a
 * @since 1.0
 *
 * @return array
 */

function _papi_get_only_property_values ($a = array()) {
  foreach ($a as $key => $value) {
    if (_papi_is_property_type_key($key)) {
      unset($a[$key]);
    }
  }

  return $a;
}

/**
 * Get property type by the given type.
 *
 * @param string $type
 * @since 1.0.0
 *
 * @return object|null
 */

function _papi_get_property_type ($type) {
  if (is_object($type) && isset($type->type) && is_string($type->type)) {
    $type = $type->type;
  }

  if (empty($type)) {
    return null;
  }

  return Papi_Property::factory($type);
}

/**
 * Get property options.
 *
 * @param array $options
 * @since 1.0.0
 *
 * @return object
 */

function _papi_get_property_options ($options) {
  $defaults = array(
    'title'         => '',
    'no_title'      => false,
    'disabled'      => false,
    'slug'          => '',
    'settings'      => array(),
    'sort_order'    => null,
    'value'         => '',
    'type'          => '',
    'colspan'       => '',
    'lang'          => '',
    'old_slug'      => '',
    'default'       => '',
    'capabilities' => array()
  );

  $options = array_merge($defaults, $options);
  $options = (object)$options;

  if ($options->no_title) {
    $options->title = '';
    $options->colspan = 2;
  }

  // This fixes so you can use "Text" as type and hasn't to write "PropertyText".
  if (!preg_match('/^Property/', $options->type)) {
    $options->type = 'Property' . ucfirst(strtolower($options->type));
  }

  if (empty($options->slug)) {
    $options->slug = _papi_slugify($options->title);
  }

  if (!empty($options->old_slug)) {
    $options->old_slug = _papi_name($options->old_slug);
  }

  // Generate colspan attribute
  if (!empty($options->colspan)) {
    $options->colspan = _papi_attribute('colspan', $options->colspan);
  }

  // Generate a vaild Papi meta name.
  $options->slug = _papi_name($options->slug);

  // Get meta value for the field
  $options->value = papi_field($options->slug, null, null, $options->old_slug);

  // Add default value if database value is empty.
  if (empty($options->value)) {
    $options->value = $options->default;
  }

  return $options;
}

/**
 * Render a property the right way.
 *
 * @param object $property
 * @since 1.0.0
 */

function _papi_render_property ($property) {
  // Check so type isn't empty and capabilities on the property.
  if (empty($property->type) || !_papi_current_user_is_allowed($property->capabilities)) {
    return;
  }

  $property_type = _papi_get_property_type($property->type);

  if (is_null($property_type)) {
    return;
  }

  $property_type->set_options($property);

  // Only render if it's the right language if the definition exist.
  if (_papi_get_qs('lang') != null) {
    $render = $property->lang === strtolower(_papi_get_qs('lang'));
  } else {
    $render = true;
  }

  // Render the property.
  if ($render) {
    $property_type->assets();
    $property_type->render();
    $property_type->hidden();
  }
}

/**
 * Render properties the right way.
 *
 * @param array $properties
 * @since 1.0.0
 */

function _papi_render_properties ($properties) {
  // Don't proceed without any properties
  if (!is_array($properties) || empty($properties)) {
    return;
  }

  // If it's a tab the tabs class will
  // handle the rendering of the properties.
  if (isset($properties[0]->tab) && $properties[0]->tab) {
    new Papi_Admin_Meta_Box_Tabs($properties);
  } else {
    // Sort properties based on `sort_order` value.
    $properties = _papi_sort_order($properties);

    echo '<table class="papi-table">';
      echo '<tbody>';

    foreach ($properties as $property) {
      _papi_render_property($property);
    }

      echo '</tbody>';
    echo '</table>';
  }
}
