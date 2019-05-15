<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\Annotation,
    Eckinox\Event,
    Eckinox\config;

use Eckinox\Nex;

class Migrations {
    use config;

    protected $fields = null;
    protected $tables = null;


    public function __construct() {
        $this->tables = ( new Model\Table() )->get_tables();
        $this->fields = Field::instance();

        # Event::instance()->on('Nex.model.register', function( $e, $model_key ) {
            # $annotation = Annotation::instance();
            # $list = $annotation->annotations();
        # });
    }

    public function get_table_fields($table, $raw = false) {
        if ( $this->tables->find($table, 'TABLE_NAME') ) {
            $columns = $this->tables->get_columns();

            if ($raw) {
                return $columns;
            }

            $fields = [];
            foreach($columns as $item) {
                #dump($item["COLUMN_DEFAULT"]);
                $fields[ strtolower($item['COLUMN_NAME']) ] = [
                    "type" => strtolower($item['DATA_TYPE']),
                    "null" => $item['IS_NULLABLE'] === 'YES',
                    "attr" => array_filter([
                        $item["EXTRA"] === "auto_increment" ? "auto_increment" : "",
                        $item["COLUMN_KEY"] === "PRI" ? "primary_key" : "",
                        strpos($item["COLUMN_TYPE"], "unsigned") !== false ? "unsigned" : "",
                    ]),
                ] + array_filter([
                    "on_update" => $item["EXTRA"] === "on update CURRENT_TIMESTAMP" ? "CURRENT_TIMESTAMP" : "",
                    "default" => $item["COLUMN_DEFAULT"],
                ], function( $item ) {
                    return $item !== "";
                });
            }

            return $fields;
        }
        else {
            trigger_error("Requested table '$table' was not found within table list given by Information_schema table");
        }
    }

    public function inexisting_tables() {
        $table = $this->tables->getListOf('TABLE_NAME');

        foreach($this->project_model_list() as $model => $class) {
            if ( ! in_array($class['class']['table']['name'], $table) ) {
                $model_tables[$model] = $class['class']['table']['name'];
            }
        }

        return $model_tables ?? [];
    }

    public function alterable_tables() {
        $alterable = [];

        foreach($this->tables as $table) {
            $name = $table->name();
            $alterable[$name] = [];

            $fields = $this->get_table_fields($name, false);
            $from_annotation = $this->_get_table_fields_definition($name);

            foreach($from_annotation as $key => $item) {
                $alterable[$name][$key] = [];

                if ( empty($fields[$key]) ) {
                    $alterable[$name][$key] = [
                        'new_msg' => "New field",
                        'new' => true,
                    ];
                }
                else {
                    if ( $fields[$key]['type'] !== $item['type'] ) {
                        $alterable[$name][$key]['type'] = $item['type'];
                        $alterable[$name][$key]['type_info'] = "Type change of $key - {$fields[$key]['type']} -> {$item['type']}";
                    }

                    if ( ( $fields[$key]['null'] ?? false ) !== $item['null'] ) {
                        $alterable[$name][$key]['null'] =  $item['null'];
                        $alterable[$name][$key]['null_msg'] = "Null attributes has changed to «" . ($item['null'] ? "nullable" : "not nullable")."»";
                    }

                    if ( ( $fields[$key]['default'] ?? "" ) !== trim( $item['default'] ?? "" , "'\"") ) {
                        $alterable[$name][$key]['default_msg'] = "Default value has changed to «". ( $item['default'] ?? "" ) ."»";
                    }

                    if ( $added_attributes = array_diff($item['attr'] ?? [], $fields[$key]['attr'] ?? [], ['unique']) ) {
                        $alterable[$name][$key]['attr_added'] = "Attribute(s) added to '$key' -> ".json_encode(array_values($added_attributes));
                    }

                    #if ( $removed_attributes = array_diff($fields[$key]['attr'] ?? [], $item['attr'] ?? []) ) {
                    #    $alterable[$name][$key]['attr_removed'] = "Attribute(s) removed from '$key' -> ".json_encode(array_values($removed_attributes));
                    #}
                }

                # if ( $from_annotation[$key]['size'] && ( $from_annotation[$key]['size'] !== $item['size'] ) ) {
                #     # $alterable[$name][$key]['display_width'] =
                # }
            }

            $alterable[$name] = array_filter($alterable[$name]);
        }

        return array_filter($alterable);
    }

    public function project_model_list() {
        $annotation = Annotation::instance();
        $list = $annotation->annotations();

        $stack = [];

        foreach( $list as $model => $item ) {
            if ( $item['type'] === 'model' && $item['class'] ) {
                $stack[$model] = $item;
            }
        }

        return $stack;
    }

    public function table_create($name, $run_query = false) {
        $list = $this->project_model_list();

        $def = $list[$name]['class'] ?? false;

        if ( ! $def ) {
            return false;
        }

        $engine = $def['table']['engine'] ?? $this->config('Nex_model_tools.table.engine');
        $charset = $def['table']['charset'] ?? $this->config('Nex_model_tools.table.charset');

        $pk = $fieldlist = [];

        # Backward compat allows "s"
        foreach($def['field'] ?? $def['fields'] ?? [] as $key => $field) {
            $fieldlist[] = $this->_generate_field($key, $field, $pk);
        }

        if ( ! $fieldlist ) {
            return false;
        }

        if ( $pk ) {
            $fieldlist[] = "PRIMARY KEY(`" . implode('`,`', $pk) . "`)";
        }

        $fieldlist = implode(", \n\t", $fieldlist);
        $sql = "CREATE TABLE IF NOT EXISTS `{$def['table']['name']}` ( ". PHP_EOL .
               "\t$fieldlist" . PHP_EOL . ") ENGINE={$engine} CHARSET={$charset}";

        return $run_query ? ( new $name() )->query($sql) : $sql;
    }

