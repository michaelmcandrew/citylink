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

require_once 'CRM/Core/DAO/Mapping.php';

class CRM_Core_BAO_Mapping extends CRM_Core_DAO_Mapping 
{

    /**
     * class constructor
     */
    function __construct( ) 
    {
        parent::__construct( );
    }

    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. Typically the valid params are only
     * contact_id. We'll tweak this function to be more full featured over a period
     * of time. This is the inverse function of create. It also stores all the retrieved
     * values in the default array
     * 
     * @param array $params      (reference ) an assoc array of name/value pairs
     * @param array $defaults    (reference ) an assoc array to hold the flattened values
     * 
     * @return object     CRM_Core_DAO_Mapping object on success, otherwise null
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults ) 
    {
        $mapping =& new CRM_Core_DAO_Mapping( );
        $mapping->copyValues( $params );
        if ( $mapping->find( true ) ) {
            CRM_Core_DAO::storeValues( $mapping, $defaults );
            return $mapping;
        }
        return null;
    }
    
    /**
     * Function to delete the mapping 
     *
     * @param int $id   mapping id
     *
     * @return boolean
     * @access public
     * @static
     *
     */
    static function del ( $id ) 
    {
        // delete from mapping_field table
        require_once "CRM/Core/DAO/MappingField.php";
        $mappingField =& new CRM_Core_DAO_MappingField( );
        $mappingField->mapping_id = $id;
        $mappingField->find();
        while ( $mappingField->fetch() ) {
            $mappingField->delete();
        }
        
        // delete from mapping table
        $mapping =& new CRM_Core_DAO_Mapping( );
        $mapping->id = $id;
        $mapping->delete();
        CRM_Core_Session::setStatus( ts('Selected Mapping has been Deleted Successfuly.') );
        
        return true;
    }
    
    /**
     * takes an associative array and creates a contact object
     * 
     * The function extract all the params it needs to initialize the create a
     * contact object. the params array could contain additional unused name/value
     * pairs
     * 
     * @param array  $params         (reference) an assoc array of name/value pairs
     * 
     * @return object    CRM_Core_DAO_Mapper object on success, otherwise null
     * @access public
     * @static
     */
    static function add( &$params ) 
    {
        $mapping            =& new CRM_Core_DAO_Mapping( );
        $mapping->copyValues( $params );
        $mapping->save( );

        return $mapping;
    }
    
    /**
     * function to get the list of mappings
     * 
     * @params string  $mappingTypeId  mapping type id 
     *
     * @return array $mapping array of mapping name 
     * @access public
     * @static
     */
    static function getMappings( $mappingTypeId )
    {
        $mapping = array( );
        $mappingDAO =&  new CRM_Core_DAO_Mapping();
        $mappingDAO->mapping_type_id = $mappingTypeId;
        $mappingDAO->find();
        
        while ($mappingDAO->fetch()) {
            $mapping[$mappingDAO->id] = $mappingDAO->name;
        }
        
        return $mapping;
    }

    /**
     * function to get the mapping fields
     *
     * @params int $mappingId  mapping id
     *
     * @return array $mappingFields array of mapping fields
     * @access public
     * @static
     *
     */
    static function getMappingFields( $mappingId )
    {
        //mapping is to be loaded from database
        require_once "CRM/Core/DAO/MappingField.php";
        $mapping =& new CRM_Core_DAO_MappingField();
        $mapping->mapping_id = $mappingId;
        $mapping->orderBy('column_number');
        $mapping->find();
        
        $mappingName = $mappingLocation = $mappingContactType = $mappingPhoneType = array( );
        $mappingRelation = $mappingOperator = $mappingValue = array( );
        while($mapping->fetch()) {
            $mappingName[$mapping->grouping][$mapping->column_number] = $mapping->name;
            $mappingContactType[$mapping->grouping][$mapping->column_number] = $mapping->contact_type;                
            
            if ( !empty($mapping->location_type_id ) ) {
                $mappingLocation[$mapping->grouping][$mapping->column_number] = $mapping->location_type_id;
            }
            
            if ( !empty( $mapping->phone_type_id ) ) {
                $mappingPhoneType[$mapping->grouping][$mapping->column_number] = $mapping->phone_type_id;
            }
            
            if ( !empty($mapping->relationship_type_id) ) {
                $mappingRelation[$mapping->grouping][$mapping->column_number] = 
                    "{$mapping->relationship_type_id}_{$mapping->relationship_direction}";
            }
            
            if ( !empty($mapping->operator) ) {
                $mappingOperator[$mapping->grouping][$mapping->column_number] = $mapping->operator;
            }

            if ( !empty($mapping->value) ) {
                $mappingValue[$mapping->grouping][$mapping->column_number] = $mapping->value;
            }
        }
        
        return array ($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType,
                      $mappingRelation, $mappingOperator, $mappingValue);   
    }

    /**
     *function to check Duplicate Mapping Name
     *
     * @params $nameField  string mapping Name
     *
     * @params $mapTypeId string mapping Type
     *
     * @return boolean
     * 
     */
    static function checkMapping( $nameField, $mapTypeId )
    {
        $mapping =& new CRM_Core_DAO_Mapping();
        $mapping->name = $nameField;
        $mapping->mapping_type_id = $mapTypeId;
        if ( $mapping->find(true) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function returns associated array of elements, that will be passed for search
     *
     * @params int $smartGroupId smart group id 
     *
     * @return $returnFields  associated array of elements
     *
     * @static
     * @public
     */
    static function getFormattedFields($smartGroupId) 
    {
        $returnFields = array();

        //get the fields from mapping table
        $dao =& new CRM_Core_DAO_MappingField( );
        $dao->mapping_id = $smartGroupId;
        $dao->find();
        while ( $dao->fetch( ) ) {
            $fldName = $dao->name;
            if ($dao->location_type_id) {
                $fldName .= "-{$dao->location_type_id}";
            }
            if ($dao->phone_type) {
                $fldName .= "-{$dao->phone_type}" ;
            }
            $returnFields[$fldName]['value'   ] = $dao->value;
            $returnFields[$fldName]['op'      ] = $dao->operator;
            $returnFields[$fldName]['grouping'] = $dao->grouping;
        }
        return $returnFields;
    }

    /**
     * Function to build the mapping form
     *
     * @params object $form        form object
     * @params string $mappingType mapping type (Export/Import/Search Builder)
     * @params int    $mappingId   mapping id
     * @params mixed  $columnCount column count is int for and array for search builder
     * @params int    $blockCount  block count (no of blocks shown) 
     *
     * @return none
     * @access public
     * @static
     */
    static function buildMappingForm(&$form, $mappingType = 'Export', $mappingId = null, $columnNo, $blockCount = 3, $exportMode = null ) 
    {
        if ($mappingType == 'Export') {
            $name = "Map";
            $columnCount = array ('1' => $columnNo);
        } else if ($mappingType == 'Search Builder') {
            $name = "Builder";
            $columnCount = $columnNo;
        }

        //get the saved mapping details
        require_once 'CRM/Core/DAO/Mapping.php';
        require_once 'CRM/Contact/BAO/Contact.php';
        require_once 'CRM/Core/BAO/LocationType.php';

        if ( $mappingType == 'Export' ) {
            $form->applyFilter('saveMappingName', 'trim');
            
            //to save the current mappings
            if ( !isset($mappingId) ) {
                $saveDetailsName = ts('Save this field mapping');
                $form->add('text','saveMappingName',ts('Name'));
                $form->add('text','saveMappingDesc',ts('Description'));
            } else {
                $form->assign('loadedMapping', $mappingId);
                
                $params = array('id' =>  $mappingId);
                $temp   = array ();
                $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);
                
                $form->assign('savedName',$mappingDetails->name);
                
                $form->add('hidden','mappingId',$mappingId);

                $form->addElement('checkbox','updateMapping',ts('Update this field mapping'), null);
                $saveDetailsName = ts('Save as a new field mapping');
                $form->add('text','saveMappingName',ts('Name'));
                $form->add('text','saveMappingDesc',ts('Description'));
            }
            
            $form->addElement('checkbox','saveMapping',$saveDetailsName, null, array('onclick' =>"showSaveDetails(this)"));
            $form->addFormRule( array( 'CRM_Export_Form_Map', 'formRule' ), $form->get( 'mappingTypeId') );
        } else  if ($mappingType == 'Search Builder') { 
            $form->addElement('submit', 'addBlock', ts('Also include contacts where'), 
                              array( 'class' => 'submit-link')
                              );
        }
        
        $defaults        = array( );
        $hasLocationTypes= array();
        $fields          = array();
        
        if ( $mappingType == 'Export' ) {
            $required = true;
        } else if ($mappingType == 'Search Builder') {
            $required = false;
        }

        $fields['Individual'  ] =& CRM_Contact_BAO_Contact::exportableFields('Individual', false, $required);
        $fields['Household'   ] =& CRM_Contact_BAO_Contact::exportableFields('Household', false, $required);
        $fields['Organization'] =& CRM_Contact_BAO_Contact::exportableFields('Organization', false, $required);
        
        //get the current employer for mapping.
        if ( $required ) {
            $fields['Individual']['current_employer']['title'] = ts('Current Employer');
        }
        
        // add component fields
        $compArray = array();

        if ( CRM_Core_Permission::access( 'Quest' ) ) {
            require_once 'CRM/Quest/BAO/Student.php';
            $fields['Student'] =& CRM_Quest_BAO_Student::exportableFields();
            $compArray['Student'] = 'Student';
        }
        
        //we need to unset groups, tags, notes for component export
        require_once 'CRM/Export/Form/Select.php';
        if ( $exportMode != CRM_Export_Form_Select::CONTACT_EXPORT  ) {
            foreach( array( 'groups', 'tags', 'notes' ) as $value ) {
                unset( $fields['Individual'][$value] );
                unset( $fields['Household'][$value] );
                unset( $fields['Organization'][$value] );
            }
        }

        if ( ( $mappingType == 'Search Builder' ) || ( $exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT ) ) {
            if ( CRM_Core_Permission::access( 'CiviContribute' ) ) {
                require_once 'CRM/Contribute/BAO/Contribution.php';
                $fields['Contribution'] =& CRM_Contribute_BAO_Contribution::exportableFields();
                unset($fields['Contribution']['contribution_contact_id']);
                $compArray['Contribution'] = ts('Contribution');
            }
        }
        
        if ( ( $mappingType == 'Search Builder' ) || ( $exportMode == CRM_Export_Form_Select::EVENT_EXPORT ) ) {
            if ( CRM_Core_Permission::access( 'CiviEvent' ) ) {
                require_once 'CRM/Event/BAO/Participant.php';
                $fields['Participant'] =& CRM_Event_BAO_Participant::exportableFields( );
                unset($fields['Participant']['participant_contact_id']);
                $compArray['Participant'] = ts('Participant');
            }
        }

        if ( ( $mappingType == 'Search Builder' ) || ( $exportMode == CRM_Export_Form_Select::MEMBER_EXPORT ) ) {
            if ( CRM_Core_Permission::access( 'CiviMember' ) ) {
                require_once 'CRM/Member/BAO/Membership.php';
                $fields['Membership'] =& CRM_Member_BAO_Membership::getMembershipFields();
                unset($fields['Membership']['membership_contact_id']);
                $compArray['Membership'] = ts('Membership');
            }
        }

        if ( ( $mappingType == 'Search Builder' ) || ( $exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT ) ) {
            if ( CRM_Core_Permission::access( 'CiviPledge' ) ) {
                require_once 'CRM/Pledge/BAO/Pledge.php';
                $fields['Pledge'] =& CRM_Pledge_BAO_Pledge::exportableFields( );
                unset($fields['Pledge']['pledge_contact_id']);
                $compArray['Pledge'] = ts('Pledge');
            }
        }

        if ( ( $mappingType == 'Search Builder' ) || ( $exportMode == CRM_Export_Form_Select::CASE_EXPORT ) ) {
            if ( CRM_Core_Permission::access( 'CiviCase' ) ) {
                require_once 'CRM/Case/BAO/Case.php';
                $fields['Case']    =& CRM_Case_BAO_Case::exportableFields( );
                $compArray['Case'] = ts('Case');
                
                require_once 'CRM/Activity/BAO/Activity.php';
                $fields['Activity']    =& CRM_Activity_BAO_Activity::exportableFields( );
                $compArray['Activity'] = ts('Case Activity');

                unset($fields['Case']['case_contact_id']);
            }
        }
       
        foreach ($fields as $key => $value) {
            foreach ($value as $key1 => $value1) {
                //CRM-2676, replacing the conflict for same custom field name from different custom group.
                if ( $customFieldId = CRM_Core_BAO_CustomField::getKeyID( $key1 ) ) {
                    $customGroupId   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id' );
                    $customGroupName = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', $customGroupId, 'title' );
                    if ( strlen( $customGroupName ) > 13 ) {
                        $customGroupName = substr( $customGroupName, 0, 10 ) . '...';
                    }
                    $mapperFields[$key][$key1] = $customGroupName . ': ' . $value1['title'];
                } else {
                    $mapperFields[$key][$key1] = $value1['title'];
                }
                if ( isset( $value1['hasLocationType'] ) ) {
                    $hasLocationTypes[$key][$key1]    = $value1['hasLocationType'];
                }
            }
        }
        $mapperKeys      = array_keys( $mapperFields );
        
        $locationTypes  =& CRM_Core_PseudoConstant::locationType();
        
        $defaultLocationType =& CRM_Core_BAO_LocationType::getDefault();
            
        /* FIXME: dirty hack to make the default option show up first.  This
         * avoids a mozilla browser bug with defaults on dynamically constructed
         * selector widgets. */
        
        if ($defaultLocationType) {
            $defaultLocation = $locationTypes[$defaultLocationType->id];
            unset($locationTypes[$defaultLocationType->id]);
            $locationTypes = 
                array($defaultLocationType->id => $defaultLocation) + 
                $locationTypes;
        }
        
        $locationTypes = array (' ' => ts('Primary')) + $locationTypes;
       
        $sel1 = array('' => ts('- select record type -')) + CRM_Core_SelectValues::contactType() + $compArray; 
        
        foreach($sel1 as $key=>$sel ) {
            if($key) {
                $sel2[$key] = $mapperFields[$key];
            }
        }
        
        $sel3[''] = null;
        $phoneTypes = CRM_Core_PseudoConstant::phoneType();
        asort($phoneTypes);

        foreach($sel1 as $k=>$sel ) {
            if($k) {
                foreach ($locationTypes as $key => $value) {
                    if ( trim( $key ) != '' ) {
                        $sel4[$k]['phone'][$key] =& $phoneTypes;
                    }
                }
            }
        }

        foreach($sel1 as $k=>$sel ) {
            if ($k) {
                foreach ($mapperFields[$k]  as $key=>$value) {
                    if (isset ( $hasLocationTypes[$k][$key] ) ) {
                        $sel3[$k][$key] = $locationTypes;
                    } else {
                        $sel3[$key] = null;
                    }
                }
            }
        }
        
        //special fields that have location, hack for primary location
        $specialFields = array ('street_address','supplemental_address_1', 'supplemental_address_2', 'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'state_province', 'country', 'phone', 'email', 'im' );
        
        if ( isset($mappingId) ) {
            $colCnt = 0;
            
            list ($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingRelation, $mappingOperator, $mappingValue ) = CRM_Core_BAO_Mapping::getMappingFields($mappingId);
            
            $blkCnt = count($mappingName);
            if ( $blkCnt >= $blockCount ) {
                $blockCount  = $blkCnt + 1;
            }
            for ( $x = 1; $x < $blockCount; $x++ ) { 
                $colCnt = count($mappingName[$x]);
                if ( $colCnt >= $columnCount[$x] ) {
                    $columnCount[$x]  = $colCnt;
                }
            }
        }
        
        $defaults = array();

        $noneArray = array( );
        $nullArray = array( );

        //used to warn for mismatch column count or mismatch mapping 
        $warning = 0;
        for ( $x = 1; $x < $blockCount; $x++ ) {

            for ( $i = 0; $i < $columnCount[$x]; $i++ ) {
                 
                $sel =& $form->addElement('hierselect', "mapper[$x][$i]", ts('Mapper for Field %1', array(1 => $i)), null);
                $jsSet = false;
                
                if ( isset($mappingId) ) {
                    $locationId = isset($mappingLocation[$x][$i])? $mappingLocation[$x][$i] : 0;                
                    if ( isset($mappingName[$x][$i]) ) {
                        if (is_array($mapperFields[$mappingContactType[$x][$i]])) {
                            $phoneType = isset($mappingPhoneType[$x][$i]) ? $mappingPhoneType[$x][$i] : null;
                            
                            if ( !$locationId && in_array($mappingName[$x][$i], $specialFields) ) {
                                $locationId = " ";
                            }

                            $defaults["mapper[$x][$i]"] = array( $mappingContactType[$x][$i],
                                                                 $mappingName[$x][$i],
                                                                 $locationId,
                                                                 $phoneType
                                                                 );

                            if ( ! $mappingName[$x][$i] ) {
                                $noneArray[] = array( $x, $i, 1 );
                            }
                            if ( ! $locationId ) {
                                $noneArray[] = array( $x, $i, 2 );
                            }
                            if ( ! $phoneType ) {
                                $noneArray[] = array( $x, $i, 3 );
                            }
                            $jsSet = true;

                            if ( CRM_Utils_Array::value( $i, $mappingOperator[$x] ) ) {
                                $defaults["operator[$x][$i]"] = CRM_Utils_Array::value( $i, $mappingOperator[$x] );
                            }
                            
                            if (CRM_Utils_Array::value( $i, $mappingValue[$x] ) ) {
                                $defaults["value[$x][$i]"] = CRM_Utils_Array::value( $i, $mappingValue[$x] );
                            }
                        }
                    } 
                } 
                
                $formValues = $form->exportValues( );
                if ( ! $jsSet ) {
                    if ( empty( $formValues ) ) {
                        for ( $k = 1; $k < 4; $k++ ) {
                            $noneArray[] = array( $x, $i, $k );
                        }
                    } else {
                        if ( !empty($formValues['mapper'][$x]) ) {
                            foreach ( $formValues['mapper'][$x] as $value) {
                                for ( $k = 1; $k < 4; $k++ ) {
                                    if ( ! isset ($formValues['mapper'][$x][$i][$k] ) ||
                                         ( ! $formValues['mapper'][$x][$i][$k] ) ) {
                                        $noneArray[] = array( $x, $i, $k );
                                    } else {
                                        $nullArray[] = array( $x, $i, $k );
                                    }
                                }
                            }
                        } else {
                            for ( $k = 1; $k < 4; $k++ ) {
                                $noneArray[] = array( $x, $i, $k );
                            }
                        }
                    }
                }
                
                $sel->setOptions(array($sel1,$sel2,$sel3, $sel4));
                
                if ($mappingType == 'Search Builder') {
                    //CRM -2292, restricted array set
                    $operatorArray = array ('' => ts('-operator-'), '=' => '=', '!=' => '!=', '>' => '>', '<' => '<', 
                                            '>=' => '>=', '<=' => '<=', 'IN' => 'IN',
                                            'LIKE' => 'LIKE', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL' );
                    
                    $form->add('select',"operator[$x][$i]",'', $operatorArray);
                    $form->add('text',"value[$x][$i]",'');
                }
                
            } //end of columnCnt for 
            if ($mappingType == 'Search Builder') {
                $title = ts('Another search field');
            } else {
                $title = ts('Select more fields');
            }
            
            $form->addElement('submit', "addMore[$x]", $title, array( 'class' => 'submit-link' ) );            
            
        } //end of block for

        $js = "<script type='text/javascript'>\n";
        $formName = "document.{$name}";
        if ( ! empty( $nullArray ) ) {
            $js .= "var nullArray = [";
            $elements = array( );
            $seen     = array( );
            foreach ( $nullArray as $element ) {
                $key = "{$element[0]}, {$element[1]}, {$element[2]}";
                if ( ! isset( $seen[$key] ) ) {
                    $elements[] = "[$key]";
                    $seen[$key] = 1;
                }
            }
            $js .= implode( ', ', $elements );
            $js .= "]";
            $js .= "
for(var i=0;i<nullArray.length;i++) {
  {$formName}['mapper['+nullArray[i][0]+']['+nullArray[i][1]+']['+nullArray[i][2]+']'].style.display = '';
}
";
        }
        if ( ! empty( $noneArray ) ) {
            $js .= "var noneArray = [";
            $elements = array( );
            $seen     = array( );
            foreach ( $noneArray as $element ) {
                $key = "{$element[0]}, {$element[1]}, {$element[2]}";
                if ( ! isset( $seen[$key] ) ) {
                    $elements[] = "[$key]";
                    $seen[$key] = 1;
                }
            }
            $js .= implode( ', ', $elements );
            $js .= "]";
            $js .= "
for(var i=0;i<noneArray.length;i++) {
  {$formName}['mapper['+noneArray[i][0]+']['+noneArray[i][1]+']['+noneArray[i][2]+']'].style.display = 'none';  
}
";
        }
        $js .= "</script>\n"; 

        $form->assign('initHideBoxes', $js);
        $form->assign('columnCount', $columnCount);
        $form->assign('blockCount', $blockCount);
        
        $form->setDefaults($defaults);
        
        $form->setDefaultAction( 'refresh' );       
    }    

    /**
     * Function returns associated array of elements, that will be passed for search
     *
     * @params array   $params associated array of submitted values
     * @params boolean $row    row no of the fields
     *
     * @return $returnFields  formatted associated array of elements
     *
     * @static
     * @public
     */
    static function &formattedFields( &$params , $row = false ) {
        $fields = array( );

        if ( empty( $params ) || ! isset( $params['mapper'] ) ) {
            return $fields;
        }
        
        $types = array( 'Individual', 'Organization', 'Household' );
        foreach ($params['mapper'] as $key => $value) {
            $contactType = null;
            foreach ($value as $k => $v) {
                if ( in_array( $v[0], $types ) ) {
                    if ( $contactType && $contactType != $v[0] ) {
                        CRM_Core_Error::fatal( ts( "Cannot have two clauses with different types: %1, %2",
                                                   array( 1 => $contactType, 2 => $v[0] ) ) );
                    }
                    $contactType = $v[0];
                }
                if ( CRM_Utils_Array::value('1',$v) ) {
                    $fldName = $v[1];
                    if ( CRM_Utils_Array::value('2',$v ) ) {
                        $fldName .= "-{$v[2]}";
                    }
                    
                    if ( CRM_Utils_Array::value('3',$v) ) {
                        $fldName .= "-{$v[3]}";
                    }
                    
                    $value = $params['value'   ][$key][$k];
                    if ( $fldName == 'group' || $fldName == 'tag' ) {
                        $value = trim($value);
                        $value = str_replace( '(', '', $value);
                        $value = str_replace( ')', '', $value);
                        
                        $v = explode( ',', $value );
                        $value = array( );
                        foreach ( $v as $i ) {
                            $value[$i] = 1;
                        }
                    }

                    if ( $v[0] == 'Contribution' && substr( $fldName, 0, 7 ) != 'custom_' ) {
                        if ( substr( $fldName, 0, 13 ) != 'contribution_' ) {
                            $fldName = 'contribution_' . $fldName;
                        }
                    }

                    if ( $row ) {
                        $fields[] = array( $fldName,
                                           $params['operator'][$key][$k],
                                           $value,
                                           $key,
                                           $k );
                    } else {
                        $fields[] = array( $fldName,
                                           $params['operator'][$key][$k],
                                           $value,
                                           $key,
                                           0 );

                    }
                }
            }
            if ( $contactType ) {
                $fields[] = array( 'contact_type',
                                   '=',
                                   $contactType,
                                   $key,
                                   0 );
            }
        }
        
        //add sortByCharacter values        
        if ( isset($params['sortByCharacter']) ) {
            $fields[] = array( 'sortByCharacter',
                               '=',
                               $params['sortByCharacter'],
                               0,
                               0 );
        }


        return $fields;
    }

    static function &returnProperties( &$params ) {
        $fields = array( 'contact_type'     => 1,
                         'contact_sub_type' => 1,
                         'sort_name'        => 1 );
        
        if ( empty( $params ) || empty( $params['mapper'] ) ) {
            return $fields;
        }
        
        $locationTypes  =& CRM_Core_PseudoConstant::locationType();
        foreach ( $params['mapper'] as $key => $value ) {
            foreach ( $value as $k => $v ) {
                if ( isset ($v[1] ) ) {
                    if ( $v[1] == 'groups' || $v[1] == 'tags' ) {
                        continue;
                    }
                    
                    if ( isset($v[2]) &&  is_numeric($v[2])  ) {
                        if ( ! array_key_exists( 'location', $fields ) ) {
                            $fields['location'] = array( );
                        }
                        
                        // make sure that we have a location fields and a location type for this
                        $locationName = $locationTypes[$v[2]];
                        if ( ! array_key_exists( $locationName, $fields['location'] ) ) {
                            $fields['location'][$locationName] = array( );
                            $fields['location'][$locationName]['location_type'] = $v[2];
                        }
                        
                        if ( $v[1] == 'phone' || $v[1] == 'email' || $v[1] == 'im' ) {
                            if ( isset( $v[3] ) ) { // phone type handling
                                $fields['location'][$locationName][$v[1] . "-" . $v[3]] = 1;
                            } else {
                                $fields['location'][$locationName][$v[1]] = 1;
                            }
                        } else {
                            $fields['location'][$locationName][$v[1]] = 1;
                        }
                    } else {
                        $fields[$v[1]] = 1;
                    }
                }
            }
        }

        return $fields;

    }

    /**
     * save the mapping field info for search builder / export given the formvalues
     *
     * @param array $params       asscociated array of formvalues
     * @param int   $mappingId    mapping id
     *
     * @return null
     * @static
     * @access public
     */
    static function saveMappingFields(&$params, $mappingId ) 
    {
        //delete mapping fields records for exixting mapping
        require_once "CRM/Core/DAO/MappingField.php";
        $mappingFields =& new CRM_Core_DAO_MappingField();
        $mappingFields->mapping_id = $mappingId;
        $mappingFields->delete( );
        
        if ( empty($params['mapper']) ) {
            return;
        }

        //save record in mapping field table
        foreach ($params['mapper'] as $key => $value) {
            $colCnt = 0;
            foreach ($value as $k => $v) {
                if ( CRM_Utils_Array::value( '1' ,$v ) ) {
                    $saveMappingFields =& new CRM_Core_DAO_MappingField();
                    $saveMappingFields->mapping_id   = $mappingId;
                    $saveMappingFields->name         = CRM_Utils_Array::value( '1' ,$v );
                    $saveMappingFields->contact_type = CRM_Utils_Array::value( '0' ,$v );
                    
                    $locationId =  CRM_Utils_Array::value( '2' ,$v );
                    $saveMappingFields->location_type_id = is_numeric($locationId) ? $locationId : null;
                    
                    $saveMappingFields->phone_type    = CRM_Utils_Array::value( '3' ,$v                       );
                    $saveMappingFields->operator      = CRM_Utils_Array::value( $k, $params['operator'][$key] );
                    $saveMappingFields->value         = CRM_Utils_Array::value( $k, $params['value'][$key]    );
                    $saveMappingFields->grouping      = $key;
                    $saveMappingFields->column_number = $colCnt;

                    $saveMappingFields->save();
                    $colCnt ++;
                }
            }
        }
    }
    
}

