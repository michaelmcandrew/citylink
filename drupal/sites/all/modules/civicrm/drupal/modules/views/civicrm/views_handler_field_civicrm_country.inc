<?php

/**
 * Field handler to provide acess control for the state field (which is a lookup)
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_civicrm_country extends views_handler_field_civicrm {
    static $_countries;

    function construct( ) {
        if ( ! self::$_countries ) {
            civicrm_initialize( );
            require_once 'CRM/Core/PseudoConstant.php';
            self::$_countries = CRM_Core_PseudoConstant::countryIsoCode( );
        }
    }

    function render( $values ) {
        $sid = $values->{$this->field_alias};
        if ( empty( $sid ) ||
             (int ) $sid <= 0 ) {
            return null;
        }

        return self::$_countries[$values->{$this->field_alias}];
    }
}
?>