<?php
// $Id: themekey_ui_helper.inc,v 1.1.2.1 2009/03/29 11:42:47 profix898 Exp $

require_once(drupal_get_path('module', 'themekey') .'/themekey_build.inc');

/**
 * Function themekey_ui_get_node_theme().
 */
function themekey_ui_get_node_theme($nid) {
  return db_result(db_query('SELECT theme FROM {themekey_properties} WHERE property = \'node:nid\' AND value = \'%s\'', $nid));
}

/**
 * Function themekey_ui_set_node_theme().
 */
function themekey_ui_set_node_theme($nid, $theme = 'default') {
  $item = array('property' => 'node:nid', 'value' => $nid, 'theme' => $theme);
  if ($id = db_result(db_query('SELECT id FROM {themekey_properties} WHERE property = \'node:nid\' AND value = \'%s\'', $nid))) {
    $item['id'] = $id;
  }
  _themekey_properties_set($item);
}

/**
 * Function themekey_ui_del_node_theme().
 */
function themekey_ui_del_node_theme($nid) {
  db_query('DELETE FROM {themekey_properties} WHERE property = \'node:nid\' AND value = \'%s\'', $nid);
}

/**
 * Function themekey_ui_get_path_theme().
 */
function themekey_ui_get_path_theme($path) {
  return db_result(db_query('SELECT theme FROM {themekey_paths} WHERE path = \'%s\'', $path));
}

/**
 * Function themekey_ui_set_path_theme().
 */
function themekey_ui_set_path_theme($path, $theme = 'default') {
  $item = array('path' => $path, 'theme' => $theme);
  if ($id = db_result(db_query('SELECT id FROM {themekey_paths} WHERE path = \'%s\'', $path))) {
    $item['id'] = $id;
  }
  _themekey_path_set($item);
}

/**
 * Function themekey_ui_del_path_theme().
 */
function themekey_ui_del_path_theme($path) {
  db_query('DELETE FROM {themekey_paths} WHERE path = \'%s\'', $path);
}
