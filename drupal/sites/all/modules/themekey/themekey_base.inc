<?php
// $Id: themekey_base.inc,v 1.1.2.4 2009/03/29 11:42:47 profix898 Exp $

/**
 * Function _themekey_invoke_modules().
 */
function _themekey_invoke_modules($hook) {
  $return = array();
  foreach (variable_get('themekey_modules', array('themekey.node')) as $module) {
    if (is_readable(drupal_get_path('module', 'themekey') .'/modules/'. $module .'.inc')) {
      require_once(drupal_get_path('module', 'themekey') .'/modules/'. $module .'.inc');
    }
    $function = str_replace('.', '_', $module) .'_'. $hook;
    if (function_exists($function)) {
      $return = array_merge_recursive($return, $function());
    }
  }

  return $return;
}

/* Path-based */

/**
 * Function _themekey_get_path_ancestors().
 * (based on menu_get_ancestors() in includes/menu.inc)
 */
function _themekey_get_path_ancestors($parts) {
  $placeholders = array();
  $ancestors = array();
  
  $number_parts = count($parts);
  $length =  $number_parts - 1;
  $end = (1 << $number_parts) - 1;
  
  $masks = variable_get('menu_masks', array());
  foreach ($masks as $i) {
    if ($i > $end) {
      continue;
    }
    elseif ($i < (1 << $length)) {
      --$length;
    }
    $current = '';
    for ($j = $length; $j >= 0; $j--) {
      if ($i & (1 << $j)) {
        $current .= $parts[$length - $j];
      }
      else {
        $current .= is_numeric($parts[$length - $j]) ? '#' : '%';
      }
      if ($j) {
        $current .= '/';
      }
    }
    $placeholders[] = "'%s'";
    $ancestors[] = $current;
    if (stristr($current, '#') !== FALSE) {
      $placeholders[] = "'%s'";
      $ancestors[] = str_replace('#', '%', $current);
    }
  }
  
  return array($ancestors, $placeholders);
}

/**
 * Function _themekey_match_paths().
 */
function _themekey_match_paths($path) {
  static $global_parameters = NULL;
  //
  if (!isset($global_parameters)) {
    $global_parameters = module_invoke_all('themekey_global');
  }
  //
  $parts = explode('/', $path, MENU_MAX_PARTS);
  list($ancestors, $placeholders) = _themekey_get_path_ancestors($parts);
  //
  $result = db_query('SELECT * FROM {themekey_paths} WHERE path IN ('. implode(',', $placeholders) .') ORDER BY fit DESC, weight DESC, custom DESC', $ancestors);
  while ($item = db_fetch_array($result)) {
    $parameters = $global_parameters;
    $conditions = unserialize($item['conditions']);
    //
    $wildcards = unserialize($item['wildcards']);
    foreach ($wildcards as $index => $wildcard) {
      $parameters[$wildcard] = arg($index, $path);
    }
    //
    if (!_themekey_match_conditions($conditions, $parameters)) {
      continue;
    }
    //
    $callbacks = unserialize($item['callbacks']);
    if (count($callbacks)) {
      foreach ($callbacks as $callback) {
        $callback($item, $parameters);
      }
    }
    //
    if (!$item['theme']) {
      $item['theme'] = _themekey_match_properties($parameters);
    }
    
    return $item['theme'];
  }
  
  return _themekey_match_properties($global_parameters);
}

/* Property-based */

/**
 * Function _themkey_prepare_object().
 */
function _themkey_prepare_object($object) {
  $parameters = array('#raw' => $object);
  $properties = variable_get('themekey_properties', array());
  foreach ($properties as $property => $details) {
    if (($value = _themekey_property_field($object, $details['path'])) != NULL) {
      $parameters[$property] = $value;
    }
  }
  
  return $parameters;
}

/**
 * Function _themekey_property_field().
 */
function _themekey_property_field($value, $path) {
  //
  $parts = explode('/', $path);
  foreach ($parts as $part) {
    if (is_array($value) && isset($value[$part])) {
      $value = (array)$value[$part];
    }
    else {
      return NULL;
    }
  }
  
  return is_array($value) ? array_keys($value) : $value;
}

/**
 * Function _themekey_match_properties().
 */