    public function table_alter($name, $run_query = false) {
        $return = [];
        $alter = new Alter($name);

        $last_field = false;

        $fields = $this->get_table_fields($name, false);
        $from_annotation = $this->_get_table_fields_definition($name);

        foreach($from_annotation as $key => $item) {
            $sql = false;
            $opt = $pk = [];

            // Adding a new column
            if ( empty($fields[$key]) ) {
                $opt = [
                    'position' => $last_field
                ];

                $sql = $alter->add_column($this->_generate_field($key, $item, $pk), $opt);

                if ( $pk ) {
                    $sql .= ", ADD PRIMARY KEY (`$key`)";
                }
            }
            else {
                $new_def = [];

                if ( $fields[$key]['type'] !== $item['type'] ) {
                    $new_def['type'] = $item['type'];
                }

                if ( ( $fields[$key]['null'] ?? false ) !== $item['null'] ) {
                    $new_def['null'] = $item['null'];
                }

                $default = array_key_exists('default', $item) ? ( is_string($item['default']) ? trim($item['default'], "'\"") : $item['default'] ) : "";

                if ( ( $fields[$key]['default'] ?? "" ) !== $default ) {
                    $new_def['default'] = $default;
                }

                #dump($name, $item, $key, $fields[$key], $new_def);
                if ( $added_attributes = array_diff($item['attr'] ?? [], $fields[$key]['attr'] ?? [], [ 'unique' ]) ) {
                    $new_def['attr'] = $added_attributes;
                }

                /* if ( $removed_attributes = array_diff($fields[$key]['attr'] ?? [], $item['attr'] ?? []) ) {
                    $alterable[$key]['attr_removed'] = "Attribute(s) removed from '$key' -> ".json_encode(array_values($removed_attributes));
                }*/

                if ( $new_def ) {
                    $sql = $alter->alter_column($key, $this->_generate_field($key, array_replace_recursive($item, $fields[$key], $new_def)), $opt);
                }
            }


            if ($sql && $run_query) {
                dump($sql);
                Nex\Database::instance()->query($sql);
            }

            # if ( $from_annotation[$key]['size'] && ( $from_annotation[$key]['size'] !== $item['size'] ) ) {
            #     # $alterable[$name][$key]['display_width'] =
            # }

            $last_field = $key;
        }

        return $return;
    }

    public function table_indexes($idx) {

    }

    public function table_relations($idx) {

    }

    protected function _query_sql($sql) {
        $db = Nex\Database::instance();

        foreach($sql as $item) {
            dump($item);
            $db->query($item);
        }
    }

    protected function _generate_field($name, $field, &$pk = null) {
        $def = is_string($field) ? $this->fields->get_field([ 'type' => $field ]) : $this->fields->get_field($field);
        $attr = array_map("strtolower", $def['attr'] ?? []);

        if ( in_array("primary_key", $attr) ) {
            $pk[] = $name;
        }

        $sql[] = "`$name`";
        $sql[] = strtoupper( $def['type'] ) . ( isset($def['size']) ? "({$def['size']})" : "" );
        $sql[] = in_array("unsigned", $attr) ? "UNSIGNED" : "";
        $sql[] = $def['null'] ? "NULL" : "NOT NULL";
        $sql[] = in_array("auto_increment", $attr) ? "AUTO_INCREMENT" : "";
        $sql[] = in_array("unique", $attr) ? "UNIQUE" : "";

        if ( array_key_exists( 'default', $def ) ) {
            if ( is_null($def['default']) ) {
                $def['default'] = "NULL";
            }
            $sql[] = "DEFAULT " . $def['default'];
        }

        if ( $def['on_update'] ?? false ) {
            $sql[] = "ON UPDATE " . $def['on_update'];
        }

        return implode(" ", array_filter($sql));
    }

    /*
    protected function fields->get_field($field_definition, $skip_default_definition = false) {
        if ( $this->fields['custom'][ $field_definition['type'] ] ?? false ) {
            $found_field = $this->fields->get_field( $this->fields['custom'][$field_definition['type']], true );
        }
        elseif ( $this->fields[$this->type][ $field_definition['type'] ] ?? false ) {
            $found_field = $this->fields->get_field( $this->fields[$this->type][$field_definition['type']], true );
        }

        return array_replace_recursive($skip_default_definition ? [] : ($this->definition ?? []), $field_definition, $found_field ?? []);
    }
    */

    protected function _get_table_fields_definition($table) {
        $list = $this->project_model_list();

        foreach($list as $item) {
            if ( $table === $item['class']['table']['name'] ?? false ) {
                $fields = $item['class']['field'] ?? "";
                break;
            }
        }


        if ( $fields ?? false ) {
            foreach($fields as $key => $field) {
                $fieldlist[$key] = $this->fields->get_field( is_string($field) ? $this->fields->get_field([ 'type' => $field ]) : $this->fields->get_field($field));
            }
        }

        return $fieldlist ?? [];
    }

}
