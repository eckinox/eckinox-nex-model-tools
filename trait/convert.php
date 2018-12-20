<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\Annotation,
    Eckinox\Configuration;

use Eckinox\Nex\valid,
    Eckinox\Nex\date;

trait convert {

    protected $convert_type = [
        "text" => "string",
        "varchar" => "string",
    ];

    protected function _convert_value_from_field($field, $value) {
        # Convert function to-be used
        $field_type = isset( $this->convert_type[ $field['type'] ] ) ?  $this->convert_type[ $field['type'] ] : $field['type'];

        # Nullable value & value is empty, will be set to null.
        if ( null !== $value = $this->_convert_nullable_value($field, $value) ) {
            $value = $this->{"_convert_$field_type"}($field, $value);
        }

        return $value;
    }

    protected function _convert_string($field, $value) {
        return is_string($value) ? (string) $value : "";
    }

    protected function _convert_int($field, $value) {
        return is_numeric($value) ? (int) $value : 0;
    }

    protected function _convert_float($field, $value) {
        return is_numeric($value) ? (float) $value : 0;
    }

    protected function _convert_double($field, $value) {
        return is_numeric($value) ? (double) $value : 0;
    }

    protected function _convert_nullable_value($field, $value) {
        return ( $field['null'] ?? false ) && ( $value === "" ) ? null : $value;
    }

    protected function _convert_date($field, $value) {
        return ( $ts = date::dateToTimestamp($value) ) ? date::date(Configuration::get('nex_model_tools.converter.format.date'), $ts) : null;
    }

    protected function _convert_datetime($field, $value) {
        return ( $ts = date::dateToTimestamp($value) ) ? date::date(Configuration::get('nex_model_tools.converter.format.datetime'), $ts) : null;
    }
}
