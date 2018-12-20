<?php

namespace Eckinox\Nex_model_tools\Model;

use Eckinox\Nex_model_tools\{
    Information_schema_model
};

class Table extends Information_schema_model {
    public $tablename = "tables";

    public function get_tables() {
        return $this->where('SELF.TABLE_SCHEMA', $this->default_database())->load_all();
    }

    public function get_columns() {
        return (new Column)->where([
                'SELF.TABLE_NAME'   => $this['TABLE_NAME'],
                'SELF.TABLE_SCHEMA' => $this['TABLE_SCHEMA']
            ])
            ->orderBy("SELF.ordinal_position")
            ->load_all();
    }

    public function name() {
        return $this['TABLE_NAME'];
    }
}
