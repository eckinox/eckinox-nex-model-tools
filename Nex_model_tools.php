<?php
namespace Eckinox\Nex_model_tools;

use Eckinox\{
    reg,
    singleton,
    Annotation,
    Component,
    Event
};

class Nex_model_tools extends Component {
    use singleton, reg;

    public function __construct(...$args) {
        parent::__construct(...$args);

        Event::instance()->on('Nex.model.register', function($e, $table) {
            $obj = $e->caller();

            # Prefill table name if it's not defined
            if ( empty($obj->tablename) &&
                ( $annotation = Annotation::instance()->get_from_object($obj) ) &&
                ( $annotation['class']['table'] ?? false ) ) {

                foreach($annotation['class']['relation'] ?? [] as $field => $rel) {
                    $obj::$$field = $rel;
                }

                $obj->set_table_name( $annotation['class']['table']['name'] );
            }


        });
    }
}
