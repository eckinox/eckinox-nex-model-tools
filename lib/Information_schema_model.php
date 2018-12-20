<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\Nex\{
    Model,
    Database
};

class Information_schema_model extends Model {

    protected $default_database = "";

    /**
     * Constructor
     * @param string $database key of database in config
     */
    public function __construct($database = 'information_schema') {
        $default = $this->config("Nex.database._default");

        $this->default_database( $default['name'] );

        parent::__construct(array_merge($default, $this->config("Nex.database.information_schema")));
    }

    public function default_database($set = null) {
        return $set === null ? $this->default_database : $this->default_database = $set;
    }
}
