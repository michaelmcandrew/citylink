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

require_once 'CRM/Report/Form.php';

class CRM_Report_Form_Contact_Summary extends CRM_Report_Form {

    protected $_summary      = null;

    protected $_emailField   = false;
    
    protected $_phoneField   = false;
    
    protected $_addressField = false;
    
    function __construct( ) {
        $this->_columns = 
            array( 'civicrm_contact' =>
                   array( 'dao'       => 'CRM_Contact_DAO_Contact',
                          'fields'    =>
                          array( 'display_name' => 
                                 array( 'title'     => ts( 'Contact Name' ),
                                        'required'  => true,
                                        'no_repeat' => true ),

                                 'id'           => 
                                 array( 'no_display'=> true,
                                        'required'  => true, ), ),
                          'filters'   =>             
                          array( 'sort_name'    => 
                                 array( 'title'      => ts( 'Contact Name' )  ),
                                 'source'       => 
                                 array( 'title'      => ts( 'Contact Source' ),
                                        'type'       => CRM_Utils_Type::T_STRING ),
                                 'id'           => 
                                 array( 'title'      => ts( 'Contact ID' ),
                                        'no_display' => true ), ),
                          'grouping'  => 'contact-fields',
                          ),
                   'civicrm_email'   =>
                   array( 'dao'       => 'CRM_Core_DAO_Email',
                          'fields'    =>
                          array( 'email' => 
                                 array( 'title'      => ts( 'Email' ),
                                        'no_repeat'  => true 
                                        ),
                                 ),
                          'grouping'  => 'contact-fields', 
                          ),
                   
                   'civicrm_address' =>
                   array( 'dao'       => 'CRM_Core_DAO_Address',
                          'grouping'  => 'contact-fields',
                          'fields'    =>
                          array( 'street_address'    => 
                                 array( 'default' => true ),
                                 'city'              => 
                                 array( 'default' => true ),
                                 'postal_code'       => null,
                                 'state_province_id' => 
                                 array( 'title'   => ts( 'State/Province' ), ),
                                 'country_id'        => 
                                 array( 'title'   => ts( 'Country' ),  
                                        'default' => true ), 
                                 ),
                          'filters'   =>             
                          array( 'country_id' => 
                                 array( 'title'   => ts( 'Country' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::country( ),
                                        ), 
                                 'state_province_id' =>  
                                 array( 'title'   => ts( 'State / Province' ), 
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::stateProvince( ), ), 
                                 ), 
                          ),
                   'civicrm_phone' => 
                   array( 'dao'       => 'CRM_Core_DAO_Phone',
                          'fields'    =>
                          array( 'phone'  => null),
                          'grouping'  => 'contact-fields',
                          ),

                   'civicrm_group' => 
                   array( 'dao'    => 'CRM_Contact_DAO_Group',
                          'alias'  => 'cgroup',
                          'filters' =>             
                          array( 'gid' => 
                                 array( 'name'    => 'id',
                                        'title'   => ts( 'Group' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::staticGroup( ) 
                                        ), 
                                 ), 
                          ),
                   );

        parent::__construct( );
    }
    
    function preProcess( ) {
        parent::preProcess( );
    }
    
    function select( ) {
        $select = array( );
        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                        if ( $tableName == 'civicrm_address' ) {
                            $this->_addressField = true;
                        } else if ( $tableName == 'civicrm_email' ) {
                            $this->_emailField = true;
                        } else if ( $tableName == 'civicrm_phone' ) {
                            $this->_phoneField = true;
                        }

                        $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                        $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                        $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                    }
                }
            }
        }

        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }

    static function formRule( &$fields, &$files, $self ) {  
        $errors = $grouping = array( );
        return $errors;
    }

    function from( ) {
        $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} ";

        if ( $this->_addressField ) {
            $this->_from .= "
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";
        }
        if ( $this->_emailField ) {
            $this->_from .= "
            LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']} 
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1) ";
        }

        if ( $this->_phoneField ) {
            $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
        }   

        if ( !empty( $this->_params['gid_value'] ) ) {
            $this->_from .= "
            LEFT  JOIN civicrm_group_contact  group_contact 
                    ON {$this->_aliases['civicrm_contact']}.id = group_contact.contact_id  AND 
                        group_contact.status = 'Added'
            LEFT  JOIN civicrm_group  {$this->_aliases['civicrm_group']} 
                    ON group_contact.group_id = {$this->_aliases['civicrm_group']}.id ";
        }
        
    }

    function where( ) {
        $clauses = array( );
        $this->_having = '';
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('filters', $table) ) {
                foreach ( $table['filters'] as $fieldName => $field ) {
                    $clause = null;
                    $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
                    if ( $op ) {
                        $clause = 
                            $this->whereClause( $field,
                                                $op,
                                                CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                                CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                                CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
                    }
                    
                    if ( ! empty( $clause ) ) {
                        $clauses[] = $clause;
                    }
                }
            }
        }

        if ( empty( $clauses ) ) {
            $this->_where = "WHERE ( 1 ) ";
        } else {
            $this->_where = "WHERE " . implode( ' AND ', $clauses );
        }
    }

    function groupBy( ) {
        $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
    }
    
    function postProcess( ) {

        $this->beginPostProcess( );

        $sql  = $this->buildQuery( true );
        
        $rows = $graphRows = array();
        $this->buildRows ( $sql, $rows );
        
        $this->formatDisplay( $rows );
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );	
    }

    function alterDisplay( &$rows ) {
        // custom code to alter rows
        $entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            // convert display name to links
            if ( array_key_exists('civicrm_contact_display_name', $row) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                $url = CRM_Report_Utils_Report::getNextUrl( 'contact/detail', 
                                              'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
                                              $this->_absoluteUrl, $this->_id );
                $rows[$rowNum]['civicrm_contact_display_name_link' ] = $url;
                $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact details for this contact.");
                $entryFound = true;
            }

            // handle country
            if ( array_key_exists('civicrm_address_country_id', $row) ) {
                if ( $value = $row['civicrm_address_country_id'] ) {
                    $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country( $value, false );
                }
                $entryFound = true;
            }
            if ( array_key_exists('civicrm_address_state_province_id', $row) ) {
                if ( $value = $row['civicrm_address_state_province_id'] ) {
                    $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince( $value, false );
                }
                $entryFound = true;
            }


            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
