<?php

namespace Core;

abstract class Endpoint {

    /**
     * Action name to be handled
     *
     * @var string
     */
    protected $action;

    /**
     * Check if Endpoint will be accessible only by authorized users
     *
     * @var bool
     */
    protected $authOnly = false;

    /**
     * Endpoint handler
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * Check if user is authorized to perform the action
     *
     * @return mixed
     */
    protected function authorized()
    {
        return true;
    }

    /**
     * Register an endpoint
     */
    public function register()
    {
        add_action("wp_ajax_{$this->action}", [$this, 'handler']);

        if(!$this->authOnly) {
            add_action("wp_ajax_nopriv_{$this->action}", [$this, 'handler']);
        }

        return $this;
    }

    /**
     * Handle the request if authorized to
     */
    public function handler() {
        if($this->authorized()) {
            $this->handle();
        } else {
            wp_send_json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }
}
