<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 2.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2009                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2009
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';
require_once 'CRM/Import/Parser/Contact.php';

/**
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Import_Form_Preview extends CRM_Core_Form {

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess()
    {
        //get the data from the session             
        $dataValues         = $this->get('dataValues');
        $mapper             = $this->get('mapper');
        $invalidRowCount    = $this->get('invalidRowCount');
        $conflictRowCount   = $this->get('conflictRowCount');
        $mismatchCount      = $this->get('unMatchCount');
        $columnNames        = $this->get('columnNames');
        
        //assign column names
        $this->assign( 'columnNames', $columnNames );
        
        //get the mapping name displayed if the mappingId is set
        $mappingId = $this->get('loadMappingId');
        if ( $mappingId ) {
            $mapDAO =& new CRM_Core_DAO_Mapping();
            $mapDAO->id = $mappingId;
            $mapDAO->find( true );
            $this->assign('loadedMapping', $mappingId);
            $this->assign('savedName', $mapDAO->name);
        }

        $this->assign( 'rowDisplayCount', 2 );
        
        $groups =& CRM_Core_PseudoConstant::group();
        $this->set('groups', $groups);
        
        $tag =& CRM_Core_PseudoConstant::tag();
        if ($tag) {
            $this->set('tag', $tag);
        }
        
        if ($invalidRowCount) {
            $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=1'));
        }

        if ($conflictRowCount) {
            $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=2'));
        }
        
        if ($mismatchCount) {
            $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=4'));
        }

        
        $properties = array( 'mapper', 'locations', 'phones',
                             'dataValues', 'columnCount',
                             'totalRowCount', 'validRowCount', 
                             'invalidRowCount', 'conflictRowCount',
                             'downloadErrorRecordsUrl',
                             'downloadConflictRecordsUrl',
                             'downloadMismatchRecordsUrl',
                             'related', 'relatedContactDetails', 'relatedContactLocType',
                             'relatedContactPhoneType'
                    );
                             
        foreach ( $properties as $property ) {
            $this->assign( $property, $this->get( $property ) );
        }
        
        $statusID = $this->get( 'statusID' );
        if ( ! $statusID ) {
            $statusID = md5(uniqid(rand(), true));
            $this->set( 'statusID', $statusID );
        }
        $this->assign('statusID', $statusID );
        
        $showColNames = true;
        if ( 'CRM_Import_DataSource_CSV' == $this->get( 'dataSource' ) && 
             !$this->get( 'skipColumnHeader' ) ) {
            $showColNames = false;
        }
        $this->assign( 'showColNames', $showColNames );
    }

    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) {
        $this->addElement( 'text', 'newGroupName', ts('Name for new group'));
        $this->addElement( 'text', 'newGroupDesc', ts('Description of new group'));
        $this->addRule( 'newGroupName',
                        ts('Name already exists in Database.'),
                        'objectExists',
                        array( 'CRM_Contact_DAO_Group', null, 'title' ) );

        $groups =& $this->get('groups');
        
        if ( ! empty( $groups ) ) {
            $this->addElement( 'select', 'groups', ts('Add imported records to existing group(s)'), $groups, array('multiple' => "multiple", 'size' => 5));
        }

        //display new tag
        $this->addElement( 'text', 'newTagName', ts('Tag'));
        $this->addElement( 'text', 'newTagDesc', ts('Description'));
        $this->addFormRule(array('CRM_Import_Form_Preview','newTagRule'));    
    
        $tag =& $this->get('tag');
        if (! empty($tag) ) {
            foreach ($tag as $tagID => $tagName) {
                $this->addElement('checkbox', "tag[$tagID]", null, $tagName);
            }
        }
        
        $previousURL = CRM_Utils_System::url('civicrm/import/contact', '_qf_MapField_display=true');
        $cancelURL   = CRM_Utils_System::url('civicrm/import/contact', 'reset=1');
        
        $buttons = array(
                         array ( 'type'      => 'back',
                                 'name'      => ts('<< Previous'),
                                 'js'        => array( 'onclick' => "location.href='{$previousURL}'; return false;" ) ),
                         array ( 'type'      => 'next',
                                 'name'      => ts('Import Now >>'),
                                 'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                 'isDefault' => true,
                                 'js'        => array( 'onclick' => "return verify( );" )

                                 ),
                         array ( 'type'      => 'cancel',
                                 'name'      => ts('Cancel'),
                                 'js'        => array( 'onclick' => "location.href='{$cancelURL}'; return false;" ) ),
                         );
        
        $this->addButtons( $buttons );
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle( ) {
        return ts('Preview');
    }

    /**
     * Process the mapped fields and map it into the uploaded file
     * preview the file and extract some summary statistics
     *
     * @return void
     * @access public
     */
    public function postProcess( ) {

       $importJobParams = array(
            'doGeocodeAddress'  => $this->controller->exportValue( 'DataSource', 'doGeocodeAddress' ),
            'invalidRowCount'   => $this->get('invalidRowCount'),
            'conflictRowCount'  => $this->get('conflictRowCount'),
            'onDuplicate'       => $this->get('onDuplicate'),
            'newGroupName'      => $this->controller->exportValue( $this->_name, 'newGroupName'),
            'newGroupDesc'      => $this->controller->exportValue( $this->_name, 'newGroupDesc'),
            'groups'            => $this->controller->exportValue( $this->_name, 'groups'),
            'allGroups'         => $this->get('groups'),
            'newTagName'        => $this->controller->exportValue( $this->_name, 'newTagName'),
            'newTagDesc'        => $this->controller->exportValue( $this->_name, 'newTagDesc'),
            'tag'               => $this->controller->exportValue( $this->_name, 'tag'),
            'allTags'           => $this->get('tag'),
            'mapper'            => $this->controller->exportValue( 'MapField', 'mapper' ),
            'mapFields'         => $this->get('fields'),
            'contactType'       => $this->get('contactType'),
            'primaryKeyName'    => $this->get('primaryKeyName'),
            'statusFieldName'   => $this->get('statusFieldName'),
            'statusID'          => $this->get('statusID'),
            'totalRowCount'     => $this->get('totalRowCount')
        );
        
        $tableName = $this->get( 'importTableName' );
        require_once 'CRM/Import/ImportJob.php';
        $importJob = new CRM_Import_ImportJob( $tableName );
        $importJob->setJobParams( $importJobParams );
               
        // run the import
        $importJob->runImport($this);
               
        // add all the necessary variables to the form
        $importJob->setFormVariables( $this );
        
        // check if there is any error occured
        $errorStack =& CRM_Core_Error::singleton();
        $errors     = $errorStack->getErrors();
        
        $errorMessage = array();
       
        if( is_array( $errors ) ) {
            foreach($errors as $key => $value) {
                $errorMessage[] = $value['message'];
            }
    
            // there is no fileName since this is a sql import
            // so fudge it
            $config =& CRM_Core_Config::singleton( );
            $errorFile =$config->uploadDir . "sqlImport.error.log"; 
            if ( $fd = fopen( $errorFile, 'w' ) ) {
                fwrite($fd, implode('\n', $errorMessage));
            }
            fclose($fd);
            
            $this->set('errorFile', $errorFile);
            $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=1'));
            $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=2'));
            $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=4'));
        }
        
        //hack to clean db
        //if job complete drop table.
        $importJob->isComplete( true );
    }

    /**
     * Process the mapped fields and map it into the uploaded file
     * preview the file and extract some summary statistics
     *
     * @return void
     * @access public
     */
    public function postProcessOld( ) {
       
        $doGeocodeAddress   = $this->controller->exportValue( 'DataSource', 'doGeocodeAddress' );
        $invalidRowCount    = $this->get('invalidRowCount');
        $conflictRowCount   = $this->get('conflictRowCount');
        $onDuplicate        = $this->get('onDuplicate');
        $newGroupName       = $this->controller->exportValue( $this->_name, 'newGroupName');
        $newGroupDesc       = $this->controller->exportValue( $this->_name, 'newGroupDesc');
        $groups             = $this->controller->exportValue( $this->_name, 'groups');
        $allGroups          = $this->get('groups');
        $newTagName         = $this->controller->exportValue( $this->_name, 'newTagName');
        $newTagDesc         = $this->controller->exportValue( $this->_name, 'newTagDesc');
        $tag                = $this->controller->exportValue( $this->_name, 'tag');
        $allTags            = $this->get('tag');
        
        $mapper = $this->controller->exportValue( 'MapField', 'mapper' );
        
        $mapperKeys = array();
        $mapperLocTypes = array();
        $mapperPhoneTypes = array();
        $mapperRelated = array();
        $mapperRelatedContactType = array();
        $mapperRelatedContactDetails = array();
        $mapperRelatedContactLocType = array();
        $mapperRelatedContactPhoneType = array();
        
        foreach ($mapper as $key => $value) {
            $mapperKeys[$key] = $mapper[$key][0];
            if (is_numeric($mapper[$key][1])) {
                $mapperLocTypes[$key] = $mapper[$key][1];
            } else {
                $mapperLocTypes[$key] = null;
            }
            
            if ( CRM_Utils_Array::value($key,$mapperKeys) == 'phone' ) {
                $mapperPhoneTypes[$key] = $mapper[$key][2];
            } else {
                $mapperPhoneTypes[$key] = null;
            }

            list($id, $first, $second) = explode('_', $mapper[$key][0]);
            if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                $relationType =& new CRM_Contact_DAO_RelationshipType();
                $relationType->id = $id;
                $relationType->find(true);
                eval( '$mapperRelatedContactType[$key] = $relationType->contact_type_'.$second.';');
                $mapperRelated[$key] = $mapper[$key][0];
                $mapperRelatedContactDetails[$key] = $mapper[$key][1];
                $mapperRelatedContactLocType[$key] = $mapper[$key][2];
                $mapperRelatedContactPhoneType[$key] = $mapper[$key][3];
            } else {
                $mapperRelated[$key] = null;
                $mapperRelatedContactType[$key] = null;
                $mapperRelatedContactDetails[$key] = null;
                $mapperRelatedContactLocType[$key] = null;
                $mapperRelatedContactPhoneType[$key] = null;
            }
        }
        
        $parser =& new CRM_Import_Parser_Contact( $mapperKeys, $mapperLocTypes,
                                                  $mapperPhoneTypes, $mapperRelated, $mapperRelatedContactType,
                                                  $mapperRelatedContactDetails, $mapperRelatedContactLocType, 
                                                  $mapperRelatedContactPhoneType);
        
        $mapFields = $this->get('fields');
      
        $locationTypes  = CRM_Core_PseudoConstant::locationType();
        $phoneTypes = CRM_Core_PseudoConstant::phoneType();
        
        foreach ($mapper as $key => $value) {
            $header = array();
            list($id, $first, $second) = explode('_', $mapper[$key][0]);
            if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                $relationType =& new CRM_Contact_DAO_RelationshipType();
                $relationType->id = $id;
                $relationType->find(true);
                
                $header[] = $relationType->name_a_b;
                $header[] = ucwords(str_replace("_", " ", $mapper[$key][1]));
                
                if ( isset($mapper[$key][2]) ) {
                    $header[] = $locationTypes[$mapper[$key][2]];
                }
                if ( isset($mapper[$key][3]) ) {
                    $header[] = $phoneTypes[$mapper[$key][3]];
                }
                
            } else {
                if ( isset($mapFields[$mapper[$key][0]]) ) {
                    $header[] = $mapFields[$mapper[$key][0]];
                    if ( isset($mapper[$key][1]) ) {
                        $header[] = $locationTypes[$mapper[$key][1]];
                    }
                    if ( isset($mapper[$key][2]) ) {
                        $header[] = $phoneTypes[$mapper[$key][2]];
                    }
                }
            }            
            $mapperFields[] = implode(' - ', $header);
        }
        
        $tableName = $this->get( 'importTableName' );
        //print "Running parser on table: $tableName<br/>";
        $parser->run( $tableName, $mapperFields,
                      CRM_Import_Parser::MODE_IMPORT,
                      $this->get('contactType'),
                      $this->get('primaryKeyName'),
                      $this->get('statusFieldName'),
                      $onDuplicate,
                      $this->get( 'statusID' ),
                      $this->get( 'totalRowCount' ),
                      $doGeocodeAddress );
        
        // add the new contacts to selected groups
        $contactIds =& $parser->getImportedContacts();
      
        // add the new related contacts to selected groups
        $relatedContactIds =& $parser->getRelatedImportedContacts();
        
        $this->set('relatedCount', count($relatedContactIds));
        $newGroupId = null;
        
        //changed below if-statement "if ($newGroup) {" to "if ($newGroupName) {" 
        if ($newGroupName) {
            /* Create a new group */
            $gParams = array(
                             'name'          => $newGroupName,
                             'title'         => $newGroupName,
                             'description'   => $newGroupDesc,
                             'is_active'     => true,
                             );
            $group =& CRM_Contact_BAO_Group::create($gParams);
            $groups[] = $newGroupId = $group->id;
        }
        
        if(is_array($groups)) {
            $groupAdditions = array();
            foreach ($groups as $groupId) {
                $addCount =& CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
                if ( !empty($relatedContactIds) ) {
                    $addRelCount =& CRM_Contact_BAO_GroupContact::addContactsToGroup($relatedContactIds, $groupId);
                }
                $totalCount = $addCount[1] + $addRelCount[1];
                if ($groupId == $newGroupId) {
                    $name = $newGroupName;
                    $new = true;
                } else {
                    $name = $allGroups[$groupId];
                    $new = false;
                }
                $groupAdditions[] = array(
                                          'url'      => CRM_Utils_System::url( 'civicrm/group/search',
                                                                               'reset=1&force=1&context=smog&gid=' . $groupId ),
                                          'name'     => $name,
                                          'added'    => $totalCount,
                                          'notAdded' => $addCount[2] + $addRelCount[2],
                                          'new'      => $new
                                          );
            }
            $this->set('groupAdditions', $groupAdditions);
        }
        
        $newTagId = null;
        if ($newTagName) {
            /* Create a new Tag */
            $tagParams = array(
                               'name'          => $newTagName,
                               'title'         => $newTagName,
                               'description'   => $newTagDesc,
                               'is_active'     => true,
                               );
            require_once 'CRM/Core/BAO/Tag.php';
            $id = array();
            $addedTag =& CRM_Core_BAO_Tag::add($tagParams,$id);
            $tag[$addedTag->id] = 1;            
        }
        //add Tag to Import   
        
        if(is_array($tag)) {
            
            $tagAdditions = array();
            require_once "CRM/Core/BAO/EntityTag.php";
            foreach ($tag as $tagId =>$val) {
                $addTagCount =& CRM_Core_BAO_EntityTag::addContactsToTag( $contactIds, $tagId );
                if ( !empty($relatedContactIds) ) {
                    $addRelTagCount =& CRM_Core_BAO_EntityTag::addContactsToTag( $relatedContactIds, $tagId );
                }
                $totalTagCount = $addTagCount[1] + $addRelTagCount[1];
                if ($tagId == $addedTag->id) {
                    $tagName = $newTagName;
                    $new = true;
                } else {
                    $tagName = $allTags[$tagId];
                    $new = false;
                }
                $tagAdditions[] = array(
                                        'url'      => CRM_Utils_System::url( 'civicrm/contact/search',
                                                                             'reset=1&force=1&context=smog&id=' . $tagId ),
                                        'name'     => $tagName,
                                        'added'    => $totalTagCount,
                                        'notAdded' => $addTagCount[2] + $addRelTagCount[2],
                                        'new'      => $new
                                        );
            }
            $this->set('tagAdditions', $tagAdditions);
        }
               
        // add all the necessary variables to the form
        $parser->set( $this, CRM_Import_Parser::MODE_IMPORT );
        
        // check if there is any error occured
        
        $errorStack =& CRM_Core_Error::singleton();
        $errors     = $errorStack->getErrors();
        
        $errorMessage = array();
        
       
        if( is_array( $errors ) ) {
            foreach($errors as $key => $value) {
                $errorMessage[] = $value['message'];
            }
    
            // there is no fileName since this is a sql import
            // so fudge it
            $config =& CRM_Core_Config::singleton( );
            $errorFile =$config->uploadDir . "sqlImport.error.log"; 
            if ( $fd = fopen( $errorFile, 'w' ) ) {
                fwrite($fd, implode('\n', $errorMessage));
            }
            fclose($fd);
            
            $this->set('errorFile', $errorFile);
            $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=1'));
            $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=2'));
            $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=4'));
        }
    }


    /**
     * function for validation
     *
     * @param array $params (reference) an assoc array of name/value pairs
     *
     * @return mixed true or array of errors
     * @access public
     * @static
     */
    static function newGroupRule( &$params ) {
        if (CRM_Utils_Array::value('_qf_Import_refresh', $_POST)) {
            return true;
        }
        
        /* If we're not creating a new group, accept */
        if (! $params['newGroupName']) {
            return true;
        }
        
        $errors = array();
        
        if ( $params['newGroupName'] &&
             ( ! CRM_Utils_Rule::objectExists( trim( $params['newGroupName'] ),
                                               array( 'CRM_Contact_DAO_Group') ) ) ) {
            $errors['newGroupName'] = ts( 'Group \'%1\' already exists.',
                                          array( 1 => $params['newGroupName']));
        }
        return empty($errors) ? true : $errors;
    }

    /**
     * function for validation
     *
     * @param array $params (reference) an assoc array of name/value pairs
     *
     * @return mixed true or array of errors
     * @access public
     * @static
     */
    static function newTagRule( &$params ) {
        if (CRM_Utils_Array::value('_qf_Import_refresh', $_POST)) {
            return true;
        }
        
        /* If we're not creating a new Tag, accept */
        if (! $params['newTagName']) {
            return true;
        }
        
        $errors = array();
        
        if ($params['newTagName']) {
            if (!CRM_Utils_Rule::objectExists(trim($params['newTagName']),array('CRM_Core_DAO_Tag')))
            {
                $errors['newTagName'] = ts( 'Tag \'%1\' already exists.',
                        array( 1 => $params['newTagName']));
            }
        }
        return empty($errors) ? true : $errors;
    }
}
