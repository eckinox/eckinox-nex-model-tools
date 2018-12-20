<?php

namespace Eckinox\Nex_model_tools\Model;

use Eckinox\Nex_model_tools\{
    Information_schema_model
};

class Column extends Information_schema_model {
    public $tablename = "columns";

    public function get_table_columns($table) {
        die("Column model -> add database check before loading fields");

        return $this->where('SELF.TABLE_NAME', $table)
            ->orderBy("SELF.ordinal_position")
            ->load_all();
    }
}
