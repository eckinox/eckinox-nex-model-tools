<?php

namespace Eckinox\Nex_model_tools\Controller;

use Eckinox\{
    singleton,
    autoload
};

use Eckinox\Nex\{
    Basic_controller
#    Migrate\Migrate
};

use Eckinox\Nex_model_tools\{
    Model,
    Migrations
};

class Migration extends Basic_controller {
    use singleton;

    public function migrate($segment = "") {
        switch($segment) {
            default:
            case "code":
                return $this->code();

            case "create":
                return $this->create();

            case "alter":
                return $this->alter();

            case "relation":
                return $this->relation();
        }
    }

    /**
     *   @breadcrumb "parent" : null, "icon" : "briefcase", "lang" : "site.client.breadcrumb.index"
     */
    public function code() {
        $migrate = null;

        if ( $this->form_sent() ) {
            foreach(autoload::instance()->ns_stack as $ns => $item) {
                if ( file_exists( $classpath = $item['dir'] . \Eckinox\Nex\MIGRATION_DIR . \Eckinox\Nex\MIGRATE_APP . \Eckinox\PHP_EXT  ) ) {
                    $class = implode('\\', [ $ns, \Eckinox\Nex\MIGRATION_NS, \Eckinox\Nex\MIGRATE_APP ]);
                    $migrate = $class::instance()->build()->autoload();
                }
            }
        }

        return $this->render('/code', get_defined_vars());
    }

    /**
     *   @breadcrumb "parent" : null, "icon" : "briefcase", "lang" : "site.client.breadcrumb.index"
     */
    public function create() {
        if ( $this->form_sent() ) {
            foreach(array_filter((array) $this->post('create')) as $item) {
                $this->_migrations()->table_create($item, true);
            }

            return $this->redirect($this->url('/!/migrate/alter'));
        }

        $create = $this->_migrations()->inexisting_tables();
        return $this->render('/create', get_defined_vars());
    }

    public function alter() {
        if ( $this->form_sent() ) {
            foreach(array_filter((array) $this->post('alter')) as $item) {
                $this->_migrations()->table_alter($item, true);
            }
        }

        $alter = $this->_migrations()->alterable_tables(null);

        if ( $table = $this->get('table') ) {
            $alter = array_intersect_key($alter, array_flip((array) $table));
        }

        return $this->render('/alter', get_defined_vars());
    }

    public function relation() {
        return $this->render('/relation', get_defined_vars());
    }

    protected function _migrations() {
        static $migration = null;
        return $migration ?: $migration = (new Migrations());
    }
}
