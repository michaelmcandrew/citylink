<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 2.3                                                |
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


class CRM_Report_Utils_Get {

    static function getTypedValue( $name, $type ) {
        $value = CRM_Utils_Array::value( $name, $_GET );
        if ( $value === null ) {
            return null;
        }
        return CRM_Utils_Type::escape( $value,
                                       CRM_Utils_Type::typeToString( $type ),
                                       false );

    }

    static function dateParam( $fieldName, &$field, &$defaults ) {
        // type = 12 (datetime) is not recognized by Utils_Type::escape() method, 
        // and therefore the below hack
        $type = 4;

        $from     = self::getTypedValue( "{$fieldName}_from", $type );
        $to       = self::getTypedValue( "{$fieldName}_to",   $type );
                
        $relative = CRM_Utils_Array::value("{$fieldName}_relative", $_GET );
        if( $relative ) {
            list( $from, $to ) = CRM_Report_Form::getFromTo( $relative, null, null );
        }

        if ( !($from || $to) ) {
            return false;
        } else if ( $from || $to || $relative ) {
            // unset other criteria
            self::unsetFilters( $defaults );
        }
        $defaults["{$fieldName}_from"] = CRM_Utils_Date::unformat($from, '');
        $defaults["{$fieldName}_to"]   = CRM_Utils_Date::unformat($to, '');
    }

    static function stringParam( $fieldName, &$field, &$defaults ) {
        $fieldOP = CRM_Utils_Array::value( "{$fieldName}_op", $_GET, 'like' );

        switch ( $fieldOP ) {
        case 'has' :
        case 'sw'  :
        case 'ew'  :
        case 'nhas':
        case 'like':
        case 'neq' :
            $value = self::getTypedValue( "{$fieldName}_value", $field['type'] );
            if ( $value !== null ) {
                self::unsetFilters( $defaults );
                $defaults["{$fieldName}_value"] = $value;
                $defaults["{$fieldName}_op"   ] = $fieldOP;
            }
            break;

        }            
    }

    static function intParam( $fieldName, &$field, &$defaults ) {
        $fieldOP = CRM_Utils_Array::value( "{$fieldName}_op", $_GET, 'eq' );

        switch ( $fieldOP ) {
        case 'lte':
        case 'gte':
        case 'eq' :
        case 'lt' :
        case 'gt' :
        case 'neq':
            $value = self::getTypedValue( "{$fieldName}_value", $field['type'] );
            if ( $value !== null ) {
                self::unsetFilters( $defaults );
                $defaults["{$fieldName}_value"] = $value;
                $defaults["{$fieldName}_op"   ] = $fieldOP;
            }
            break;

        case 'bw' :
        case 'nbw':
            $minValue = self::getTypedValue( "{$fieldName}_min", $field['type'] );
            $maxValue = self::getTypedValue( "{$fieldName}_max", $field['type'] );
            if ( $minValue !== null ||
                 $maxValue !== null ) {
                self::unsetFilters( $defaults );
                $defaults["{$fieldName}_min"] = $minValue;
                $defaults["{$fieldName}_max"] = $maxValue;
                $defaults["{$fieldName}_op" ] = $fieldOP;
            }
            break;

        case 'in' :
            // assuming only one value for now. A plus symbol could be used 
            // to diplsay multiple values in url
            $value    = self::getTypedValue( "{$fieldName}_value", $field['type'] );
            self::unsetFilters( $defaults );
            $defaults["{$fieldName}_value"] = array( $value );
            $defaults["{$fieldName}_op"   ] = $fieldOP;
            break;
        }
    }

    function processChart( &$defaults ) {
        $chartType = CRM_Utils_Array::value( "charts", $_GET );
        if ( in_array( $chartType, array('barGraph','pieGraph' ) ) ) {
            $defaults["charts"] = $chartType;
        }
    }

    function processFilter( &$fieldGrp, &$defaults ) {
        // process only filters for now
        foreach ( $fieldGrp as $tableName => $fields ) {
            foreach ( $fields as $fieldName => $field ) {
                switch ( CRM_Utils_Array::value( 'type', $field ) ) {
                    
                case CRM_Utils_Type::T_INT:
                case CRM_Utils_Type::T_MONEY:
                    self::intParam( $fieldName, $field, $defaults );
                    break;
                    
                case CRM_Utils_Type::T_STRING:
                    self::stringParam( $fieldName, $field, $defaults );
                    break;
                    
                case CRM_Utils_Type::T_DATE:
                case CRM_Utils_Type::T_DATE | CRM_Utils_Type::T_TIME:
                    self::dateParam( $fieldName, $field, $defaults );
                    break;
                }
            }
        }
    }
  
    //unset default filters
    function unsetFilters( &$defaults ) {
        static $unsetFlag = true ;
        if( $unsetFlag ) {
            foreach($defaults as $field_name => $field_value ){
                $newstr  = substr( $field_name , strrpos( $field_name , '_' ) );
                if( $newstr == '_value' || $newstr == '_op'  ||
                    $newstr == '_min'   || $newstr == '_max' ||
                    $newstr == '_from'  || $newstr == '_to'  ||
                    $newstr == '_relative' ) {
                    unset($defaults[$field_name]);
                }
            }
            $unsetFlag = false;
        }
    }
    
    function processGroupBy( &$fieldGrp, &$defaults ) {
        // process only group_bys for now
        $flag = false;

        if ( is_array($fieldGrp) ) {
            foreach ( $fieldGrp as $tableName => $fields ) {
                if ( $groupBys = CRM_Utils_Array::value( "gby", $_GET) ) {
                    $groupBys = explode( ' ' , $groupBys );
                    if ( !empty($groupBys) ) { 
                        if ( !$flag ) {
                            unset( $defaults['group_bys'] );
                            $flag = true;
                        }
                        foreach( $groupBys as $gby ) {
                            if ( array_key_exists($gby, $fields) ) {
                                $defaults['group_bys'][$gby] = 1;
                            }
                        }
                    }
                }
            }
        }
    }

    function processFields( &$reportFields, &$defaults ) {
        //add filters from url 
        if ( is_array($reportFields) ) {
            if ( $urlfileds = CRM_Utils_Array::value( "fld", $_GET) ) {
                $urlfileds = explode( ',' , $urlfileds );
            }
            if ( !empty( $urlfileds ) ){
                foreach ( $reportFields as $tableName => $fields ) {
                    foreach ( $urlfileds as $fld ) {
                        if ( array_key_exists($fld, $fields) ) {
                            $defaults['fields'][$fld] = 1;
                        }
                    }
                }
            }
        }
    }

}
