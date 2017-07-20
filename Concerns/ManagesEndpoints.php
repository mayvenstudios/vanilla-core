<?php

namespace Vanilla\Concerns;

use Vanilla\Endpoint;

trait ManagesEndpoints {

    /**
     * Register custom endpoints
     */
    protected function registerEndpoints()
    {
        add_rewrite_endpoint($this->endpointsNamespace(), E_ALL);
        add_action('template_redirect', [$this, 'redirectEndpoints']);
    }

    protected function endpointsNamespace()
    {
        return $this->config('endpoints_namespace', 'endpoints');
    }

    public function redirectEndpoints()
    {
        global $wp_query;

        $action = $wp_query->get($this->endpointsNamespace());
        if (!$action) {
            return;
        }

        $endpoint = $this->endpoint($action);

        if ($endpoint) {
            $endpoint->handler();
        } else {
            wp_send_json(['status' => 'error', 'message' => 'Not found'], 404);
        }
    }

    /**
     * @param string $name
     *
     * @return null|Endpoint
     */
    protected function endpoint($name)
    {
        $files = collect(glob($this->appPath('Endpoints/*.php')));

        return $files->reduce(function ($instance, $path) use ($name) {
            $className = "App\\Endpoints\\" . rtrim(basename($path), '.php');
            if (is_null($instance) && class_exists($className)) {
                $object = new $className;
                if ($object->name() === $name) {
                    $instance = $object;
                }
            }

            return $instance;
        }, null);
    }
}