function _themekey_match_properties($parameters, $object = NULL) {
  //
  if (isset($object)) {
    $parameters = _themkey_prepare_object(array_merge($parameters, (array)drupal_clone($object)));
  }
  //
  $properties = variable_get('themekey_properties', array());
  foreach (array_keys($properties) as $property) {
    $value = _themekey_property_value($parameters, $property);
    if (!empty($value) || count($value)) {
      $query = 'SELECT * FROM {themekey_properties} WHERE property = \''. $property .'\' AND value ';
      $query .= is_array($value) ? ('IN ('. db_placeholders($value, 'varchar') .') ORDER BY weight') : '= \'%s\'';
      $result = db_query($query, $value);
      while ($item = db_fetch_array($result)) {
        //
        $item['conditions'] = unserialize($item['conditions']);
        if (_themekey_match_conditions($item['conditions'], $parameters)) {
          //
          $callbacks = unserialize($item['callbacks']);
          if (is_array($callbacks) && count($callbacks)) {
            foreach ($callbacks as $callback) {
              $callback($item, $parameters);
            }
          }
          //
          return $item['theme'];
        }
      }
    }
  }
  
  return NULL;
}

/**
 * Function _themekey_match_conditions().
 */
function _themekey_match_conditions($conditions, $parameters) {
  if (is_array($conditions) && count($conditions)) {
    foreach ($conditions as $condition) {
      //
      $value = _themekey_property_value($parameters, $condition['property']);
      if (is_array($value)) {
        //
        if (!in_array($condition['value'], $value)) {
          return FALSE;
        }
      }
      else {
        // Default operator is 'equal'
        if (!isset($condition['operator'])) {
          $condition['operator'] = '=';
        }
        // Supported operators for condition check:
        // smaller ('<'), greater ('>'), equal ('='), not equal ('!')
        if ($condition['operator'] == '<' && $value >= $condition['value']) {
          return FALSE;
        }
        else if ($condition['operator'] == '>' && $value <= $condition['value']) {
          return FALSE;
        }
        else if ($condition['operator'] == '=' && $value != $condition['value']) {
          return FALSE;
        }
        else if ($condition['operator'] == '!' && $value == $condition['value']) {
          return FALSE;
        }
      }
    }
  }

  return TRUE;
}

/**
 * Function _themekey_property_value().
 */
function _themekey_property_value($parameters, $property) {
  // Property value is available directly
  if (isset($parameters[$property])) {
    return $parameters[$property];
  }
  
  // Try to get property value utilizing mapped properties
  $nidmap = array('src' => FALSE, 'dst' => FALSE);
  $uidmap = array('src' => FALSE, 'dst' => FALSE);
  $maps = variable_get('themekey_maps', array());
  foreach ($maps as $pos => $map) {
    if (isset($parameters[$map['src']]) && $map['dst'] == 'node:nid') {
      $nidmap['src'] = $pos;
    }
    if (isset($parameters[$map['src']]) && $map['dst'] == 'user:uid') {
      $uidmap['src'] = $pos;
    }
    if ($map['src'] == 'node:nid' && $map['dst'] == $property) {
      $nidmap['dst'] = $pos;
    }
    if ($map['src'] == 'user:uid' && $map['dst'] == $property) {
      $uidmap['dst'] = $pos;
    }
    if (isset($parameters[$map['src']]) && $map['dst'] == $property) {
      $map_func = $map['callback'];
      return $map_func($parameters[$map['src']], $parameters);
    }
  }
  // Support mapping via node id (src -> node:nid -> dst)
  if ($nidmap['src'] !== FALSE && $nidmap['dst'] !== FALSE) {
    $src_func = $maps[$nidmap['src']]['callback'];
    $dst_func = $maps[$nidmap['dst']]['callback'];
    return $dst_func($src_func($parameters[$maps[$nidmap['src']]['src']], $parameters), $parameters);
  }
  // Support mapping via user id (src -> user:uid -> dst)
  if ($uidmap['src'] !== FALSE && $uidmap['dst'] !== FALSE) {
    $src_func = $maps[$uidmap['src']]['callback'];
    $dst_func = $maps[$uidmap['dst']]['callback'];
    return $dst_func($src_func($parameters[$maps[$uidmap['src']]['src']], $parameters), $parameters);
  }

  return NULL;
}
