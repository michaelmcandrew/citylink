<?php
// $Id: themekey.system.inc,v 1.1.2.1 2009/03/29 11:42:47 profix898 Exp $

function themekey_system_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['system:host'] = array(
    'path' => 'system:host',
    'description' => t('System: Server Host')
  );
  
  return array('attributes' => $attributes);
}

function themekey_system_themekey_global() {
  global $user;
  
  $parameters = array();
  $parameters['system:host'] = $_SERVER['HTTP_HOST'];
  
  return $parameters;
}
