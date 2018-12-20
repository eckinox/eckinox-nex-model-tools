<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\Annotation;

use Eckinox\Nex\valid;

trait validation {
    use convert;

    protected $validation_data;

    protected $validation_error_list = [];

    public function load_from_input($data, $fields_mapping = []) {
        $fields = Field::instance();
        $this->validation_data = $data;

        foreach($this->_fieldlist() as $key => $value) {
            if ( isset($data[$key]) ) {
                $field_definition = $fields->get_field( is_string($value) ? [ 'type' => $value ] : $value );
                $this[ $fields_mapping[$key] ?? $key ] = $this->_convert_value_from_field( $field_definition, $data[$key] );
            }
        }

/*
        foreach($this->_relationlist()['parentOf'] ?? [] as $key => $unused) {
            foreach($data[$key] ?? [] as $field => $value) {
                $this[$key][ $fields_mapping[$key][$field] ?? $field ] = $value;
            }
        }
*/
        return $this;
    }

    public function validate() {
        $this->validation_error_list = [];

        foreach($this->_validation_def() as $key => $value) {
            if ( ! isset($this->validation_data[$key]) ) {
                continue;
            }

            if ( is_string($value) ) {
                $value = [ 'type' => $value ];
            }

            # Is it required ?
            if ( ( $value['required'] ?? false ) && ( true !== $result = $this->_validate_required($this->validation_data[$key]) ) ) {
                $this->validation_error_list[$key] = $this->validation_error_list[$key] ?? [];
                $this->validation_error_list[$key][] = $result;
            }


            if ( ( $this->validation_data[$key] !== "" ) && ( true !== $result = $this->{"_validate_".$value['type']}($this->validation_data[$key], $value) ) ) {
                $this->validation_error_list[$key] = $this->validation_error_list[$key] ?? [];
                $this->validation_error_list[$key][] = $result;
            }
        }

        return empty($this->validation_error_list);
    }

    public function validation_error_list() {
        return $this->validation_error_list;
    }

    protected function _validation_def() {
        return Annotation::instance()->annotations()[get_class()]['class']['validation'] ?? trigger_error("@validation annotation was not found within «".get_class()."» object", \E_USER_ERROR);
    }

    protected function _fieldlist() {
        return Annotation::instance()->annotations()[get_class()]['class']['field'] ?? trigger_error("@fields annotation was not found within «".get_class()."» object", \E_USER_ERROR);
    }

    protected function _relationlist() {
        return Annotation::instance()->annotations()[get_class()]['class']['relation'] ?? trigger_error("@fields annotation was not found within «".get_class()."» object", \E_USER_ERROR);
    }

    protected function _validate_password($value, $param) {
        # Confirmation field is optional
        if ( ! empty($param['confirm']) && ( $value !== $this->validation_data[$param['confirm']] ) ) {
            return "password.mismatch";
        }

        return valid::password($value, $param['min_char'] ?? 5, $param['max_char'] ?? 255) ?: "password.invalid";
    }

    protected function _validate_readonly($value)  {
        return "readonly";
    }

    protected function _validate_email($value) {
        return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $value) ?: "email.invalid";
    }

    protected function _validate_zipcode($value) {
        return valid::zip($value) ?: "zipcode.invalid";
    }

    protected function _validate_required($value) {
        return (bool) $value ?: "required.invalid";
    }

    protected function _validate_alpha($value) {
        return valid::alpha($value) ?: "alphabetic.invalid";
    }

    protected function _validate_alphanumeric($value) {
        return valid::alpha_numeric($value) ?: "alphabetic.invalid";
    }

    protected function _validate_numeric($value) {
        return valid::numeric($value) ?: "numeric.invalid";
    }

    protected function _validate_username($value) {
        return valid::username($value, $param['min_char'] ?: 5, $param['max_char'] ?: 75) ?: "username.invalid";
    }

    protected function _validate_regex($value, $param) {
        return valid::regex($value, $param['pattern']);
    }
}
