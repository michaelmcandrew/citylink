<?php
// $Id: themekey.node.inc,v 1.1.2.3 2009/03/29 11:55:14 profix898 Exp $

function themekey_node_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['node:changed'] = array('description' => t('Node: Changed date'));
  $attributes['node:created'] = array('description' => t('Node: Created date'));
  $attributes['node:language'] = array('description' => t('Node: Language'));
  $attributes['node:nid'] = array('description' => t('Node: ID'));
  $attributes['node:promote'] = array('description' => t('Node: Promoted'));
  $attributes['node:sticky'] = array('description' => t('Node: Sticky'));
  $attributes['node:type'] = array('description' => t('Node: Type'));
  $attributes['node:uid'] = array('description' => t('Node: User ID'));

  return array('attributes' => $attributes);
}

function themekey_node_themekey_paths() {
  $paths = array();
  $paths[] = array('path'       => 'node/#node:nid',
                   'callbacks'  => array('_themekey_node_callback'));
  
  return $paths;
}

function _themekey_node_callback(&$item, $parameters) {
  if ($node = node_load($parameters['node:nid'])) {
    if ($theme = _themekey_match_properties($parameters, $node)) {
      $item['theme'] = $theme;
    }
  }
}
