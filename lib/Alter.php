<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\{
    config,
    singleton
};

class Alter {
    use config;

    protected $table = "";
    protected $query = [];

    public function __construct($table) {
        $this->table = $table;
    }

    public function build() {
        if (empty($this->query)) {
            return false;
        }

        $return = implode(' ', [
            $this->_alter_table($this->table),
            implode(', ', $this->query),
        ]);

        $this->query = [];

        return $return;
    }

    public function add_column($field_definition, $options = []) {
        $this->query[] = implode(' ', [
            "ADD COLUMN $field_definition ",
            $options['position'] ? "AFTER `{$options['position']}`" : 'FIRST',
        ]);

        return $this->build();
    }

    public function alter_column($field, $field_definition, $options = []) {
        $this->query[] = implode(' ', [
            "CHANGE `$field` $field_definition ",
        ]);

        return $this->build();
    }

    protected function _alter_table($table) {
        return "ALTER TABLE `$table`";
    }
}
