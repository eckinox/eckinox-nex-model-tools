<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\{
    config,
    singleton
};

use Eckinox\Nex\{
    Model,
    Database
};

class Field {
    use config, singleton;

    protected $type = [];
    protected $fields = [];
    protected $definition = [];

    protected function __construct() {
        $this->type = $this->config('Nex.database._default.type');
        $this->fields = $this->config("Nex_model_tools.fields");
        $this->definition = $this->config("Nex_model_tools.definition");
    }

    public function get_field($field_definition, $skip_default_definition = false) {
        if ( $this->fields['custom'][ $field_definition['type'] ] ?? false ) {
            $found_field = $this->get_field( $this->fields['custom'][$field_definition['type']], true );
        }
        elseif ( $this->fields[$this->type][ $field_definition['type'] ] ?? false ) {
            $found_field = $this->get_field( $this->fields[$this->type][$field_definition['type']], true );
        }
        
        return array_replace_recursive($skip_default_definition ? [] : ( $this->definition ?? [] ), $found_field ?? [], $field_definition, [ 'type' => ($found_field ?? $field_definition)['type'] ]);
    }

}
