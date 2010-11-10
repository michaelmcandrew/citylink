<?php
// $Id: themekey.comment.inc,v 1.1.2.2 2009/01/13 21:33:07 profix898 Exp $

function themekey_comment_themekey_properties() {
  // Attributes for properties
  $attributes = array();
  $attributes['comment:cid'] = array('description' => t('Comment: ID'));
  
  // Mapping functions
  $maps = array();
  $maps[] = array('src'       => 'comment:cid',
                  'dst'       => 'node:nid',
                  'callback'  => 'themekey_comment_cid2nid');
  
  return array('attributes' => $attributes, 'maps' => $maps);
}

function themekey_comment_themekey_paths() {
  $paths = array();
  $paths[] = array('path' => 'comment/reply/#node:nid');
  $paths[] = array('path' => 'comment/reply/#node:nid/#comment:cid');
  $paths[] = array('path' => 'comment/edit/#comment:cid');
  $paths[] = array('path' => 'comment/delete/#comment:cid');
  
  return $paths;
}

function themekey_comment_cid2nid($cid) {
  $nid = db_result(db_query('SELECT nid FROM {comments} WHERE cid = %d', $cid));
  return $nid ? $nid : FALSE;
}
