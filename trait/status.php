<?php

namespace Eckinox\Nex_model_tools;

/**
 *  @fields {
 *      "status" : { "type" : "string", "default" : "active" }
 *  }
 */
trait status {
    protected $status_field = "status";

    protected $status_list = [
        'active', 'inactive', 'archive', 'delete'
    ];

    protected $status_active_list = [
        'active'
    ];

    protected $status_default = "active";

    public function load_all_active($limit = null, $offset = null) {
        return $this->_filter_active()->load_all($limit, $offset);
    }

    public function load_count_active() {
        return $this->_filter_active()->load_count();
    }

    public function get_status() {
        return $this[$this->status_field] ?: $this->status_default;
    }

    public function set_status($status) {
        $this[$this->status_field] = $status;
        return $this;
    }

    public function is_active() {
        return $this[$this->status_field] === 'active';
    }

    public function is_inactive() {
        return $this[$this->status_field] === 'inactive';
    }

    public function is_delete() {
        return $this[$this->status_field] === 'delete';
    }

    public function is_archive() {
        return $this[$this->status_field] === 'archive';
    }

    protected function _filter_active() {
        return $this->in("{$this->self_alias}.{$this->status_field}", $this->status_active_list);
    }
}
