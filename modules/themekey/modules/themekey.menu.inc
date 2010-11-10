<?php
// $Id: themekey.menu.inc,v 1.1.2.2 2009/01/13 21:33:07 profix898 Exp $

function themekey_menu_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['menu:has_children'] = array('description' => t('Menu: Has Children'));
  $attributes['menu:menu_name'] = array('description' => t('Menu: Menu Name'));
  $attributes['menu:mlid'] = array('description' => t('Menu: Menu Link ID'));

  return array('attributes' => $attributes);
}
