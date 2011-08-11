<?php
// $Id: themekey.book.inc,v 1.1.2.2 2009/01/13 21:33:07 profix898 Exp $

function themekey_book_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['book:bid'] = array('description' => t('Book: ID'));
  $attributes['book:has_children'] = array('description' => t('Book: Has Children'));

  return array('attributes' => $attributes);
}
