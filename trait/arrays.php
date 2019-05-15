<?php

namespace Eckinox\Nex_model_tools;

use Eckinox\Annotation,
    Eckinox\Event;

trait arrays {

    protected $array_fields = [];

    public function __construct_arrays() {
        $a = Annotation::instance()->get_class_methods(static::class);

        foreach($a['class']['field'] as $key => $item) {
            if ( ( is_string($item) && $item === "array" ) || ( "array" === ( $item['type'] ?? false ) ) ) {
                $this->array_fields[] = $key;
            }
        }

        if ($this->array_fields) {
            Event::instance()->on("{$this->model_key}.loading_done", function($e) {
                if ( $e->caller() === $this ) {
                    $this->each(function($instance) {
                        foreach($this->array_fields as $item) {
                            $instance[$item] = json_decode($instance[$item] ?? "[]", true);
                        }
                    });
                }
            });
        }
    }

    public function loading_donez() {
        return $this->each(function() {
            $this['latest_dns'] = json_decode($this['latest_dns'] ?: '[]', true);
        });
    }

    public function savez($reload = false, $mode = null) {
        $this['latest_dns'] = json_encode($dns = $this['latest_dns']);
        $this['latest_whois'] = json_encode($whois = $this['latest_whois']);
        $this['latest_connections'] = json_encode($connections = $this['latest_connections']);
        $this['latest_ssl_certificate'] = json_encode($ssl = $this['latest_ssl_certificate']);

        parent::save($reload, $mode);

        $this['latest_dns'] = $dns;
        $this['latest_whois'] = $whois;
        $this['latest_connections'] = $connections;
        $this['latest_ssl_certificate'] = $ssl;

        return $this;
    }

    protected function _convert_array($field, $value) {
        return json_encode($value);
    }
}
