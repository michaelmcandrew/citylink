<?php
// $Id: themekey.user.inc,v 1.1.2.2 2009/01/13 21:33:07 profix898 Exp $

function themekey_user_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['user:hostname'] = array(
    'path' => 'user:hostname',
    'description' => t('User: Hostname')
  );
  $attributes['user:language'] = array(
    'path' => 'user:language',
    'description' => t('User: Language')
  );
  $attributes['user:name'] = array(
    'path' => 'user:name',
    'description' => t('User: Name')
  );
  $attributes['user:uid'] = array(
    'path' => 'user:uid',
    'description' => t('User: ID')
  );
  
  return array('attributes' => $attributes);
}

function themekey_user_themekey_global() {
  global $user;
  
  $parameters = array();
  $parameters['user:hostname'] = isset($user->hostname) ? $user->hostname : '';
  $parameters['user:language'] = isset($user->language) ? $user->language : 'en';
  $parameters['user:name'] = isset($user->name) ? $user->name : '';
  $parameters['user:uid'] = $user->uid;
  
  return $parameters;
}
